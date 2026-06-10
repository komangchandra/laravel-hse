<?php

namespace Database\Seeders;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
     public function run(): void
    {
        $this->call([
            PartnerSeeder::class,
            RoleSeeder::class,
            QuestionCategorySeeder::class,
            SimperCategorySeeder::class,
        ]);
        // Reset cached roles & permissions
        // app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // // Assign all permissions to admin
        // $adminRole->givePermissionTo(Permission::all());

        // // Assign read-only permission to user
        // $userRole->givePermissionTo(['user.view']);

        // /*
        // |--------------------------------------------------------------------------
        // | Users
        // |--------------------------------------------------------------------------
        // */

        // // Admin User
        // $admin = User::firstOrCreate(
        //     ['email' => 'komangchandraaa1@gmail.com'],
        //     [
        //         'name' => 'Admin - Komang Chandra',
        //         'password' => Hash::make('Empire8855!'),
        //     ]
        // );
        // $admin->assignRole($adminRole);

        // // Normal User
        // $user = User::firstOrCreate(
        //     ['email' => 'user@example.com'],
        //     [
        //         'name' => 'Normal User',
        //         'password' => Hash::make('User@123'),
        //     ]
        // );
        // $user->assignRole($userRole);
    }
}
