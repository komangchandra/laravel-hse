<?php

namespace Tests\Feature;

use App\Models\Manpower;
use App\Models\Partner;
use App\Models\PartnerType;
use App\Models\User;
use Database\Seeders\PartnerTypeSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartnerOrganizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, PartnerTypeSeeder::class]);
    }

    public function test_developer_can_create_owner_and_partner_with_valid_relationship(): void
    {
        $developer = User::factory()->create();
        $developer->assignRole('developer');

        $this->actingAs($developer)->get(route('dashboard.partners.create'))->assertOk();

        $this->actingAs($developer)->post(route('dashboard.partners.store'), $this->ownerPayload())
            ->assertRedirect(route('dashboard.partners.index'));

        $owner = Partner::where('short_name', 'OWN-A')->firstOrFail();
        $type = PartnerType::where('code', 'mining_contractor')->firstOrFail();

        $this->actingAs($developer)->post(route('dashboard.partners.store'), $this->partnerPayload($owner, $type))
            ->assertRedirect(route('dashboard.partners.index'));

        $partner = Partner::where('short_name', 'MIT-A')->firstOrFail();
        $this->actingAs($developer)->get(route('dashboard.partners.edit', $partner))->assertOk();
        $this->assertTrue($owner->childPartners->contains($partner));
        $this->assertTrue($partner->owner->is($owner));
        $this->assertTrue($partner->partnerType->is($type));
    }

    public function test_partner_cannot_use_another_partner_as_owner_or_point_to_itself(): void
    {
        $developer = User::factory()->create();
        $developer->assignRole('developer');
        $owner = $this->createOwner('OWN-A');
        $existingPartner = $this->createPartner($owner, 'MIT-A');
        $type = PartnerType::where('code', 'rental')->firstOrFail();

        $payload = $this->partnerPayload($owner, $type);
        $payload['owner_id'] = $existingPartner->id;

        $this->actingAs($developer)->post(route('dashboard.partners.store'), $payload)
            ->assertSessionHasErrors('owner_id');

        $update = $this->partnerPayload($owner, $type) + [];
        $update['legal_name'] = $existingPartner->legal_name;
        $update['short_name'] = $existingPartner->short_name;
        $update['email'] = $existingPartner->email;
        $update['owner_id'] = $existingPartner->id;

        $this->actingAs($developer)->put(route('dashboard.partners.update', $existingPartner), $update)
            ->assertSessionHasErrors('owner_id');
    }

    public function test_owner_scope_and_owner_user_listing_exclude_other_owner_partners(): void
    {
        $ownerA = $this->createOwner('OWN-A');
        $ownerB = $this->createOwner('OWN-B');
        $partnerA = $this->createPartner($ownerA, 'MIT-A');
        $partnerB = $this->createPartner($ownerB, 'MIT-B');

        $this->assertEqualsCanonicalizing(
            [$ownerA->id, $partnerA->id],
            Partner::forOwner($ownerA)->pluck('id')->all(),
        );

        $hse = User::factory()->create(['partner_id' => $ownerA->id]);
        $hse->assignRole('hse_owner');

        $this->actingAs($hse)->get(route('dashboard.partners.index'))
            ->assertOk()
            ->assertSee('MIT-A')
            ->assertDontSee('MIT-B');

        $this->actingAs($hse)->get(route('dashboard.partners.create'))->assertForbidden();
    }

    public function test_organization_with_children_or_operational_history_cannot_be_deleted(): void
    {
        $developer = User::factory()->create();
        $developer->assignRole('developer');
        $owner = $this->createOwner('OWN-A');
        $partner = $this->createPartner($owner, 'MIT-A');

        $this->actingAs($developer)->delete(route('dashboard.partners.destroy', $owner))
            ->assertSessionHasErrors('partner');
        $this->assertDatabaseHas('partners', ['id' => $owner->id]);

        $manpower = Manpower::create([
            'nik' => 'MP-001',
            'name' => 'Pekerja Uji',
            'contact_number' => '08123456789',
            'blood_type' => 'O',
            'partner_id' => $partner->id,
        ]);

        $this->actingAs($developer)->delete(route('dashboard.partners.destroy', $partner))
            ->assertSessionHasErrors('partner');
        $this->assertDatabaseHas('partners', ['id' => $partner->id]);
        $this->assertDatabaseHas('manpowers', ['id' => $manpower->id, 'partner_id' => $partner->id]);

        $databaseRejectedDelete = false;
        try {
            $partner->delete();
        } catch (QueryException) {
            $databaseRejectedDelete = true;
        }

        $this->assertTrue($databaseRejectedDelete);
        $this->assertDatabaseHas('manpowers', ['id' => $manpower->id, 'partner_id' => $partner->id]);
    }

    private function createOwner(string $shortName): Partner
    {
        return Partner::create([
            'legal_name' => "PT {$shortName}",
            'short_name' => $shortName,
            'email' => strtolower($shortName).'@example.test',
            'status' => 'active',
            'level' => 'owner',
            'organization_kind' => Partner::KIND_OWNER,
        ]);
    }

    private function createPartner(Partner $owner, string $shortName): Partner
    {
        return Partner::create([
            'owner_id' => $owner->id,
            'partner_type_id' => PartnerType::where('code', 'rental')->value('id'),
            'organization_kind' => Partner::KIND_PARTNER,
            'legal_name' => "PT {$shortName}",
            'short_name' => $shortName,
            'email' => strtolower($shortName).'@example.test',
            'status' => 'active',
            'level' => 'rental',
        ]);
    }

    private function ownerPayload(): array
    {
        return [
            'legal_name' => 'PT Owner A',
            'short_name' => 'OWN-A',
            'email' => 'owner-a@example.test',
            'organization_kind' => Partner::KIND_OWNER,
            'status' => 'active',
            'permit_prefix' => 'OWNA',
        ];
    }

    private function partnerPayload(Partner $owner, PartnerType $type): array
    {
        return [
            'legal_name' => 'PT Mitra A',
            'short_name' => 'MIT-A',
            'email' => 'mitra-a@example.test',
            'organization_kind' => Partner::KIND_PARTNER,
            'owner_id' => $owner->id,
            'partner_type_id' => $type->id,
            'status' => 'active',
        ];
    }
}
