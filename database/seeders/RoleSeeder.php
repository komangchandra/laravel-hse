<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    private const GUARD = 'web';

    private const PERMISSIONS = [
        'dashboard',
        'dashboard.view',
        'analytics',
        'analytics.view',
        'analytics.export',

        'user.view',
        'user.create',
        'user.update',
        'user.delete',

        'role.view',
        'role.create',
        'role.update',
        'role.delete',

        'permission.view',
        'permission.create',
        'permission.update',
        'permission.delete',

        'partner.view',
        'partner.create',
        'partner.update',
        'partner.delete',

        'manpower.view',
        'manpower.create',
        'manpower.update',
        'manpower.delete',

        'simper.view',
        'simper.create',
        'simper.update',
        'simper.delete',

        'simper-category.view',
        'simper-category.create',
        'simper-category.update',
        'simper-category.delete',

        'question-category.view',
        'question-category.create',
        'question-category.update',
        'question-category.delete',

        'question.view',
        'question.create',
        'question.update',
        'question.delete',

        'exam-session.view',
        'exam-session.create',
        'exam-session.update',
        'exam-session.delete',
        'exam-token.generate',
        'exam-result.view',
        'exam-result.pdf',

        'permit-application.view',
        'permit-application.create',
        'permit-application.update',
        'permit-application.submit',
        'permit-application.review-hse',
        'permit-application.configure-exam',
        'permit-application.submit-ktt',
        'permit-application.review-ktt',
        'permit-application.issue',

        'permit-card.view',
        'permit-card.download',
        'permit-card.reprint',
        'permit-card.revoke',

        'audit.view',
    ];

    private const SAFETY_MITRA_PERMISSIONS = [
        'dashboard',
        'dashboard.view',
        'manpower.view',
        'manpower.create',
        'manpower.update',
        'manpower.delete',
        'simper.view',
        'simper.create',
        'simper.update',
        'simper.delete',
        'permit-application.view',
        'permit-application.create',
        'permit-application.update',
        'permit-application.submit',
        'exam-result.view',
        'exam-result.pdf',
        'permit-card.view',
        'permit-card.download',
        'audit.view',
    ];

    private const HSE_OWNER_PERMISSIONS = [
        'dashboard',
        'dashboard.view',
        'user.view',
        'partner.view',
        'manpower.view',
        'manpower.create',
        'manpower.update',
        'manpower.delete',
        'simper.view',
        'simper-category.view',
        'simper-category.create',
        'simper-category.update',
        'simper-category.delete',
        'question-category.view',
        'question-category.create',
        'question-category.update',
        'question-category.delete',
        'question.view',
        'question.create',
        'question.update',
        'question.delete',
        'exam-session.view',
        'exam-session.create',
        'exam-session.update',
        'exam-token.generate',
        'exam-result.view',
        'exam-result.pdf',
        'permit-application.view',
        'permit-application.create',
        'permit-application.update',
        'permit-application.submit',
        'permit-application.review-hse',
        'permit-application.configure-exam',
        'permit-application.submit-ktt',
        'permit-card.view',
        'permit-card.download',
        'audit.view',
    ];

    private const KTT_PERMISSIONS = [
        'dashboard',
        'dashboard.view',
        'user.view',
        'partner.view',
        'manpower.view',
        'simper.view',
        'simper-category.view',
        'question-category.view',
        'question.view',
        'exam-session.view',
        'exam-result.view',
        'exam-result.pdf',
        'permit-application.view',
        'permit-application.review-ktt',
        'permit-application.issue',
        'permit-card.view',
        'permit-card.download',
        'permit-card.reprint',
        'permit-card.revoke',
        'audit.view',
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => self::GUARD,
            ]);
        }

        $rolePermissions = [
            'developer' => self::PERMISSIONS,
            'ktt' => self::KTT_PERMISSIONS,
            'hse_owner' => self::HSE_OWNER_PERMISSIONS,
            'safety_mitra' => self::SAFETY_MITRA_PERMISSIONS,
        ];

        foreach ($rolePermissions as $roleName => $permissions) {
            $role = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => self::GUARD,
            ]);

            $role->syncPermissions($permissions);
        }

        $this->migrateLegacyRoles();
        $this->normalizeDeveloperAccounts();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function migrateLegacyRoles(): void
    {
        foreach (Role::whereIn('name', ['super-admin', 'owner', 'contractor', 'rental', 'guest'])->get() as $legacyRole) {
            foreach ($legacyRole->users()->with('partner')->get() as $user) {
                $targetRoles = match (true) {
                    in_array($legacyRole->name, ['contractor', 'rental'], true) => ['safety_mitra'],
                    $legacyRole->name === 'owner' => ['hse_owner'],
                    $legacyRole->name === 'super-admin' && $user->partner?->isOwner() => ['hse_owner', 'ktt'],
                    $legacyRole->name === 'super-admin' && $user->partner?->isPartner() => ['safety_mitra'],
                    $legacyRole->name === 'super-admin' && $user->partner_id === null => ['developer'],
                    default => [],
                };

                if ($targetRoles !== []) {
                    $user->assignRole($targetRoles);
                }
                $user->removeRole($legacyRole);
            }

            $legacyRole->delete();
        }
    }

    private function normalizeDeveloperAccounts(): void
    {
        $developerRole = Role::where('name', 'developer')->first();
        if (! $developerRole) {
            return;
        }

        foreach ($developerRole->users as $user) {
            $user->forceFill(['partner_id' => null])->save();
            $user->syncRoles(['developer']);
        }
    }
}
