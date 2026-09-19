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
        $this->authorize('viewAny', SimperCategory::class);
        $search = $request->input('search');
        $simperCategories = SimperCategory::visibleTo($request->user())->when($search, function ($query, $search) {
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
        $this->authorize('create', SimperCategory::class);

        return view('dashboard.simper-categories.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', SimperCategory::class);
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
        ]);

        $validatedData['owner_id'] = $request->user()->isDeveloper() ? null : $request->user()->ownerOrganizationId();
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
        $this->authorize('update', $simperCategory);

        return view('dashboard.simper-categories.edit', ['simperCategory' => $simperCategory]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SimperCategory $simperCategory)
    {
        $this->authorize('update', $simperCategory);
        $validated = $request->validate(['name' => 'required|string|max:255', 'description' => 'required|string']);
        $simperCategory->update($validated);

        return redirect()
            ->route('dashboard.simper-categories.index')
            ->with('success', 'Kategori simper berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SimperCategory $simperCategory)
    {
        $this->authorize('delete', $simperCategory);
        $simperCategory->delete();

        return redirect()->route('dashboard.simper-categories.index')
            ->with('success', 'Kategori simper berhasil dihapus.');
    }
}
