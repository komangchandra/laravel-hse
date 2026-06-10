<?php

namespace App\Http\Controllers;

use App\Models\Partner;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class PartnerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');

        $partners = Partner::when($search, function ($query, $search) {
            return $query->where('legal_name', 'like', "%{$search}%")
                         ->orWhere('short_name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
        })
        ->latest()
        ->paginate(5)
        ->withQueryString();;

        return view('partners.index', compact('partners', 'search'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $roles = Role::whereNotIn('name', ['developer', 'super-admin', 'guest'])->get();
        return view('partners.create', compact('roles'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'legal_name' => 'required|string|max:255',
            'short_name' => 'required|string|max:255|unique:partners,short_name',
            'email' => 'required|email|max:255|unique:partners,email',
            'status' => 'required|string|in:Active,Inactive,Slowdown,Suspend',
            'level' => 'required|string|exists:roles,name',
        ]);

        Partner::create($validatedData);

        return redirect()->route('dashboard.partners.index')->with('success', 'Partner berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Partner $partner)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Partner $partner)
    {
        $roles = Role::whereNotIn('name', ['developer', 'super-admin', 'guest'])->get();
        return view('partners.edit', compact('partner', 'roles'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Partner $partner)
    {
        $validatedData = $request->validate([
            'legal_name' => 'required|string|max:255',
            'short_name' => 'required|string|max:255|unique:partners,short_name,' . $partner->id,
            'email' => 'required|email|max:255|unique:partners,email,' . $partner->id,
            'status' => 'required|string|in:Active,Inactive,Slowdown,Suspend',
            'level' => 'required|string|exists:roles,name',
        ]);

        $partner->update($validatedData);

        return redirect()->route('dashboard.partners.index')->with('success', 'Partner berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Partner $partner)
    {
        $partner->delete();
        return redirect()->route('dashboard.partners.index')->with('success', 'Partner berhasil dihapus.');
    }
}
