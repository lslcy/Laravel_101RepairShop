<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

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
            $report = clone $transaction->report;
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
            ->paginate(25)
            ->withQueryString();

        return view('transactions.index', compact('transactions', 'search', 'date', 'status', 'receivedBy'));
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

        $totalAmount = $validated['labor'] + $validated['materials'] + ($validated['delivery'] ?? 0);

        // Update ServiceDetail
        \App\Models\ServiceDetail::updateOrCreate(
        ['report_id' => $report->id],
        [
            'labor' => $validated['labor'],
            'parts_total_charge' => $validated['materials'],
            'pullout_delivery' => $validated['delivery'] ?? 0,
            'total_amount' => $totalAmount,
        ]
        );

        $paymentDate = $validated['payment_date'] ?? null;
        if ($validated['payment_status'] === 'Paid') {
            $paymentDate = $paymentDate ? \Carbon\Carbon::parse($paymentDate) : now();
        }

        // Create transaction
        $transaction = \App\Models\Transaction::create([
            'report_id' => $report->id,
            'customer_id' => $report->customer_id,
            'parts_total' => $validated['materials'],
            'labor_total' => $validated['labor'],
            'total_amount' => $totalAmount,
            'payment_status' => $validated['payment_status'],
            'payment_method' => $validated['payment_method'] ?? null,
            'partial_payment_amount' => $validated['payment_status'] === 'Partial' ? $validated['partial_payment_amount'] : null,
            'reference_no' => $validated['reference_no'] ?? null,
            'payment_date' => $paymentDate,
            'paid_at' => $validated['payment_status'] === 'Paid' ? now() : null,
            'payment_due' => $validated['payment_due'] ?? null,
            'received_by' => $validated['received_by'] ?? (auth()->user() ? auth()->user()->first_name . ' ' . auth()->user()->last_name : 'System'),
        ]);

        // Create PayMongo Payment Link if total >= 100 and not Paid (requires secret key)
        $paymongoSecret = env('PAYMONGO_SECRET_KEY');
        if ($validated['payment_status'] !== 'Paid' && $totalAmount >= 100 && !empty($paymongoSecret)) {
            try {
                $response = Http::withBasicAuth($paymongoSecret, '')
                    ->withHeaders([
                    'accept' => 'application/json',
                    'content-type' => 'application/json',
                ])
                    ->post('https://api.paymongo.com/v1/links', [
                    'data' => [
                        'attributes' => [
                            'amount' => intval($totalAmount * 100),
                            'description' => 'Repair Service Payment for Report #' . $report->id,
                            'remarks' => 'Transaction #' . $transaction->id
                        ]
                    ]
                ]);

                if ($response->successful()) {
                    $paymongoData = $response->json()['data'];
                    $transaction->update([
                        'paymongo_link_id' => $paymongoData['id'],
                        'payment_url' => $paymongoData['attributes']['checkout_url']
                    ]);
                }
            }
            catch (\Exception $e) {
                // Log error or ignore to prevent breaking the flow
                \Log::error('PayMongo Link Creation Failed: ' . $e->getMessage());
            }
        }

        $this->applyWarrantyIfPaid($transaction);

        return redirect()->route('transactions.index')->with('success', 'Transaction recorded successfully.');
    }

    public function paymongoWebhook(Request $request)
    {
        $payload = $request->all();

        // Check if this is a payment paid event from PayMongo
        if (isset($payload['data']['type']) && $payload['data']['type'] === 'event' && $payload['data']['attributes']['type'] === 'link.payment.paid') {

            // Extract the link ID that was just paid 
            $linkId = $payload['data']['attributes']['data']['attributes']['link_id'] ?? null;

            if ($linkId) {
                // Find our transaction matching this exact PayMongo Link
                $transaction = \App\Models\Transaction::where('paymongo_link_id', $linkId)->first();

                if ($transaction && $transaction->payment_status !== 'Paid') {
                    $transaction->update([
                        'payment_status' => 'Paid',
                        'payment_date' => now(),
                        'paid_at' => now(),
                        // Could record 'amount' from payload if needed, but we trust the link generated.
                    ]);

                    // Trigger the warranty logic if applicable
                    $this->applyWarrantyIfPaid($transaction);

                    \Log::info("Webhook Success: Transaction #{$transaction->id} automatically marked as Paid.");
                }
            }
        }

        // Always return 200 OK so PayMongo knows we received it
        return response()->json(['status' => 'success']);
    }

    public function show(\App\Models\Transaction $transaction)
    {
        $this->checkTransactionAccess();
        return view('transactions.show', compact('transaction'));
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
            'total_amount' => 'numeric',
            'payment_status' => ['string', \Illuminate\Validation\Rule::in(\App\Models\Transaction::STATUSES)],
            'payment_method' => 'nullable|string',
            'partial_payment_amount' => 'required_if:payment_status,Partial|nullable|numeric|min:0',
            'reference_no' => 'nullable|string',
            'received_by' => 'nullable|string',
            'payment_date' => 'nullable|date',
            'payment_due' => 'nullable|date',
        ]);

        if (isset($validated['payment_status'])) {
            if ($validated['payment_status'] !== 'Partial') {
                $validated['partial_payment_amount'] = null; // Reset partial amount if not partial
            }
            if ($validated['payment_status'] === 'Paid' && empty($validated['payment_date']) && !$transaction->payment_date) {
                $validated['payment_date'] = now();
            }
            if ($validated['payment_status'] === 'Paid' && !$transaction->paid_at) {
                $validated['paid_at'] = now();
            }
        }

        $transaction->update($validated);

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
        return redirect()->route('transactions.index')->with('success', 'Transaction deleted successfully.');
    }
}
