<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Membuat permision
        $permissions = [
            'user.create',
            'user.view',
            'user.update',
            'user.delete',
            'analytics',
            'dashboard',
            'role.create',
            'role.view',
            'role.update',
            'role.delete',
            'permission.create',
            'permission.view',
            'permission.update',
            'permission.delete',
        ];

        foreach ($permissions as $permission) {
            \Spatie\Permission\Models\Permission::firstOrCreate(['name' => $permission]);
        }

        // Membuat role
        Role::create(['name' => 'developer']);
        Role::create(['name' => 'super-admin']);
        Role::create(['name' => 'owner']);
        Role::create(['name' => 'contractor']);
        Role::create(['name' => 'rental']);
        Role::create(['name' => 'guest']);

        // // Assign all permissions to admin
        $developerRole = Role::where('name', 'developer')->first();
        $developerRole->givePermissionTo(Permission::all());

        $komang = User::create([
            'name' => 'Komang Chandra Winata',
            'email' => 'komangchandraaa1@gmail.com',
            'password' => Hash::make('Empire8855!'),
            'partner_id' => 1,
        ]);
        $komang->assignRole('developer');
    }
}
