<?php

namespace App\Http\Controllers;

use App\Models\Manpower;
use App\Models\ManpowerDocument;
use App\Models\Partner;
use App\Services\FileAntivirusScanner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ManpowerController extends Controller
{
    public function __construct(private readonly FileAntivirusScanner $antivirus) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Manpower::class);
        $search = trim((string) $request->input('search'));
        $manpowers = Manpower::query()->visibleTo($request->user())->with(['partner', 'documents'])
            ->when($request->boolean('archived'), fn ($query) => $query->onlyTrashed())
            ->when($search, fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('nik', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('position', 'like', "%{$search}%")
                    ->orWhere('department', 'like', "%{$search}%");
            }))
            ->latest()->paginate(10)->withQueryString();

        return view('dashboard.manpowers.index', compact('manpowers', 'search'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Manpower::class);

        return view('dashboard.manpowers.create', ['partners' => $this->availablePartners($request)]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Manpower::class);
        $partner = $this->targetPartner($request);
        $validated = $this->validateData($request, $partner);

        if ($request->hasFile('photo_path')) {
            $this->antivirus->scan($request->file('photo_path'));
            $validated['photo_path'] = $request->file('photo_path')->store('manpowers/photos', 'local');
        }

        $manpower = DB::transaction(fn () => Manpower::create([
            ...$validated,
            'partner_id' => $partner->id,
            'owner_id' => $partner->isOwner() ? $partner->id : $partner->owner_id,
        ]));

        return redirect()->route('dashboard.manpowers.edit', $manpower)
            ->with('success', 'Tenaga kerja berhasil dibuat. Silakan lengkapi dokumen di bawah ini.');
    }

    public function show(Manpower $manpower): View
    {
        $this->authorize('view', $manpower);
        $manpower->load(['partner.owner', 'owner', 'allDocuments.uploader', 'allDocuments.verifier']);

        return view('dashboard.manpowers.show', [
            'manpower' => $manpower,
            'documentTypes' => ManpowerDocument::TYPES,
            'missingDocumentTypes' => $manpower->missingRequiredDocumentTypes(),
        ]);
    }

    public function edit(Request $request, Manpower $manpower): View
    {
        abort_if($manpower->trashed(), 409, 'Tenaga kerja yang sudah diarsipkan tidak dapat diubah.');
        $this->authorize('update', $manpower);
        $manpower->load(['allDocuments.uploader', 'allDocuments.verifier']);

        return view('dashboard.manpowers.edit', [
            'manpower' => $manpower,
            'partners' => $this->availablePartners($request),
            'documentTypes' => ManpowerDocument::TYPES,
        ]);
    }

    public function update(Request $request, Manpower $manpower): RedirectResponse
    {
        abort_if($manpower->trashed(), 409, 'Tenaga kerja yang sudah diarsipkan tidak dapat diubah.');
        $this->authorize('update', $manpower);
        $partner = $this->targetPartner($request);
        $validated = $this->validateData($request, $partner, $manpower);

        if ($request->hasFile('photo_path')) {
            $this->antivirus->scan($request->file('photo_path'));
            $validated['photo_path'] = $request->file('photo_path')->store('manpowers/photos', 'local');
        }

        DB::transaction(fn () => $manpower->update([
            ...$validated,
            'partner_id' => $partner->id,
            'owner_id' => $partner->isOwner() ? $partner->id : $partner->owner_id,
        ]));

        return redirect()->route('dashboard.manpowers.edit', $manpower)->with('success', 'Data tenaga kerja berhasil diperbarui.');
    }

    public function destroy(Manpower $manpower): RedirectResponse
    {
        $this->authorize('delete', $manpower);
        abort_if($manpower->trashed(), 409, 'Tenaga kerja sudah diarsipkan.');

        $manpower->update(['is_active' => false]);
        $manpower->delete();

        return redirect()->route('dashboard.manpowers.index')->with('success', 'Tenaga kerja diarsipkan. Seluruh histori dan file tetap disimpan.');
    }

    public function restore(Manpower $manpower): RedirectResponse
    {
        abort_unless($manpower->trashed(), 409);
        $this->authorize('update', $manpower);
        $manpower->restore();
        $manpower->update(['is_active' => true]);

        return redirect()->route('dashboard.manpowers.show', $manpower)->with('success', 'Tenaga kerja berhasil dipulihkan dari arsip.');
    }

    public function photo(Manpower $manpower): StreamedResponse
    {
        $this->authorize('viewDocument', $manpower);

        return $this->privateResponse($manpower->photo_path, 'foto-'.$manpower->id);
    }

    public function document(Manpower $manpower): StreamedResponse
    {
        $this->authorize('viewDocument', $manpower);

        return $this->privateResponse($manpower->document_path, 'dokumen-lama-'.$manpower->id.'.pdf');
    }

    private function validateData(Request $request, Partner $partner, ?Manpower $manpower = null): array
    {
        return $request->validate([
            'partner_id' => [$request->user()->isDeveloper() ? 'required' : 'exclude', 'integer'],
            'nik' => [
                'required', 'string', 'max:100',
                Rule::unique('manpowers')->where('owner_id', $partner->isOwner() ? $partner->id : $partner->owner_id)->ignore($manpower),
            ],
            'name' => ['required', 'string', 'max:255'],
            'position' => ['required', 'string', 'max:150'],
            'department' => ['required', 'string', 'max:150'],
            'birth_place' => ['required', 'string', 'max:150'],
            'birth_date' => ['required', 'date', 'before:today'],
            'contact_number' => ['required', 'string', 'max:20'],
            'emergency_contact_name' => ['required', 'string', 'max:255'],
            'emergency_contact_number' => ['required', 'string', 'max:20'],
            'blood_type' => ['required', Rule::in(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'])],
            'is_active' => ['required', 'boolean'],
            'photo_path' => [
                $manpower?->photo_path ? 'nullable' : 'required',
                'image', 'mimes:jpg,jpeg,png', 'max:5120', 'dimensions:min_width=300,min_height=400,ratio=3/4',
            ],
        ]);
    }

    private function targetPartner(Request $request): Partner
    {
        if ($request->user()->hasRole('safety_mitra')) {
            $partner = $request->user()->partner;
        } elseif ($request->user()->hasRole('hse_owner')) {
            $partner = $request->user()->partner;
        } else {
            $partner = Partner::find($request->input('partner_id'));
        }

        $valid = $partner && ($partner->isOwner() || ($partner->isPartner() && $partner->owner_id !== null));
        if (! $valid) {
            throw ValidationException::withMessages(['partner_id' => 'Organisasi tenaga kerja tidak valid.']);
        }

        return $partner;
    }

    private function availablePartners(Request $request)
    {
        return Partner::query()->where(function ($query) {
            $query->owners()->orWhere(fn ($query) => $query->partners()->whereNotNull('owner_id'));
        })->visibleTo($request->user())->orderBy('legal_name')->get();
    }

    private function privateResponse(?string $path, string $name): StreamedResponse
    {
        abort_unless($path, 404);
        $disk = Storage::disk('local')->exists($path) ? Storage::disk('local') : Storage::disk('public');
        abort_unless($disk->exists($path), 404);

        return $disk->response($path, $name, [
            'Content-Disposition' => 'inline',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
