<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Services\PayMongoPaymentService;
use App\Models\CustomerPaymentSubmission;
use Illuminate\Support\Facades\Schema;

class TransactionController extends Controller
{
    private function checkTransactionAccess()
    {
        if (auth()->check() && !in_array(auth()->user()->role, ['Administrator', 'Cashier'])) {
            abort(403, 'Unauthorized. Only Cashiers and Administrators can manage Transactions.');
        }
    }

    private function applyWarrantyIfPaid(\App\Models\Transaction $transaction)
    {
        if ($transaction->payment_status === 'Paid') {
            $report = $transaction->report;
            if ($report && $report->appliance) {
                // Determine duration based on size (1, 3, or 6 months)
                $months = 0;
                $size = $report->appliance->appliance_size;
                if ($size === 'Small')
                    $months = 1;
                elseif ($size === 'Medium')
                    $months = 3;
                elseif ($size === 'Large')
                    $months = 6;

                if ($months > 0) {
                    $report->appliance->update([
                        'warranty_end' => now()->addMonths($months)
                    ]);
                }
            }
        }
    }

    public function index(Request $request)
    {
        $this->checkTransactionAccess();
        
        $search = $request->input('search');
        $date = $request->input('date');
        $status = $request->input('status');
        $receivedBy = $request->input('received_by');

        $transactions = \App\Models\Transaction::with('report')
            ->when($search, function ($q) use ($search) {
                $q->where(function ($query) use ($search) {
                    $query->where('id', 'ilike', "%$search%")
                          ->orWhereRaw('payment_date::text ilike ?', ["%$search%"])
                          ->orWhereRaw('payment_due::text ilike ?', ["%$search%"])
                          ->orWhereHas('report', function ($subQuery) use ($search) {
                              $subQuery->where('customer_name', 'ilike', "%$search%");
                          });
                });
            })
            ->when($date, function ($q) use ($date) {
                $q->where(function ($query) use ($date) {
                    $query->whereDate('payment_date', $date)
                          ->orWhereDate('payment_due', $date);
                });
            })
            ->when($status, function ($q) use ($status) {
                // The Flutter app writes 'Pending' for unpaid transactions.
                if ($status === 'Unpaid') {
                    $q->whereIn('payment_status', ['Unpaid', 'Pending']);
                } else {
                    $q->where('payment_status', $status);
                }
            })
            ->when($receivedBy, function ($q) use ($receivedBy) {
                if ($receivedBy === 'System') {
                    $q->where(function($query) {
                        $query->where('received_by', 'System')->orWhereNull('received_by');
                    });
                } elseif (in_array($receivedBy, ['Administrator', 'Secretary', 'Cashier'])) {
                    $userNames = \App\Models\User::where('role', $receivedBy)
                        ->get()
                        ->map(fn($user) => trim($user->first_name . ' ' . $user->last_name))
                        ->filter()
                        ->toArray();
                        
                    if (!empty($userNames)) {
                        $q->whereIn('received_by', $userNames);
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                } else {
                    $q->where('received_by', $receivedBy);
                }
            })
            ->latest()
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $paymentReviewAvailable = Schema::hasTable('customer_payment_submissions');
        $pendingSubmissions = collect();
        if ($paymentReviewAvailable && $transactions->count() > 0) {
            $pendingSubmissions = CustomerPaymentSubmission::query()
                ->whereIn('transaction_id', $transactions->getCollection()->pluck('id'))
                ->where('method', 'gcash')->where('status', 'pending')
                ->get()->keyBy('transaction_id');
        }
        return view('transactions.index', compact('transactions', 'search', 'date', 'status', 'receivedBy', 'paymentReviewAvailable', 'pendingSubmissions'));
    }

    public function create()
    {
        $this->checkTransactionAccess();
        $reports = \App\Models\ServiceReport::with(['customer', 'appliance', 'details'])
            ->where('status', 'Completed')
            ->whereDoesntHave('transactions', function ($query) {
            $query->where('payment_status', 'Paid');
        })
            ->latest()
            ->get();

        return view('transactions.create', compact('reports'));
    }

    public function store(Request $request)
    {
        $this->checkTransactionAccess();
        $validated = $request->validate([
            'report_id' => 'required|exists:service_reports,id',
            'labor' => 'required|numeric|min:300',
            'materials' => 'required|numeric|min:0',
            'delivery' => 'nullable|numeric|min:0',
            'payment_status' => 'required|string|in:Paid,Unpaid,Partial',
            'payment_method' => 'nullable|string',
            'partial_payment_amount' => 'required_if:payment_status,Partial|nullable|numeric|min:0',
            'reference_no' => 'nullable|string',
            'received_by' => 'nullable|string',
            'payment_date' => 'nullable|date',
            'payment_due' => 'nullable|date',
        ], [
            'labor.min' => 'Labor cost must be at least ₱300.',
        ]);

        $report = \App\Models\ServiceReport::find($validated['report_id']);
        if ($report->status !== 'Completed') {
            return back()->withInput()->with('error', 'Only Completed service reports can be paid.');
        }

        [$transaction, $amountChanged] = DB::transaction(function () use ($report, $validated) {
            $report = \App\Models\ServiceReport::whereKey($report->id)->lockForUpdate()->firstOrFail();
            if ($report->status !== 'Completed') {
                throw ValidationException::withMessages(['report_id' => 'Only Completed service reports can be paid.']);
            }

            $existing = $report->transactions()->withTrashed()->latest()->orderByDesc('id')->lockForUpdate()->get();
            if ($existing->contains('payment_status', 'Paid')) {
                throw ValidationException::withMessages(['report_id' => 'This service report has already been paid.']);
            }

            $transaction = $existing->first(fn ($bill) => !$bill->trashed()
                && in_array($bill->payment_status, ['Pending', 'Unpaid', 'Partial'], true))
                ?? new \App\Models\Transaction(['report_id' => $report->id]);
            $previousBalance = $transaction->exists
                ? (float) $transaction->total_amount - (float) $transaction->partial_payment_amount
                : null;
            $miscCost = $report->details?->miscellaneous_cost ?? 0;
            $totalAmount = $validated['labor'] + $validated['materials'] + ($validated['delivery'] ?? 0) + $miscCost;
            if ($validated['payment_status'] === 'Partial' && $validated['partial_payment_amount'] > $totalAmount) {
                throw ValidationException::withMessages(['partial_payment_amount' => 'The partial payment cannot exceed the bill total.']);
            }

            \App\Models\ServiceDetail::updateOrCreate(['report_id' => $report->id], [
                'labor' => $validated['labor'],
                'parts_total_charge' => $validated['materials'],
                'pullout_delivery' => $validated['delivery'] ?? 0,
                'total_amount' => $totalAmount,
            ]);

            $receivedPayment = in_array($validated['payment_status'], ['Paid', 'Partial'], true);
            $paymentDate = $receivedPayment ? ($validated['payment_date'] ?? now()) : null;
            $transaction->fill([
                'customer_id' => $report->customer_id,
                'parts_total' => $validated['materials'],
                'labor_total' => $validated['labor'],
                'total_amount' => $totalAmount,
                'payment_status' => $validated['payment_status'],
                'payment_method' => $validated['payment_method'] ?? null,
                'partial_payment_amount' => $validated['payment_status'] === 'Partial' ? $validated['partial_payment_amount'] : null,
                'reference_no' => $validated['reference_no'] ?? null,
                'payment_date' => $paymentDate,
                'paid_at' => $receivedPayment ? now() : null,
                'payment_due' => $validated['payment_due'] ?? null,
                'received_by' => $validated['received_by'] ?? auth()->user()->full_name,
            ]);
            $transaction->save();
            $newBalance = $totalAmount - (float) $transaction->partial_payment_amount;

            return [$transaction, $previousBalance !== null
                && (int) round($previousBalance * 100) !== (int) round($newBalance * 100)];
        });

        app(PayMongoPaymentService::class)->ensurePaymentLink($transaction, $amountChanged);

        $this->applyWarrantyIfPaid($transaction);

        return redirect()->route('transactions.index')->with('success', 'Transaction recorded successfully.');
    }

    public function paymongoWebhook(Request $request)
    {
        // The admin verifies online payments and records them on the existing bill.
        return response()->json(['status' => 'received']);
    }

    public function show(\App\Models\Transaction $transaction)
    {
        $this->checkTransactionAccess();
        $paymentReviewAvailable = Schema::hasTable('customer_payment_submissions');
        $pendingSubmission = $paymentReviewAvailable
            ? CustomerPaymentSubmission::query()->where('transaction_id', $transaction->id)
                ->where('method', 'gcash')->where('status', 'pending')->first()
            : null;
        return view('transactions.show', compact('transaction', 'paymentReviewAvailable', 'pendingSubmission'));
    }

    public function edit(\App\Models\Transaction $transaction)
    {
        $this->checkTransactionAccess();
        return view('transactions.edit', compact('transaction'));
    }

    public function update(Request $request, \App\Models\Transaction $transaction)
    {
        $this->checkTransactionAccess();
        $validated = $request->validate([
            'total_amount' => 'numeric|min:0',
            'payment_status' => ['string', \Illuminate\Validation\Rule::in(\App\Models\Transaction::STATUSES)],
            'payment_method' => 'nullable|string',
            'partial_payment_amount' => 'required_if:payment_status,Partial|nullable|numeric|min:0',
            'reference_no' => 'nullable|string',
            'received_by' => 'nullable|string',
            'payment_date' => 'nullable|date',
            'payment_due' => 'nullable|date',
        ]);

        [$transaction, $amountChanged] = DB::transaction(function () use ($transaction, $validated) {
            if ($transaction->report_id) {
                \App\Models\ServiceReport::withTrashed()->whereKey($transaction->report_id)->lockForUpdate()->first();
            }
            $transaction = \App\Models\Transaction::whereKey($transaction->id)->lockForUpdate()->firstOrFail();
            $paymongo = app(PayMongoPaymentService::class);
            $previousBalance = $paymongo->amountInCentavos($transaction);
            $status = $validated['payment_status'] ?? $transaction->payment_status;
            if ($status !== 'Partial') {
                $validated['partial_payment_amount'] = null;
            }
            if (in_array($status, ['Paid', 'Partial'], true)) {
                $validated['payment_date'] = $validated['payment_date'] ?? $transaction->payment_date ?? now();
                $validated['paid_at'] = $transaction->paid_at ?? now();
                $receivedBy = $validated['received_by'] ?? $transaction->received_by;
                if (!$receivedBy || $receivedBy === 'System') {
                    $validated['received_by'] = auth()->user()->full_name;
                }
            } else {
                $validated['payment_date'] = null;
                $validated['paid_at'] = null;
            }
            $transaction->fill($validated);
            if ($status === 'Partial' && $transaction->partial_payment_amount > $transaction->total_amount) {
                throw ValidationException::withMessages(['partial_payment_amount' => 'The partial payment cannot exceed the bill total.']);
            }
            $transaction->save();

            return [$transaction, $previousBalance !== $paymongo->amountInCentavos($transaction)];
        });

        app(PayMongoPaymentService::class)->ensurePaymentLink($transaction, $amountChanged);

        if (isset($validated['payment_status'])) {
            $this->applyWarrantyIfPaid($transaction);
        }

        return redirect()->route('transactions.index')->with('success', 'Transaction updated successfully.');
    }

    public function destroy(\App\Models\Transaction $transaction)
    {
        $this->checkTransactionAccess();
        // Bypass $fillable: deleted_by should not be user-input controlled.
        $transaction->forceFill(['deleted_by' => auth()->id()])->save();
        $transaction->delete();
        app(PayMongoPaymentService::class)->ensurePaymentLink($transaction);
        return redirect()->route('transactions.index')->with('success', 'Transaction deleted successfully.');
    }
}
