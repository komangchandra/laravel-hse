<?php

namespace App\Http\Controllers;

use App\Models\Partner;
use App\Models\User;
use App\Services\UserAccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    private const TARGET_ROLES = ['developer', 'ktt', 'hse_owner', 'safety_mitra'];

    public function __construct(private readonly UserAccountService $accounts) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);
        $search = trim((string) $request->input('search'));
        $users = User::query()->visibleTo($request->user())->with(['roles', 'partner'])
            ->when($search, fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%");
            }))
            ->latest()->paginate(10)->withQueryString();

        return view('users.index', compact('users', 'search'));
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('users.create', $this->formOptions());
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);
        $validated = $this->validateUser($request, true);

        DB::transaction(function () use ($request, $validated) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'mobile' => $validated['mobile'] ?? null,
                'password' => Hash::make($validated['password']),
                'partner_id' => $validated['partner_id'] ?? null,
                'is_active' => (bool) $validated['is_active'],
                'deactivated_at' => $validated['is_active'] ? null : now(),
            ]);
            $user->syncRoles($validated['roles']);
            $this->accounts->audit($request->user(), $user, 'account.created', null, $this->accounts->snapshot($user));
        });

        return redirect()->route('users.index')->with('success', 'Akun berhasil dibuat dan dapat langsung digunakan jika statusnya aktif.');
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        return view('users.edit', ['user' => $user] + $this->formOptions());
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);
        $validated = $this->validateUser($request, false, $user);
        $newActive = (bool) $validated['is_active'];
        $newPartnerId = isset($validated['partner_id']) ? (int) $validated['partner_id'] : null;
        $newRoles = collect($validated['roles'])->sort()->values()->all();
        $this->accounts->ensureCriticalOwnerRoleRemains($user, $newRoles, $newPartnerId, $newActive);

        DB::transaction(function () use ($request, $validated, $user, $newActive, $newPartnerId, $newRoles) {
            $before = $this->accounts->snapshot($user);
            $passwordChanged = ! empty($validated['password']);

            $user->fill([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'mobile' => $validated['mobile'] ?? null,
                'partner_id' => $newPartnerId,
                'is_active' => $newActive,
                'deactivated_at' => $newActive ? null : ($user->deactivated_at ?? now()),
            ]);
            if ($passwordChanged) {
                $user->password = Hash::make($validated['password']);
            }
            $user->save();
            $user->syncRoles($newRoles);

            $after = $this->accounts->snapshot($user->fresh());
            $rolesChanged = $before['roles'] !== $after['roles'];
            $organizationChanged = $before['partner_id'] !== $after['partner_id'];
            $statusChanged = $before['is_active'] !== $after['is_active'];

            if ($rolesChanged || $organizationChanged || $statusChanged || $passwordChanged) {
                $this->accounts->revokeSessions($user);
                if ($user->is($request->user()) && $newActive) {
                    $request->session()->put('account_session_version', $user->session_version);
                }
            }

            if ($rolesChanged) {
                $this->accounts->audit($request->user(), $user, 'account.roles_changed', $before, $after);
            }
            if ($organizationChanged) {
                $this->accounts->audit($request->user(), $user, 'account.organization_changed', $before, $after);
            }
            if ($statusChanged) {
                $this->accounts->audit($request->user(), $user, $newActive ? 'account.activated' : 'account.deactivated', $before, $after);
            }
            if ($passwordChanged) {
                $this->accounts->audit($request->user(), $user, 'account.password_changed_by_developer', null, null);
            }
            if (! $rolesChanged && ! $organizationChanged && ! $statusChanged && ! $passwordChanged && $before !== $after) {
                $this->accounts->audit($request->user(), $user, 'account.profile_updated', $before, $after);
            }
        });

        return redirect()->route('users.index')->with('success', 'Akun berhasil diperbarui.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->authorize('delete', $user);
        abort_if($user->is($request->user()), 422, 'Akun yang sedang digunakan tidak dapat dihapus.');
        $this->accounts->ensureCriticalOwnerRoleRemains($user, [], null, false);

        DB::transaction(function () use ($request, $user) {
            $before = $this->accounts->snapshot($user);
            $this->accounts->revokeSessions($user);
            $this->accounts->audit($request->user(), $user, 'account.deleted', $before, null);
            $user->delete();
        });

        return redirect()->route('users.index')->with('success', 'Akun berhasil dihapus.');
    }

    private function formOptions(): array
    {
        return [
            'partners' => Partner::orderBy('organization_kind')->orderBy('legal_name')->get(),
            'roles' => Role::whereIn('name', self::TARGET_ROLES)->orderBy('name')->get(),
        ];
    }

    private function validateUser(Request $request, bool $creating, ?User $user = null): array
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user)],
            'mobile' => ['nullable', 'string', 'max:20'],
            'password' => [$creating ? 'required' : 'nullable', 'confirmed', Password::defaults()],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['required', 'distinct', Rule::in(self::TARGET_ROLES)],
            'partner_id' => ['nullable', 'integer', 'exists:partners,id'],
            'is_active' => ['required', 'boolean'],
        ]);

        $validator->after(function ($validator) use ($request, $user) {
            $roles = collect($request->input('roles', []));
            $partner = Partner::find($request->input('partner_id'));

            if ($roles->contains('developer')) {
                if ($roles->count() !== 1 || $partner !== null) {
                    $validator->errors()->add('roles', 'Developer harus menjadi role tunggal dan tidak terikat organisasi.');
                }
            } elseif (! $partner) {
                $validator->errors()->add('partner_id', 'Organisasi wajib dipilih untuk user non-developer.');
            } elseif ($roles->contains('safety_mitra')) {
                if ($roles->count() !== 1 || ! $partner->isPartner() || $partner->owner_id === null) {
                    $validator->errors()->add('roles', 'Safety Mitra hanya dapat ditempatkan pada mitra yang sudah memiliki owner.');
                }
            } elseif (! $partner->isOwner() || $roles->diff(['hse_owner', 'ktt'])->isNotEmpty()) {
                $validator->errors()->add('roles', 'Role HSE Owner dan KTT hanya dapat ditempatkan pada organisasi owner.');
            }

            if ($user?->is($request->user())) {
                $rolesChanged = $roles->sort()->values()->all() !== $user->getRoleNames()->sort()->values()->all();
                $organizationChanged = (int) ($request->input('partner_id') ?: 0) !== (int) ($user->partner_id ?: 0);
                if (! $request->boolean('is_active') || $rolesChanged || $organizationChanged) {
                    $validator->errors()->add('roles', 'Role, organisasi, dan status akun yang sedang digunakan tidak dapat diubah sendiri.');
                }
            }
        });

        return $validator->validate();
    }
}
