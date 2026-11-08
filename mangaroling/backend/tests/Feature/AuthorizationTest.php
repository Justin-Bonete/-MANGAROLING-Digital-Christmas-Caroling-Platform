<?php

namespace Tests\Feature;

use App\Enums\Permission;
use App\Enums\RoleName;
use App\Support\RolePermissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase, CreatesUsers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpUsers();
    }

    public function test_every_permission_belongs_to_exactly_one_role(): void
    {
        $owners = [];
        foreach (RolePermissions::map() as $role => $permissions) {
            foreach ($permissions as $p) {
                $owners[$p->value][] = $role;
            }
        }
        foreach (Permission::cases() as $p) {
            $this->assertCount(1, $owners[$p->value] ?? [], "{$p->value} must be owned by exactly one role");
        }
    }

    public function test_roles_get_only_their_own_permissions(): void
    {
        $finance = $this->makeUser(RoleName::FinanceOfficer);
        $this->assertTrue($finance->can('donations.review'));
        $this->assertFalse($finance->can('campaigns.manage'));
        $this->assertFalse($finance->can('users.manage'));

        $manager = $this->makeUser(RoleName::CampaignManager);
        $this->assertTrue($manager->can('invitations.manage'));
        $this->assertFalse($manager->can('donations.review'));   // BR-009
    }

    public function test_multiple_roles_union_their_permissions(): void
    {
        $user = $this->makeUser([RoleName::CampaignManager, RoleName::FinanceOfficer]);
        $this->assertTrue($user->can('campaigns.manage'));
        $this->assertTrue($user->can('donations.review'));
    }

    public function test_inactive_user_has_no_permissions(): void
    {
        $this->assertFalse($this->makeUser(RoleName::SystemAdministrator, ['is_active' => false])->can('users.manage'));
    }

    public function test_guests_get_401_on_protected_endpoints(): void
    {
        foreach (['/api/v1/users', '/api/v1/roles', '/api/v1/audit-logs'] as $url) {
            $this->getJson($url)->assertUnauthorized();
        }
    }

    #[DataProvider('nonAdminRoles')]
    public function test_non_admin_roles_cannot_reach_admin_endpoints(RoleName $role): void
    {
        Sanctum::actingAs($this->makeUser($role));

        foreach (['/api/v1/users', '/api/v1/roles', '/api/v1/audit-logs'] as $url) {
            $this->getJson($url)->assertForbidden();
        }
        $this->postJson('/api/v1/users', [])->assertForbidden();
    }

    public static function nonAdminRoles(): array
    {
        return [[RoleName::CampaignManager], [RoleName::FinanceOfficer]];
    }

    public function test_user_with_no_roles_has_no_access(): void
    {
        Sanctum::actingAs($this->makeUser([]));
        $this->getJson('/api/v1/users')->assertForbidden();
    }

    public function test_role_middleware_requires_matching_role(): void
    {
        \Illuminate\Support\Facades\Route::middleware(['api', 'auth:sanctum', 'role:finance_officer'])
            ->get('/api/_test/role', fn () => ['ok' => true]);

        Sanctum::actingAs($this->makeUser(RoleName::CampaignManager));
        $this->getJson('/api/_test/role')->assertForbidden();

        Sanctum::actingAs($this->makeUser(RoleName::FinanceOfficer));
        $this->getJson('/api/_test/role')->assertOk();
    }
}
