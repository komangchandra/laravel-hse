<?php

namespace Tests\Feature;

use App\Models\AccessArea;
use App\Models\Partner;
use App\Models\PartnerType;
use App\Models\QuestionCategory;
use App\Models\SimperCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_is_idempotent_and_does_not_create_default_users(): void
    {
        $this->seed();
        $this->seed();

        $this->assertSame(15, Partner::count());
        $this->assertSame(8, PartnerType::count());
        $this->assertSame(2, Partner::owners()->count());
        $this->assertSame(13, Partner::partners()->count());
        $this->assertSame(6, QuestionCategory::count());
        $this->assertSame(5, SimperCategory::count());
        $this->assertSame(8, AccessArea::where('is_active', true)->count());
        $this->assertSame([
            'Area CCP & Hauling Road', 'Kantor & Mess', 'PIT', 'Workshop',
        ], AccessArea::where('owner_id', Partner::owners()->orderBy('id')->value('id'))->orderBy('name')->pluck('name')->all());
        $this->assertSame(4, Role::count());
        $this->assertFalse(Role::whereIn('name', ['super-admin', 'owner', 'contractor', 'rental', 'guest'])->exists());
        $this->assertSame(0, User::count());

        $developer = Role::findByName('developer');
        $safetyMitra = Role::findByName('safety_mitra');
        $hseOwner = Role::findByName('hse_owner');
        $ktt = Role::findByName('ktt');

        $this->assertSame(Permission::count(), $developer->permissions()->count());
        $this->assertTrue($safetyMitra->hasPermissionTo('permit-application.submit'));
        $this->assertFalse($safetyMitra->hasPermissionTo('permit-application.review-ktt'));
        $this->assertTrue($hseOwner->hasPermissionTo('permit-application.review-hse'));
        $this->assertTrue($hseOwner->hasPermissionTo('permit-application.submit-ktt'));
        $this->assertTrue($ktt->hasPermissionTo('permit-application.review-ktt'));
    }
}
