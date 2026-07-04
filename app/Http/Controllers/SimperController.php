<?php

namespace App\Http\Controllers;

use App\Models\Manpower;
use App\Models\Partner;
use App\Models\Simper;
use App\Models\SimperCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SimperController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->search;

        $simpers = Simper::with([
                'partner',
                'manpower',
                'categories',
            ])
            ->when($search, function ($query) use ($search) {

                $query->where('code', 'like', "%{$search}%")
                    ->orWhereHas('manpower', function ($q) use ($search) {

                        $q->where('name', 'like', "%{$search}%");

                    });

            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('dashboard.simpers.index', [
            'simpers' => $simpers,
        ]);
    }

    public function pengajuan(Request $request)
    {
        $search = $request->search;

        $simpers = Simper::with([
                'partner',
                'manpower',
                'categories',
            ])
            ->where('status', 'pengajuan')
            ->when($search, function ($query) use ($search) {

                $query->where(function ($q) use ($search) {
                    $q->where('code', 'like', "%{$search}%")
                    ->orWhereHas('manpower', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%");
                    });
                });

            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        // dd($simpers);

        return view('dashboard.simpers.index', [
            'simpers' => $simpers,
        ]);
    }
    
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('dashboard.simpers.create', [ 
            'partners' => Partner::orderBy('short_name')->get(), 
            'manpowers' => Manpower::orderBy('name')->get(), 
            'categories' => SimperCategory::orderBy('name')->get(), 
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'partner_id' => 'required|exists:partners,id',
            'manpower_id' => 'required|exists:manpowers,id',
            'code' => 'required|string|max:255|unique:simpers,code',

            'categories' => 'required|array|min:1',

            'categories.*.id' => 'required|exists:simper_categories,id',

            'categories.*.level' => [
                'required',
                'in:full,partial,T,latihan',
            ],
        ]);

        DB::transaction(function () use ($validated) {

            $simper = Simper::create([
                'partner_id' => $validated['partner_id'],
                'manpower_id' => $validated['manpower_id'],
                'code' => $validated['code'],
                'status' => 'Pengajuan',
            ]);

            $syncData = [];

            foreach ($validated['categories'] as $category) {

                $syncData[$category['id']] = [
                    'level' => $category['level'],
                ];

            }

            $simper->categories()->sync($syncData);

        });

        return redirect()
            ->route('dashboard.simpers.index')
            ->with('success', 'Simper berhasil dibuat.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Simper $simper)
    {
        $simper->load([
            'manpower',
            'partner',
            'categories',
            'examTokens',
            'examAttempts.examSession',
        ]);

        return view(
            'dashboard.simpers.show',
            compact('simper')
        );
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Simper $simper)
    {
        $simper->load('categories');

        return view('dashboard.simpers.edit', [
            'simper' => $simper,
            'partners' => Partner::orderBy('short_name')->get(),
            'manpowers' => Manpower::orderBy('name')->get(),
            'categories' => SimperCategory::orderBy('name')->get(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Simper $simper)
    {
        $validated = $request->validate([
            'partner_id' => 'required|exists:partners,id',

            'manpower_id' => 'required|exists:manpowers,id',

            'code' => 'required|string|max:255|unique:simpers,code,' . $simper->id,

            'categories' => 'required|array|min:1',

            'categories.*.id' => 'required|exists:simper_categories,id',

            'categories.*.level' => [
                'required',
                'in:full,partial,T,latihan',
            ],
        ]);

        DB::transaction(function () use ($validated, $simper) {

            $simper->update([
                'partner_id' => $validated['partner_id'],
                'manpower_id' => $validated['manpower_id'],
                'code' => $validated['code'],
            ]);

            $syncData = [];

            foreach ($validated['categories'] as $category) {

                $syncData[$category['id']] = [
                    'level' => $category['level'],
                ];

            }

            $simper->categories()->sync($syncData);

        });

        return redirect()
            ->route('dashboard.simpers.index')
            ->with('success', 'Simper berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Simper $simper)
    {
        DB::transaction(function () use ($simper) {

            // hapus relasi pivot
            $simper->categories()->detach();

            // hapus simper
            $simper->delete();

        });

        return redirect()
            ->route('dashboard.simpers.index')
            ->with('success', 'Simper berhasil dihapus.');
    }


}
