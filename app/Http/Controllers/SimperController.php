<?php

namespace App\Http\Controllers;

use App\Models\Manpower;
use App\Models\Partner;
use App\Models\Simper;
use App\Models\SimperCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SimperController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Simper::class);

        return $this->listing($request);
    }

    public function pengajuan(Request $request): View
    {
        $this->authorize('viewAny', Simper::class);

        return $this->listing($request, 'pengajuan');
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Simper::class);

        return view('dashboard.simpers.create', $this->formOptions($request));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Simper::class);
        $validated = $this->validateData($request);
        [$partnerId, $manpower] = $this->resolveOwnership($request, $validated);
        $this->ensureCategoriesVisible($request, $validated['categories']);

        DB::transaction(function () use ($validated, $partnerId, $manpower) {
            $simper = Simper::create([
                'partner_id' => $partnerId,
                'manpower_id' => $manpower->id,
                'manpower_snapshot' => $manpower->snapshot(),
                'code' => $validated['code'],
                'status' => 'pengajuan',
            ]);
            $simper->categories()->sync($this->categorySyncData($validated['categories']));
        });

        return redirect()->route('dashboard.simpers.index')->with('success', 'Pengajuan SIMPER berhasil dibuat.');
    }

    public function show(Simper $simper): View
    {
        $this->authorize('view', $simper);
        $simper->load(['manpower', 'partner', 'categories', 'examTokens', 'examAttempts.examSession']);

        return view('dashboard.simpers.show', compact('simper'));
    }

    public function edit(Request $request, Simper $simper): View
    {
        $this->authorize('update', $simper);
        $simper->load('categories');

        return view('dashboard.simpers.edit', ['simper' => $simper] + $this->formOptions($request));
    }

    public function update(Request $request, Simper $simper): RedirectResponse
    {
        $this->authorize('update', $simper);
        $validated = $this->validateData($request, $simper);
        [$partnerId, $manpower] = $this->resolveOwnership($request, $validated);
        $this->ensureCategoriesVisible($request, $validated['categories']);

        DB::transaction(function () use ($validated, $partnerId, $manpower, $simper) {
            $snapshot = $simper->manpower_id !== $manpower->id || $simper->manpower_snapshot === null
                ? $manpower->snapshot()
                : $simper->manpower_snapshot;
            $simper->update([
                'partner_id' => $partnerId,
                'manpower_id' => $manpower->id,
                'manpower_snapshot' => $snapshot,
                'code' => $validated['code'],
            ]);
            $simper->categories()->sync($this->categorySyncData($validated['categories']));
        });

        return redirect()->route('dashboard.simpers.index')->with('success', 'Pengajuan SIMPER berhasil diperbarui.');
    }

    public function destroy(Simper $simper): RedirectResponse
    {
        $this->authorize('delete', $simper);
        if ($simper->examAttempts()->exists()) {
            return back()->withErrors(['simper' => 'Pengajuan yang sudah memiliki histori ujian tidak dapat dihapus.']);
        }

        DB::transaction(function () use ($simper) {
            $simper->categories()->detach();
            $simper->examTokens()->delete();
            $simper->delete();
        });

        return redirect()->route('dashboard.simpers.index')->with('success', 'Pengajuan SIMPER berhasil dihapus.');
    }

    private function listing(Request $request, ?string $status = null): View
    {
        $search = trim((string) $request->input('search'));
        $simpers = Simper::query()->visibleTo($request->user())->with(['partner', 'manpower', 'categories'])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($search, fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('code', 'like', "%{$search}%")
                    ->orWhereHas('manpower', fn ($manpower) => $manpower->where('name', 'like', "%{$search}%"));
            }))
            ->latest()->paginate(10)->withQueryString();

        return view('dashboard.simpers.index', compact('simpers'));
    }

    private function formOptions(Request $request): array
    {
        return [
            'partners' => Partner::partners()->whereNotNull('owner_id')->visibleTo($request->user())->orderBy('short_name')->get(),
            'manpowers' => Manpower::visibleTo($request->user())->orderBy('name')->get(),
            'categories' => SimperCategory::visibleTo($request->user())->orderBy('name')->get(),
        ];
    }

    private function validateData(Request $request, ?Simper $simper = null): array
    {
        return $request->validate([
            'partner_id' => [$request->user()->isDeveloper() ? 'required' : 'nullable', 'integer', Rule::exists('partners', 'id')->where('organization_kind', Partner::KIND_PARTNER)->whereNotNull('owner_id')],
            'manpower_id' => ['required', 'integer', 'exists:manpowers,id'],
            'code' => ['required', 'string', 'max:255', Rule::unique('simpers')->ignore($simper)],
            'categories' => ['required', 'array', 'min:1'],
            'categories.*.id' => ['required', 'integer', 'exists:simper_categories,id'],
            'categories.*.level' => ['required', Rule::in(['full', 'partial', 'T', 'latihan'])],
        ]);
    }

    private function resolveOwnership(Request $request, array $validated): array
    {
        $partnerId = $request->user()->isDeveloper() ? (int) $validated['partner_id'] : (int) $request->user()->partner_id;
        $manpower = Manpower::visibleTo($request->user())->find($validated['manpower_id']);
        if (! $manpower || $manpower->partner_id !== $partnerId) {
            throw ValidationException::withMessages(['manpower_id' => 'Manpower harus berasal dari organisasi mitra yang dipilih.']);
        }

        return [$partnerId, $manpower];
    }

    private function ensureCategoriesVisible(Request $request, array $categories): void
    {
        $ids = collect($categories)->pluck('id')->unique();
        if (SimperCategory::visibleTo($request->user())->whereIn('id', $ids)->count() !== $ids->count()) {
            throw ValidationException::withMessages(['categories' => 'Kategori SIMPER tidak tersedia untuk organisasi ini.']);
        }
    }

    private function categorySyncData(array $categories): array
    {
        return collect($categories)->mapWithKeys(fn ($category) => [
            $category['id'] => ['level' => $category['level']],
        ])->all();
    }
}
