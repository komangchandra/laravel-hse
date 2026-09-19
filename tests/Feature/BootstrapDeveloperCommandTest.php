<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BootstrapDeveloperCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_developer_account_can_be_bootstrapped_without_a_default_password(): void
    {
        Role::create(['name' => 'developer', 'guard_name' => 'web']);

        $this->artisan('app:bootstrap-developer', [
            'email' => 'developer@example.com',
            '--name' => 'Test Developer',
        ])
            ->expectsQuestion('Password', 'SecurePassword123!')
            ->expectsQuestion('Confirm password', 'SecurePassword123!')
            ->expectsOutput('Akun developer berhasil dibuat atau diperbarui.')
            ->assertSuccessful();

        $user = User::where('email', 'developer@example.com')->firstOrFail();

        $this->assertSame('Test Developer', $user->name);
        $this->assertNull($user->partner_id);
        $this->assertTrue($user->is_active);
        $this->assertTrue($user->hasRole('developer'));
    }

    public function test_developer_account_can_use_a_generated_password_without_prompts(): void
    {
        Role::create(['name' => 'developer', 'guard_name' => 'web']);

        $this->artisan('app:bootstrap-developer', [
            'email' => 'generated@example.com',
            '--name' => 'Generated Developer',
            '--generate-password' => true,
        ])
            ->expectsOutput('Akun developer berhasil dibuat atau diperbarui.')
            ->expectsOutputToContain('Password baru: ')
            ->expectsOutput('Simpan password tersebut sekarang. Password hanya ditampilkan satu kali.')
            ->assertSuccessful();

        $user = User::where('email', 'generated@example.com')->firstOrFail();

        $this->assertTrue($user->hasRole('developer'));
    }
}
