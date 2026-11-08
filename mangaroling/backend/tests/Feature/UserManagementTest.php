<?php

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase, CreatesUsers;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpUsers();
        $this->admin = $this->makeUser(RoleName::SystemAdministrator);
        Sanctum::actingAs($this->admin);
    }

    private function payload(array $overrides = []): array
    {
        return $overrides + [
            'name' => 'Ninang Maria', 'email' => 'Maria@Example.com',
            'password' => self::PASSWORD, 'roles' => ['campaign_manager'],
        ];
    }

    public function test_admin_creates_user_with_temporary_password(): void
    {
        $this->postJson('/api/v1/users', $this->payload())
            ->assertCreated()
            ->assertJsonPath('data.email', 'maria@example.com')
            ->assertJsonPath('data.must_change_password', true)
            ->assertJsonPath('data.organization_id', $this->org->id)
            ->assertJsonPath('data.roles', ['campaign_manager'])
            ->assertJsonMissingPath('data.password');

        $user = User::where('email', 'maria@example.com')->firstOrFail();
        $this->assertNotSame(self::PASSWORD, $user->password);   // hashed
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.created', 'entity_id' => $user->id, 'user_id' => $this->admin->id]);
    }

    public function test_duplicate_email_is_rejected_case_insensitively(): void
    {
        $this->makeUser(RoleName::CampaignManager, ['email' => 'maria@example.com']);
        $this->postJson('/api/v1/users', $this->payload())->assertStatus(422)->assertJsonValidationErrors('email');
    }

    public function test_weak_password_and_unknown_role_are_rejected(): void
    {
        $this->postJson('/api/v1/users', $this->payload(['password' => 'weak']))->assertJsonValidationErrors('password');
        $this->postJson('/api/v1/users', $this->payload(['roles' => ['superuser']]))->assertJsonValidationErrors('roles.0');
        $this->postJson('/api/v1/users', $this->payload(['roles' => []]))->assertJsonValidationErrors('roles');
    }

    public function test_list_is_scoped_to_organization_and_searchable(): void
    {
        $this->makeUser(RoleName::CampaignManager, ['name' => 'Juan Dela Cruz']);
        $other = Organization::factory()->create();
        $this->makeUser(RoleName::CampaignManager, ['name' => 'Juan Outsider'], $other);

        $names = collect($this->getJson('/api/v1/users?q=juan')->assertOk()->json('data'))->pluck('name');
        $this->assertSame(['Juan Dela Cruz'], $names->all());
    }

    public function test_users_of_other_organizations_look_like_404(): void
    {
        $outsider = $this->makeUser(RoleName::CampaignManager, [], Organization::factory()->create());

        $this->getJson("/api/v1/users/{$outsider->id}")->assertNotFound();
        $this->patchJson("/api/v1/users/{$outsider->id}/active", ['is_active' => false])->assertNotFound();
        $this->putJson("/api/v1/users/{$outsider->id}/roles", ['roles' => ['finance_officer']])->assertNotFound();
        $this->assertTrue($outsider->fresh()->is_active);
    }

    public function test_update_name_and_email_is_audited(): void
    {
        $target = $this->makeUser(RoleName::CampaignManager);

        $this->putJson("/api/v1/users/{$target->id}", ['name' => 'Renamed'])->assertOk()->assertJsonPath('data.name', 'Renamed');

        $log = \App\Models\AuditLog::where('action', 'user.updated')->firstOrFail();
        $this->assertSame(['name'], $log->details['changed']);
    }

    public function test_deactivation_revokes_tokens_and_is_audited(): void
    {
        $target = $this->makeUser(RoleName::FinanceOfficer);
        $target->createToken('t');

        $this->patchJson("/api/v1/users/{$target->id}/active", ['is_active' => false])
            ->assertOk()->assertJsonPath('data.is_active', false);

        $this->assertSame(0, $target->tokens()->count());
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.deactivated', 'entity_id' => $target->id]);

        $this->patchJson("/api/v1/users/{$target->id}/active", ['is_active' => true])->assertOk();
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.activated', 'entity_id' => $target->id]);
    }

    public function test_admin_cannot_deactivate_self(): void
    {
        $this->patchJson("/api/v1/users/{$this->admin->id}/active", ['is_active' => false])
            ->assertStatus(422)->assertJsonValidationErrors('is_active');
        $this->assertTrue($this->admin->fresh()->is_active);
    }

    public function test_roles_can_be_changed_and_history_is_recorded(): void
    {
        $target = $this->makeUser(RoleName::CampaignManager);

        $this->putJson("/api/v1/users/{$target->id}/roles", ['roles' => ['finance_officer']])
            ->assertOk()->assertJsonPath('data.roles', ['finance_officer']);

        $log = \App\Models\AuditLog::where('action', 'user.roles_changed')->firstOrFail();
        $this->assertSame(['campaign_manager'], $log->details['before']);
        $this->assertSame(['finance_officer'], $log->details['after']);
    }

    public function test_admin_cannot_remove_own_admin_role(): void
    {
        $this->putJson("/api/v1/users/{$this->admin->id}/roles", ['roles' => ['campaign_manager']])
            ->assertStatus(422)->assertJsonValidationErrors('roles');
        $this->assertTrue($this->admin->fresh()->hasRole(RoleName::SystemAdministrator));
    }

    public function test_reset_password_forces_change_revokes_tokens_and_never_logs_the_password(): void
    {
        $target = $this->makeUser(RoleName::CampaignManager);
        $target->createToken('t');
        $temp = 'Temp0rary-Passw0rd!';

        $this->postJson("/api/v1/users/{$target->id}/reset-password", ['password' => $temp])
            ->assertOk()->assertJsonPath('data.must_change_password', true);

        $this->assertSame(0, $target->tokens()->count());
        $log = \App\Models\AuditLog::where('action', 'user.password_reset')->firstOrFail();
        $this->assertStringNotContainsString($temp, json_encode($log->details));
    }

    public function test_roles_endpoint_lists_roles_with_permissions(): void
    {
        $roles = collect($this->getJson('/api/v1/roles')->assertOk()->json('data'))->keyBy('name');
        $this->assertCount(3, $roles);
        $this->assertContains('donations.review', $roles['finance_officer']['permissions']);
    }

    public function test_audit_logs_can_be_listed_and_filtered(): void
    {
        $this->postJson('/api/v1/users', $this->payload())->assertCreated();

        $res = $this->getJson('/api/v1/audit-logs?action=user.')->assertOk();
        $this->assertSame('user.created', $res->json('data.0.action'));
        $this->assertSame($this->admin->email, $res->json('data.0.user.email'));
        $this->assertCount(0, $this->getJson('/api/v1/audit-logs?action=nonexistent')->json('data'));
    }
}
