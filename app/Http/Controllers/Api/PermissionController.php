<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Permissions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $companyId = session('current_company_id');
        $pivot = $user->companies()->where('companies.id', $companyId)->first()?->pivot;
        $roleId = $pivot->role_id ?? null;
        $permissions = Permissions::ALL;
        if ($roleId && $companyId) {
            $role = \App\Models\Role::where('company_id', $companyId)->find($roleId);
            $rolePerms = $role?->permissions ?? [];
            if (is_array($rolePerms) && in_array(Permissions::ADMIN_FULL, $rolePerms, true)) {
                $permissions = Permissions::ALL;
            } else {
                $permissions = Permissions::forRole($rolePerms);
            }
        }
        return response()->json(['data' => array_values($permissions)]);
    }

    public function definitions(): JsonResponse
    {
        return response()->json(['data' => Permissions::definitions()]);
    }
}
