<?php

namespace App\Http\Controllers;

use App\Models\SimperCategory;
use Illuminate\Http\Request;

class SimperCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $simperCategories = SimperCategory::when($search, function ($query, $search){
            return $query->where('name', 'like', "%{$search}%");
        })
        ->latest()
        ->paginate(10)
        ->withQueryString();

        return view('dashboard.simper-categories.index', compact('simperCategories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('dashboard.simper-categories.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
        ]);

        SimperCategory::create($validatedData);

        return redirect()->route('dashboard.simper-categories.index')
                         ->with('success', 'Kategori Simper berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(SimperCategory $simperCategory)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SimperCategory $simperCategory) 
    {
        return view('dashboard.simper-categories.edit', [ 'simperCategory' => $simperCategory, ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SimperCategory $simperCategory)
    {
        $validated = $request->validate([ 'name' => 'required|string|max:255', 'description' => 'required|string', ]); $simperCategory->update($validated); return redirect()
        ->route('dashboard.simper-categories.index')
        ->with('success', 'Kategori simper berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SimperCategory $simperCategory)
    {
        $simperCategory->delete();
        return redirect()->route('dashboard.simper-categories.index')
            ->with('success', 'Kategori simper berhasil dihapus.');
    }
}
