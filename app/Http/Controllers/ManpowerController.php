<?php

namespace App\Http\Controllers;

use App\Models\Manpower;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ManpowerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = trim($request->input('search'));
        $user = Auth::user();

        $manpowers = Manpower::query()

            // Search
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('nik', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
                });
            })

            // Role filter
            ->when(
                $user->hasRole(['contractor', 'rental']),
                function ($query) use ($user) {
                    $query->where('partner_id', $user->partner_id);
                }
            )

            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('dashboard.manpowers.index', compact('manpowers', 'search'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('dashboard.manpowers.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([

            'nik' => 'nullable|string|max:255|unique:manpowers,nik',

            'name' => 'required|string|max:255',

            'contact_number' => 'required|string|max:20',

            'blood_type' => 'required|string|max:3',

            'photo_path' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',

            'document_path' => 'nullable|file|mimes:pdf|max:5120',

        ]);

        DB::transaction(function () use ($request, $validatedData) {

            $user = Auth::user();

            // =====================
            // PARTNER
            // =====================
            $validatedData['partner_id'] = $user->partner_id;

            // =====================
            // UPLOAD PHOTO
            // =====================
            if ($request->hasFile('photo_path')) {

                $validatedData['photo_path'] = $request
                    ->file('photo_path')
                    ->store('manpowers/photos', 'public');

            }

            // =====================
            // UPLOAD DOCUMENT
            // =====================
            if ($request->hasFile('document_path')) {

                $validatedData['document_path'] = $request
                    ->file('document_path')
                    ->store('manpowers/documents', 'public');

            }

            // =====================
            // CREATE MANPOWER
            // =====================
            Manpower::create($validatedData);

        });

        return redirect()
            ->route('dashboard.manpowers.index')
            ->with('success', 'Manpower berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Manpower $manpower)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Manpower $manpower)
    {
        return view('dashboard.manpowers.edit', compact('manpower'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Manpower $manpower)
    {
        $validatedData = $request->validate([

            'nik' => 'nullable|string|max:255|unique:manpowers,nik,' . $manpower->id,

            'name' => 'required|string|max:255',

            'contact_number' => 'required|string|max:20',

            'blood_type' => 'required|string|max:3',

            'photo_path' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',

            'document_path' => 'nullable|file|mimes:pdf|max:5120',

        ]);

        DB::transaction(function () use ($request, $validatedData, $manpower) {

            // =====================
            // UPDATE PHOTO
            // =====================
            if ($request->hasFile('photo_path')) {

                // hapus file lama
                if ($manpower->photo_path && Storage::disk('public')->exists($manpower->photo_path)) {

                    Storage::disk('public')->delete($manpower->photo_path);

                }

                $validatedData['photo_path'] = $request
                    ->file('photo_path')
                    ->store('manpowers/photos', 'public');

            }

            // =====================
            // UPDATE DOCUMENT
            // =====================
            if ($request->hasFile('document_path')) {

                // hapus file lama
                if ($manpower->document_path && Storage::disk('public')->exists($manpower->document_path)) {

                    Storage::disk('public')->delete($manpower->document_path);

                }

                $validatedData['document_path'] = $request
                    ->file('document_path')
                    ->store('manpowers/documents', 'public');

            }

            // =====================
            // UPDATE MANPOWER
            // =====================
            $manpower->update($validatedData);

        });

        return redirect()
            ->route('dashboard.manpowers.index')
            ->with('success', 'Manpower berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Manpower $manpower)
    {
        DB::transaction(function () use ($manpower) {

            // =====================
            // DELETE PHOTO
            // =====================
            if ($manpower->photo_path &&
                Storage::disk('public')->exists($manpower->photo_path)) {

                Storage::disk('public')->delete($manpower->photo_path);

            }

            // =====================
            // DELETE DOCUMENT
            // =====================
            if ($manpower->document_path &&
                Storage::disk('public')->exists($manpower->document_path)) {

                Storage::disk('public')->delete($manpower->document_path);

            }

            // =====================
            // DELETE MANPOWER
            // =====================
            $manpower->delete();

        });

        return redirect()
            ->route('dashboard.manpowers.index')
            ->with('success', 'Manpower berhasil dihapus.');
    }
}
