<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Support\RolePermissions;
use Illuminate\Http\JsonResponse;

class RoleController extends Controller
{
    public function index(): JsonResponse
    {
        $data = Role::orderBy('name')->get()->map(fn (Role $role) => [
            'name' => $role->name,
            'description' => $role->description,
            'permissions' => RolePermissions::forRoles([$role->name]),
        ]);

        return response()->json(['data' => $data]);
    }
}
