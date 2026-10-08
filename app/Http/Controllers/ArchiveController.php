<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ServiceReport;
use App\Models\Part;
use App\Models\Customer;
use App\Models\Transaction;
use App\Models\User;

class ArchiveController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->input('type', 'all');
        $search = $request->input('search');

        $archives = collect();

        if ($type === 'all' || $type === 'services') {
            $services = ServiceReport::onlyTrashed()
                ->when($search, function ($query) use ($search) {
                $query->where('customer_name', 'ilike', "%$search%")
                    ->orWhere('id', 'ilike', "%$search%");
            })
                ->get()
                ->map(function ($item) {
                $item->type = 'Service Report';
                $item->details = '#' . $item->id . ' - ' . $item->customer_name;
                return $item;
            });
            $archives = $archives->merge($services);
        }

        if ($type === 'all' || $type === 'inventory') {
            $parts = Part::onlyTrashed()
                ->when($search, function ($query) use ($search) {
                $query->where('name', 'ilike', "%$search%")
                    ->orWhere('part_no', 'ilike', "%$search%");
            })
                ->get()
                ->map(function ($item) {
                $item->type = 'Inventory Part';
                $item->details = $item->part_no . ' - ' . $item->name;
                return $item;
            });
            $archives = $archives->merge($parts);
        }

        if ($type === 'all' || $type === 'customers') {
            $customers = Customer::onlyTrashed()
                ->when($search, function ($query) use ($search) {
                $query->where('first_name', 'ilike', "%$search%")
                    ->orWhere('last_name', 'ilike', "%$search%");
            })
                ->get()
                ->map(function ($item) {
                $item->type = 'Customer';
                $item->details = $item->first_name . ' ' . $item->last_name . ' - ' . $item->phone_no;
                return $item;
            });
            $archives = $archives->merge($customers);
        }

        if ($type === 'all' || $type === 'users') {
            $users = User::onlyTrashed()
                ->when($search, function ($query) use ($search) {
                $query->where('first_name', 'ilike', "%$search%")
                    ->orWhere('username', 'ilike', "%$search%");
            })
                ->get()
                ->map(function ($item) {
                $item->type = 'User';
                $item->details = $item->full_name . ' (' . $item->username . ') - ' . $item->role;
                return $item;
            });
            $archives = $archives->merge($users);
        }

        if ($type === 'all' || $type === 'transactions') {
            $transactions = Transaction::onlyTrashed()
                ->when($search, function ($query) use ($search) {
                $query->where('id', 'ilike', "%$search%");
            })
                ->get()
                ->map(function ($item) {
                $item->type = 'Transaction';
                $item->details = 'Transaction #' . $item->id . ' — ₱' . number_format($item->total_amount, 2);
                return $item;
            });
            $archives = $archives->merge($transactions);
        }

        // Pagination (manual)
        $archives = $archives->sortBy([
            ['deleted_at', 'desc'],
            ['type', 'asc'],
            ['id', 'desc'],
        ])->map(function ($item) {
            // Resolve deleted_by name if it's an integer (user ID)
            if (isset($item->deleted_by) && is_numeric($item->deleted_by)) {
                $deleter = User::withTrashed()->find($item->deleted_by);
                $item->deleted_by_name = $deleter ? $deleter->full_name : 'Unknown';
            }
            else {
                $item->deleted_by_name = $item->deleted_by ?? 'Unknown';
            }
            return $item;
        });
        $perPage = 10;
        $page = $request->input('page', 1);
        $paginatedArchives = new \Illuminate\Pagination\LengthAwarePaginator(
            $archives->forPage($page, $perPage),
            $archives->count(),
            $perPage,
            $page,
        ['path' => $request->url(), 'query' => $request->query()]
            );

        return view('archive.index', compact('paginatedArchives', 'type', 'search'));
    }

    public function restore($type, $id)
    {
        switch ($type) {
            case 'Service Report':
                $item = ServiceReport::onlyTrashed()->find($id);
                break;
            case 'Inventory Part':
                $item = Part::onlyTrashed()->find($id);
                break;
            case 'Customer':
                $item = Customer::onlyTrashed()->find($id);
                break;
            case 'User':
                $item = User::onlyTrashed()->find($id);
                break;
            case 'Transaction':
                $item = Transaction::onlyTrashed()->find($id);
                break;
            default:
                return back()->with('error', 'Invalid type');
        }

        if ($item) {
            $item->restore();
            return back()->with('success', 'Record restored successfully.');
        }

        return back()->with('error', 'Record not found.');
    }

    public function destroy(Request $request, $type, $id)
    {
        switch ($type) {
            case 'Service Report':
                $item = ServiceReport::onlyTrashed()->find($id);
                break;
            case 'Inventory Part':
                $item = Part::onlyTrashed()->find($id);
                break;
            case 'Customer':
                $item = Customer::onlyTrashed()->find($id);
                break;
            case 'User':
                $item = User::onlyTrashed()->find($id);
                break;
            case 'Transaction':
                $item = Transaction::onlyTrashed()->find($id);
                break;
            default:
                return back()->with('error', 'Invalid type');
        }

        $request->validate([
            'password' => 'required|string',
        ]);

        if (!\Illuminate\Support\Facades\Hash::check($request->password, auth()->user()->password)) {
            return back()->with('error', 'Incorrect password. Deletion cancelled.');
        }

        if ($item) {
            $item->forceDelete();
            return back()->with('success', 'Record permanently deleted.');
        }

        return back()->with('error', 'Record not found.');
    }
}
