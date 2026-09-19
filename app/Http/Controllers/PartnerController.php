<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePartnerRequest;
use App\Http\Requests\UpdatePartnerRequest;
use App\Models\Partner;
use App\Models\PartnerType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PartnerController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $this->authorize('viewAny', Partner::class);
        $query = Partner::query()->visibleTo($user)->with(['owner', 'partnerType']);

        $search = trim((string) $request->input('search'));
        $query->when($search, function (Builder $query, string $search) {
            $query->where(function (Builder $query) use ($search) {
                $query->where('legal_name', 'like', "%{$search}%")
                    ->orWhere('short_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        });

        $partners = $query->orderBy('organization_kind')->orderBy('legal_name')->paginate(10)->withQueryString();

        return view('partners.index', compact('partners', 'search'));
    }

    public function create(): View
    {
        $this->authorize('create', Partner::class);

        return view('partners.create', $this->formOptions());
    }

    public function store(StorePartnerRequest $request): RedirectResponse
    {
        $this->authorize('create', Partner::class);
        $data = $this->organizationData($request->validated());
        if ($request->hasFile('logo')) {
            $data['logo_path'] = $request->file('logo')->store('organizations/logos', 'public');
        }

        Partner::create($data);

        return redirect()->route('dashboard.partners.index')->with('success', 'Organisasi berhasil ditambahkan.');
    }

    public function edit(Partner $partner): View
    {
        $this->authorize('update', $partner);

        return view('partners.edit', ['partner' => $partner] + $this->formOptions());
    }

    public function update(UpdatePartnerRequest $request, Partner $partner): RedirectResponse
    {
        $this->authorize('update', $partner);
        $data = $this->organizationData($request->validated());
        $oldLogo = null;

        DB::transaction(function () use ($request, $partner, &$data, &$oldLogo) {
            if ($request->hasFile('logo')) {
                $oldLogo = $partner->logo_path;
                $data['logo_path'] = $request->file('logo')->store('organizations/logos', 'public');
            }
            $partner->update($data);
        });

        if ($oldLogo) {
            Storage::disk('public')->delete($oldLogo);
        }

        return redirect()->route('dashboard.partners.index')->with('success', 'Organisasi berhasil diperbarui.');
    }

    public function destroy(Partner $partner): RedirectResponse
    {
        $this->authorize('delete', $partner);
        if ($partner->childPartners()->exists()) {
            return back()->withErrors(['partner' => 'Owner tidak dapat dihapus karena masih memiliki mitra.']);
        }

        if ($partner->hasOperationalData()) {
            return back()->withErrors(['partner' => 'Organisasi tidak dapat dihapus karena sudah memiliki pengguna atau data operasional. Nonaktifkan organisasi sebagai gantinya.']);
        }

        $logoPath = $partner->logo_path;
        $partner->delete();
        if ($logoPath) {
            Storage::disk('public')->delete($logoPath);
        }

        return redirect()->route('dashboard.partners.index')->with('success', 'Organisasi berhasil dihapus.');
    }

    private function formOptions(): array
    {
        return [
            'owners' => Partner::owners()->orderBy('legal_name')->get(),
            'partnerTypes' => PartnerType::where('is_active', true)->orderBy('name')->get(),
        ];
    }

    private function organizationData(array $validated): array
    {
        unset($validated['logo']);
        $isOwner = $validated['organization_kind'] === Partner::KIND_OWNER;
        $validated['owner_id'] = $isOwner ? null : $validated['owner_id'];
        $validated['partner_type_id'] = $isOwner ? null : $validated['partner_type_id'];

        // Dipertahankan sementara untuk kompatibilitas kode lama; bukan lagi sumber tipe organisasi.
        $validated['level'] = $isOwner
            ? 'owner'
            : (PartnerType::find($validated['partner_type_id'])?->code === 'rental' ? 'rental' : 'contractor');

        return $validated;
    }
}
