<?php

namespace App\Http\Controllers;

use App\Models\ServicePrice;
use Illuminate\Http\Request;

class ServicePriceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $prices = ServicePrice::latest()->orderByDesc('id')->get();
        return view('prices.index', compact('prices'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('prices.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'service_name' => 'required|string|max:255',
            'service_price' => 'required|numeric|min:0',
        ]);

        \App\Models\ServicePrice::create([
            'service_name' => $request->service_name,
            'service_price' => $request->service_price,
        ]);

        return redirect()->route('prices.index')->with('success', 'Service price added successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ServicePrice $price)
    {
        return view('prices.edit', compact('price'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ServicePrice $price)
    {
        $request->validate([
            'service_name' => 'required|string|max:255',
            'service_price' => 'required|numeric|min:0',
        ]);

        $price->update([
            'service_name' => $request->service_name,
            'service_price' => $request->service_price,
        ]);

        return redirect()->route('prices.index')->with('success', 'Service price updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ServicePrice $price)
    {
        $price->delete();
        return redirect()->route('prices.index')->with('success', 'Service price deleted successfully.');
    }
}
