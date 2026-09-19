<?php

namespace Tests\Feature;

use App\Enums\PermitApplicationStatus as Status;
use App\Enums\PermitApplicationType as Type;
use App\Models\AuditLog;
use App\Models\Manpower;
use App\Models\Partner;
use App\Models\PartnerType;
use App\Models\PermitApplication;
use App\Models\User;
use App\Models\UserLog;
use App\Models\WorkflowNotification;
use App\Services\OperationalAuditService;
use App\Services\PermitApplicationWorkflow;
use App\Services\WorkflowNotificationService;
use Database\Seeders\PartnerTypeSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class DashboardNotificationAuditTest extends TestCase
{
    use RefreshDatabase;

    private Partner $ownerA;

    private Partner $ownerB;

    private Partner $partnerA;

    private Partner $partnerB;

    private User $safetyA;

    private User $safetyB;

    private User $hseA;

    private User $hseB;

    private User $kttA;

    private User $developer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, PartnerTypeSeeder::class]);
        $this->ownerA = $this->organization('Owner Alpha', 'OA', Partner::KIND_OWNER);
        $this->ownerB = $this->organization('Owner Beta', 'OB', Partner::KIND_OWNER);
        $this->partnerA = $this->organization('Mitra Alpha', 'MA', Partner::KIND_PARTNER, $this->ownerA);
        $this->partnerB = $this->organization('Mitra Beta', 'MB', Partner::KIND_PARTNER, $this->ownerB);
        $this->safetyA = $this->user('Safety Alpha', $this->partnerA, 'safety_mitra');
        $this->safetyB = $this->user('Safety Beta', $this->partnerB, 'safety_mitra');
        $this->hseA = $this->user('HSE Alpha', $this->ownerA, 'hse_owner');
        $this->hseB = $this->user('HSE Beta', $this->ownerB, 'hse_owner');
        $this->kttA = $this->user('KTT Alpha', $this->ownerA, 'ktt');
        $this->developer = User::factory()->create(['partner_id' => null]);
        $this->developer->assignRole('developer');
    }

    public function test_dashboard_metrics_are_scoped_by_partner_owner_and_developer_filter(): void
    {
        $this->application($this->partnerA, $this->ownerA, $this->safetyA, Status::HseRevisionRequired, 'A-REV');
        $this->application($this->partnerA, $this->ownerA, $this->safetyA, Status::HseReview, 'A-HSE');
        $this->application($this->partnerA, $this->ownerA, $this->safetyA, Status::KttReview, 'A-KTT');
        $this->application($this->partnerB, $this->ownerB, $this->safetyB, Status::HseReview, 'B-HSE');
        $this->application($this->partnerB, $this->ownerB, $this->safetyB, Status::KttReview, 'B-KTT');

        $safetyMetrics = $this->actingAs($this->safetyA)->get(route('dashboard'))->assertOk()->viewData('metrics');
        $this->assertSame(1, $this->metric($safetyMetrics, 'Perlu revisi'));
        $this->assertSame(2, $this->metric($safetyMetrics, 'Sedang diproses'));

        $hseMetrics = $this->actingAs($this->hseA)->get(route('dashboard'))->assertOk()->viewData('metrics');
        $this->assertSame(1, $this->metric($hseMetrics, 'Antrean review HSE'));
        $this->assertSame(1, $this->metric($hseMetrics, 'Siap/menunggu KTT'));

        $kttMetrics = $this->actingAs($this->kttA)->get(route('dashboard'))->assertOk()->viewData('metrics');
        $this->assertSame(1, $this->metric($kttMetrics, 'Antrean persetujuan'));

        $developerMetrics = $this->actingAs($this->developer)->get(route('dashboard', ['owner_id' => $this->ownerB->id]))
            ->assertOk()->viewData('metrics');
        $this->assertSame(2, $this->metric($developerMetrics, 'Total pengajuan'));
        $this->assertSame(1, $this->metric($developerMetrics, 'Antrean HSE'));
        $this->assertSame(1, $this->metric($developerMetrics, 'Antrean KTT'));
    }

    public function test_notification_is_deduplicated_sanitized_and_only_recipient_can_read_it(): void
    {
        $service = app(WorkflowNotificationService::class);
        $payload = [
            'application_id' => 12,
            'plain_token' => 'SECRET-TOKEN',
            'nested' => ['password' => 'Secret123!', 'safe' => 'ok'],
            'document_path' => 'private/identity.pdf',
        ];
        $recipients = collect([$this->safetyA]);

        $service->send($recipients, 'test:dedup:1', 'test', 'Judul', 'Pesan aman', '/dashboard', $payload);
        $service->send($recipients, 'test:dedup:1', 'test', 'Judul', 'Pesan aman', '/dashboard', $payload);

        $notification = WorkflowNotification::sole();
        $this->assertDatabaseCount('workflow_notifications', 1);
        $this->assertSame(12, $notification->data['application_id']);
        $this->assertSame('ok', $notification->data['nested']['safe']);
        $this->assertArrayNotHasKey('plain_token', $notification->data);
        $this->assertArrayNotHasKey('password', $notification->data['nested']);
        $this->assertArrayNotHasKey('document_path', $notification->data);

        $this->actingAs($this->safetyB)->post(route('dashboard.notifications.read', $notification))->assertForbidden();
        $this->actingAs($this->safetyA)->post(route('dashboard.notifications.read', $notification))->assertRedirect('/dashboard');
        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_workflow_audit_is_append_only_and_strips_sensitive_payload(): void
    {
        $manpower = $this->manpower($this->partnerA, $this->ownerA, 'NIK-AUDIT');
        $application = app(PermitApplicationWorkflow::class)->createDraft($this->safetyA, $manpower, Type::MinePermitOnly);
        $application = app(PermitApplicationWorkflow::class)->cancel($application, $this->safetyA, 'Tidak jadi diajukan');

        $workflowAudit = AuditLog::where('action', 'permit_application.cancel')->sole();
        $this->assertSame($this->safetyA->id, $workflowAudit->actor_id);
        $this->assertSame($this->partnerA->id, $workflowAudit->actor_organization_id);
        $this->assertSame($this->ownerA->id, $workflowAudit->organization_id);
        $this->assertSame(Status::Draft->value, $workflowAudit->from_status);
        $this->assertSame(Status::Cancelled->value, $workflowAudit->to_status);

        $audit = app(OperationalAuditService::class)->record(
            'security.test',
            $application,
            $this->safetyA,
            $this->ownerA->id,
            null,
            null,
            ['token' => 'SECRET', 'file_path' => 'private/test.pdf', 'safe' => 'kept', 'nested' => ['answer' => 'A', 'count' => 2]],
        );
        $this->assertSame(['safe' => 'kept', 'nested' => ['count' => 2]], $audit->metadata);

        $this->expectException(LogicException::class);
        $audit->update(['action' => 'tampered']);
    }

    public function test_analytics_json_aggregation_runs_on_test_database(): void
    {
        UserLog::create([
            'user_id' => (string) $this->developer->id,
            'name' => $this->developer->name,
            'email' => $this->developer->email,
            'action' => 'login',
            'meta' => ['device' => 'Desktop', 'ip' => '127.0.0.1'],
        ]);

        $this->actingAs($this->developer)->get(route('analytics.index'))
            ->assertOk()
            ->assertSee('Desktop')
            ->assertSee('127.0.0.1');
    }

    private function metric(array $metrics, string $label): int
    {
        return collect($metrics)->firstWhere('label', $label)['count'];
    }

    private function application(Partner $partner, Partner $owner, User $creator, Status $status, string $number): PermitApplication
    {
        $manpower = $this->manpower($partner, $owner, 'NIK-'.$number);

        return PermitApplication::create([
            'application_number' => $number,
            'type' => Type::MinePermitOnly,
            'owner_id' => $owner->id,
            'partner_id' => $partner->id,
            'manpower_id' => $manpower->id,
            'created_by' => $creator->id,
            'status' => $status,
            'version' => 1,
        ]);
    }

    private function manpower(Partner $partner, Partner $owner, string $nik): Manpower
    {
        return Manpower::create([
            'nik' => $nik,
            'name' => $nik,
            'contact_number' => '0812000000',
            'blood_type' => 'O',
            'partner_id' => $partner->id,
            'owner_id' => $owner->id,
            'position' => 'Operator',
            'department' => 'OPR',
            'is_active' => true,
        ]);
    }

    private function organization(string $name, string $shortName, string $kind, ?Partner $owner = null): Partner
    {
        return Partner::create([
            'owner_id' => $owner?->id,
            'partner_type_id' => $kind === Partner::KIND_PARTNER ? PartnerType::where('code', 'rental')->value('id') : null,
            'legal_name' => 'PT '.$name,
            'short_name' => $shortName,
            'permit_prefix' => $shortName,
            'email' => strtolower($shortName).'@example.test',
            'status' => 'active',
            'level' => $kind === Partner::KIND_OWNER ? 'owner' : 'contractor',
            'organization_kind' => $kind,
        ]);
    }

    private function user(string $name, Partner $partner, string $role): User
    {
        $user = User::factory()->create(['name' => $name, 'partner_id' => $partner->id]);
        $user->assignRole($role);

        return $user;
    }
}
