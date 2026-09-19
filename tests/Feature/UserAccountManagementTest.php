<?php

namespace Tests\Feature;

use App\Models\Partner;
use App\Models\User;
use App\Models\UserLog;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UserAccountManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $developer;

    private Partner $owner;

    private Partner $partner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->owner = Partner::create([
            'legal_name' => 'Owner Utama',
            'short_name' => 'OWNER',
            'email' => 'owner@example.test',
            'status' => 'active',
            'level' => 'owner',
            'organization_kind' => Partner::KIND_OWNER,
        ]);
        $this->partner = Partner::create([
            'owner_id' => $this->owner->id,
            'legal_name' => 'Mitra Utama',
            'short_name' => 'MITRA',
            'email' => 'mitra@example.test',
            'status' => 'active',
            'level' => 'contractor',
            'organization_kind' => Partner::KIND_PARTNER,
        ]);
        $this->developer = User::factory()->create(['partner_id' => null]);
        $this->developer->assignRole('developer');
    }

    public function test_developer_can_create_an_active_account_that_can_login_immediately(): void
    {
        $this->actingAs($this->developer)->post(route('users.store'), $this->accountPayload())->assertRedirect(route('users.index'));

        $user = User::where('email', 'safety@example.test')->firstOrFail();
        $this->assertTrue($user->is_active);
        $this->assertTrue($user->hasRole('safety_mitra'));
        $this->assertSame($this->partner->id, $user->partner_id);
        $this->assertDatabaseHas('user_logs', ['action' => 'account.created', 'user_id' => (string) $this->developer->id]);

        $this->post(route('logout'));
        $this->post(route('login'), ['email' => $user->email, 'password' => 'Aa1!aaaa'])
            ->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($user);
    }

    public function test_account_creation_rejects_weak_password_and_invalid_role_organization_pairs(): void
    {
        $this->actingAs($this->developer)->post(route('users.store'), $this->accountPayload([
            'password' => 'Aa1!aaa',
            'password_confirmation' => 'Aa1!aaa',
        ]))->assertSessionHasErrors('password');

        $this->actingAs($this->developer)->post(route('users.store'), $this->accountPayload([
            'password_confirmation' => 'Different1!',
        ]))->assertSessionHasErrors('password');

        $this->actingAs($this->developer)->post(route('users.store'), $this->accountPayload([
            'email' => 'invalid@example.test',
            'roles' => ['safety_mitra'],
            'partner_id' => $this->owner->id,
        ]))->assertSessionHasErrors('roles');

        $this->actingAs($this->developer)->post(route('users.store'), $this->accountPayload([
            'email' => 'invalid-owner@example.test',
            'roles' => ['ktt'],
            'partner_id' => $this->partner->id,
        ]))->assertSessionHasErrors('roles');

        $this->assertDatabaseMissing('users', ['email' => 'invalid@example.test']);
    }

    public function test_dashboard_renders_user_log_meta_that_is_cast_to_an_array(): void
    {
        UserLog::create([
            'user_id' => $this->developer->id,
            'email' => $this->developer->email,
            'name' => $this->developer->name,
            'action' => 'login',
            'meta' => ['ip' => '127.0.0.1', 'user_agent' => 'Test Browser'],
        ]);

        $this->actingAs($this->developer)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Test Browser');
    }

    public function test_inactive_account_cannot_login_and_deactivation_revokes_existing_sessions(): void
    {
        $user = $this->organizationUser('Safety Lama', $this->partner, 'safety_mitra');
        DB::table('sessions')->insert([
            'id' => 'old-session',
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'test',
            'payload' => 'payload',
            'last_activity' => now()->timestamp,
        ]);

        $this->actingAs($this->developer)->put(route('users.update', $user), $this->accountPayload([
            'name' => $user->name,
            'email' => $user->email,
            'password' => null,
            'password_confirmation' => null,
            'is_active' => 0,
        ]))->assertRedirect(route('users.index'));

        $user->refresh();
        $this->assertFalse($user->is_active);
        $this->assertNotNull($user->deactivated_at);
        $this->assertSame(2, $user->session_version);
        $this->assertDatabaseMissing('sessions', ['id' => 'old-session']);
        $this->assertDatabaseHas('user_logs', ['action' => 'account.deactivated']);

        $this->post(route('logout'));
        $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->actingAs($this->developer)->put(route('users.update', $user), $this->accountPayload([
            'name' => $user->name,
            'email' => $user->email,
            'password' => null,
            'password_confirmation' => null,
            'is_active' => 1,
        ]))->assertRedirect(route('users.index'));
        $this->assertTrue($user->fresh()->is_active);
        $this->assertDatabaseHas('user_logs', ['action' => 'account.activated']);
    }

    public function test_role_and_organization_changes_are_audited_and_sessions_are_revoked(): void
    {
        $user = $this->organizationUser('HSE Lama', $this->owner, 'hse_owner');
        $replacement = $this->organizationUser('HSE Pengganti', $this->owner, 'hse_owner');

        $this->actingAs($this->developer)->put(route('users.update', $user), $this->accountPayload([
            'name' => $user->name,
            'email' => $user->email,
            'password' => null,
            'password_confirmation' => null,
            'roles' => ['safety_mitra'],
            'partner_id' => $this->partner->id,
        ]))->assertRedirect(route('users.index'));

        $this->assertTrue($user->fresh()->hasRole('safety_mitra'));
        $this->assertSame($this->partner->id, $user->fresh()->partner_id);
        $this->assertTrue(UserLog::where('action', 'account.roles_changed')->exists());
        $this->assertTrue(UserLog::where('action', 'account.organization_changed')->exists());
        $this->assertTrue($replacement->fresh()->hasRole('hse_owner'));
    }

    public function test_last_active_ktt_or_hse_owner_cannot_be_removed_without_a_replacement(): void
    {
        $hse = $this->organizationUser('HSE Tunggal', $this->owner, 'hse_owner');

        $this->actingAs($this->developer)->delete(route('users.destroy', $hse))->assertSessionHasErrors('roles');
        $this->assertNotNull($hse->fresh());

        $this->actingAs($this->developer)->put(route('users.update', $hse), $this->accountPayload([
            'name' => $hse->name,
            'email' => $hse->email,
            'password' => null,
            'password_confirmation' => null,
            'roles' => ['hse_owner'],
            'partner_id' => $this->owner->id,
            'is_active' => 0,
        ]))->assertSessionHasErrors('roles');

        $this->assertTrue($hse->fresh()->is_active);
        $this->organizationUser('HSE Pengganti', $this->owner, 'hse_owner');

        $this->actingAs($this->developer)->put(route('users.update', $hse), $this->accountPayload([
            'name' => $hse->name,
            'email' => $hse->email,
            'password' => null,
            'password_confirmation' => null,
            'roles' => ['hse_owner'],
            'partner_id' => $this->owner->id,
            'is_active' => 0,
        ]))->assertRedirect(route('users.index'));

        $this->assertFalse($hse->fresh()->is_active);
    }

    public function test_developer_cannot_disable_or_change_their_own_access(): void
    {
        $this->actingAs($this->developer)->put(route('users.update', $this->developer), $this->accountPayload([
            'name' => $this->developer->name,
            'email' => $this->developer->email,
            'password' => null,
            'password_confirmation' => null,
            'roles' => ['developer'],
            'partner_id' => null,
            'is_active' => 0,
        ]))->assertSessionHasErrors('roles');

        $this->assertTrue($this->developer->fresh()->is_active);
    }

    private function organizationUser(string $name, Partner $partner, string $role): User
    {
        $user = User::factory()->create(['name' => $name, 'partner_id' => $partner->id]);
        $user->assignRole($role);

        return $user;
    }

    private function accountPayload(array $overrides = []): array
    {
        return array_replace([
            'name' => 'Safety Baru',
            'email' => 'safety@example.test',
            'mobile' => '081234567890',
            'password' => 'Aa1!aaaa',
            'password_confirmation' => 'Aa1!aaaa',
            'roles' => ['safety_mitra'],
            'partner_id' => $this->partner->id,
            'is_active' => 1,
        ], $overrides);
    }
}
