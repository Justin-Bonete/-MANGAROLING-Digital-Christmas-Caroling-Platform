<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Http\Requests\Users\ResetPasswordRequest;
use App\Http\Requests\Users\StoreUserRequest;
use App\Http\Requests\Users\SyncRolesRequest;
use App\Http\Requests\Users\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/** Account management (FR-001). Route group requires can:users.manage; the policy adds org scoping. */
class UserController extends Controller
{
    public function __construct(private AuditLogger $audit) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = User::query()
            ->where('organization_id', $request->user()->organization_id)
            ->with('roles');

        if (($term = trim((string) $request->query('q'))) !== '') {
            $like = '%'.addcslashes($term, '%_\\').'%';
            $query->where(fn ($w) => $w->where('name', 'ilike', $like)->orWhere('email', 'ilike', $like));
        }
        if ($role = $request->query('role')) {
            $query->whereHas('roles', fn ($r) => $r->where('name', $role));
        }
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        return UserResource::collection($query->orderBy('name')->paginate(20)->withQueryString());
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $actor = $request->user();
        $data = $request->validated();

        $user = DB::transaction(function () use ($actor, $data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'organization_id' => $actor->organization_id,
                'is_active' => true,
                'must_change_password' => true,   // the admin-set password is temporary
            ]);
            $user->roles()->sync(Role::whereIn('name', $data['roles'])->pluck('id'));
            $this->audit->record('user.created', 'user', $user->id, ['roles' => $data['roles']], $actor);

            return $user;
        });

        return (new UserResource($user->load('roles')))->response()->setStatusCode(201);
    }

    public function show(User $user): UserResource
    {
        Gate::authorize('manage', $user);

        return new UserResource($user->load('roles'));
    }

    public function update(UpdateUserRequest $request, User $user): UserResource
    {
        Gate::authorize('manage', $user);

        $user->fill($request->validated());
        $changed = array_keys($user->getDirty());
        $user->save();

        if ($changed) {
            $this->audit->record('user.updated', 'user', $user->id, ['changed' => $changed]);
        }

        return new UserResource($user->load('roles'));
    }

    public function setActive(Request $request, User $user): UserResource
    {
        Gate::authorize('manage', $user);
        $active = (bool) $request->validate(['is_active' => ['required', 'boolean']])['is_active'];

        if (! $active && $user->is($request->user())) {
            throw ValidationException::withMessages(['is_active' => ['You cannot deactivate your own account.']]);
        }

        $user->update(['is_active' => $active]);
        if (! $active) {
            $user->tokens()->delete();
        }
        $this->audit->record($active ? 'user.activated' : 'user.deactivated', 'user', $user->id);

        return new UserResource($user->load('roles'));
    }

    public function syncRoles(SyncRolesRequest $request, User $user): UserResource
    {
        Gate::authorize('manage', $user);
        $names = $request->validated('roles');
        $before = $user->roleNames();

        if ($user->is($request->user()) && ! in_array(RoleName::SystemAdministrator->value, $names, true)) {
            throw ValidationException::withMessages(['roles' => ['You cannot remove your own administrator role.']]);
        }

        DB::transaction(function () use ($user, $names, $before) {
            $user->roles()->sync(Role::whereIn('name', $names)->pluck('id'));
            $this->audit->record('user.roles_changed', 'user', $user->id, ['before' => $before, 'after' => $names]);
        });

        return new UserResource($user->unsetRelation('roles')->load('roles'));
    }

    public function resetPassword(ResetPasswordRequest $request, User $user): UserResource
    {
        Gate::authorize('manage', $user);

        $user->forceFill([
            'password' => $request->validated('password'),
            'must_change_password' => true,
        ])->save();
        $user->tokens()->delete();
        $this->audit->record('user.password_reset', 'user', $user->id);   // never log the password

        return new UserResource($user->load('roles'));
    }
}
