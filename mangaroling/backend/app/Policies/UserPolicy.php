<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    /** Managing a user requires users.manage AND the same organization (cross-org users look like 404). */
    public function manage(User $actor, User $target): Response
    {
        if (! $actor->hasPermission(Permission::UsersManage)) {
            return Response::deny();
        }

        return $actor->organization_id == $target->organization_id
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}
