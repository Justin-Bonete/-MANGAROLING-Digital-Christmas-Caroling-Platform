<?php

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase, CreatesUsers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpUsers();
    }

    public function test_login_succeeds_and_email_is_case_insensitive(): void
    {
        $user = $this->makeUser(RoleName::CampaignManager, ['email' => 'maria@example.com']);

        $this->withHeaders($this->spa())
            ->postJson('/api/v1/auth/login', ['email' => 'Maria@Example.COM', 'password' => self::PASSWORD])
            ->assertOk()
            ->assertJsonPath('data.email', 'maria@example.com')
            ->assertJsonPath('data.roles.0', 'campaign_manager')
            ->assertJsonMissingPath('data.password');

        $this->assertAuthenticatedAs($user, 'web');
        $this->assertDatabaseHas('audit_logs', ['action' => 'auth.login', 'user_id' => $user->id]);
        $this->assertNotNull($user->fresh()->last_login_at);
    }

    public function test_wrong_password_is_rejected_and_audited_without_user(): void
    {
        $this->makeUser(RoleName::CampaignManager, ['email' => 'maria@example.com']);

        $this->withHeaders($this->spa())
            ->postJson('/api/v1/auth/login', ['email' => 'maria@example.com', 'password' => 'wrong-password'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');

        $this->assertGuest('web');
        $this->assertDatabaseHas('audit_logs', ['action' => 'auth.login_failed', 'user_id' => null]);
    }

    public function test_inactive_user_cannot_log_in_and_gets_the_same_generic_error(): void
    {
        $this->makeUser(RoleName::CampaignManager, ['email' => 'off@example.com', 'is_active' => false]);

        $inactive = $this->withHeaders($this->spa())
            ->postJson('/api/v1/auth/login', ['email' => 'off@example.com', 'password' => self::PASSWORD]);
        $unknown = $this->withHeaders($this->spa())
            ->postJson('/api/v1/auth/login', ['email' => 'nobody@example.com', 'password' => self::PASSWORD]);

        $inactive->assertStatus(422);
        $this->assertSame($unknown->json('errors.email'), $inactive->json('errors.email'));
        $this->assertSame('inactive', AuditLog::where('action', 'auth.login_failed')->orderBy('id')->first()->details['reason']);
    }

    public function test_login_is_rate_limited_after_five_attempts(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->withHeaders($this->spa())
                ->postJson('/api/v1/auth/login', ['email' => 'x@example.com', 'password' => 'bad-password'])
                ->assertStatus(422);
        }

        $this->withHeaders($this->spa())
            ->postJson('/api/v1/auth/login', ['email' => 'x@example.com', 'password' => 'bad-password'])
            ->assertStatus(429);
    }

    public function test_login_requires_a_stateful_spa_request(): void
    {
        $this->makeUser(RoleName::CampaignManager, ['email' => 'maria@example.com']);

        $this->postJson('/api/v1/auth/login', ['email' => 'maria@example.com', 'password' => self::PASSWORD])
            ->assertStatus(400)
            ->assertJsonPath('code', 'stateful_required');
    }

    public function test_me_requires_authentication(): void
    {
        $this->getJson('/api/v1/auth/me')->assertUnauthorized();
    }

    public function test_me_returns_user_roles_and_permissions(): void
    {
        Sanctum::actingAs($this->makeUser(RoleName::FinanceOfficer));

        $this->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.roles', ['finance_officer'])
            ->assertJsonFragment(['donations.review']);
    }

    public function test_logout_is_audited(): void
    {
        $user = $this->makeUser(RoleName::CampaignManager);
        Sanctum::actingAs($user);

        $this->withHeaders($this->spa())->postJson('/api/v1/auth/logout')->assertNoContent();

        $this->assertDatabaseHas('audit_logs', ['action' => 'auth.logout', 'user_id' => $user->id]);
    }

    public function test_deactivated_account_is_blocked_even_with_a_valid_session(): void
    {
        Sanctum::actingAs($this->makeUser(RoleName::CampaignManager, ['is_active' => false]));

        $this->getJson('/api/v1/auth/me')->assertForbidden()->assertJsonPath('code', 'account_inactive');
    }

    public function test_temporary_password_blocks_everything_except_auth_endpoints(): void
    {
        Sanctum::actingAs($this->makeUser(RoleName::SystemAdministrator, ['must_change_password' => true]));

        $this->getJson('/api/v1/auth/me')->assertOk()->assertJsonPath('data.must_change_password', true);
        $this->getJson('/api/v1/users')->assertForbidden()->assertJsonPath('code', 'password_change_required');
    }

    public function test_change_password_flow(): void
    {
        $user = $this->makeUser(RoleName::SystemAdministrator, ['must_change_password' => true]);
        Sanctum::actingAs($user);
        $new = 'An0ther-Str0ng-Pass!';

        $this->postJson('/api/v1/auth/change-password', [
            'current_password' => 'incorrect', 'password' => $new, 'password_confirmation' => $new,
        ])->assertStatus(422)->assertJsonValidationErrors('current_password');

        $this->postJson('/api/v1/auth/change-password', [
            'current_password' => self::PASSWORD, 'password' => 'short', 'password_confirmation' => 'short',
        ])->assertStatus(422)->assertJsonValidationErrors('password');

        $this->postJson('/api/v1/auth/change-password', [
            'current_password' => self::PASSWORD, 'password' => $new, 'password_confirmation' => $new,
        ])->assertOk()->assertJsonPath('data.must_change_password', false);

        $this->assertTrue(\Illuminate\Support\Facades\Hash::check($new, $user->fresh()->password));
        $this->assertDatabaseHas('audit_logs', ['action' => 'auth.password_changed', 'user_id' => $user->id]);
        $this->getJson('/api/v1/users')->assertOk();
    }
}
