<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Services\Permissions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $companyId = session('current_company_id');
        $roles = Role::withoutGlobalScope(\App\Scopes\CompanyScope::class)
            ->where('company_id', $companyId)
            ->orderBy('name')
            ->get();
        return response()->json(['data' => $roles]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'display_name' => 'nullable|string|max:255',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|in:' . implode(',', Permissions::ALL),
        ]);
        $validated['company_id'] = session('current_company_id');
        $validated['permissions'] = $validated['permissions'] ?? [];
        $role = Role::withoutGlobalScope(\App\Scopes\CompanyScope::class)->create($validated);
        return response()->json(['data' => $role], 201);
    }

    public function update(Request $request, Role $role): JsonResponse
    {
        if ($role->company_id != session('current_company_id')) {
            abort(404);
        }
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'display_name' => 'nullable|string|max:255',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|in:' . implode(',', Permissions::ALL),
        ]);
        if (isset($validated['permissions'])) {
            $role->permissions = $validated['permissions'];
            $role->save();
        }
        $role->update(array_filter([
            'name' => $validated['name'] ?? null,
            'display_name' => $validated['display_name'] ?? null,
        ]));
        return response()->json(['data' => $role]);
    }

    public function destroy(Role $role): JsonResponse
    {
        if ($role->company_id != session('current_company_id')) {
            abort(404);
        }
        $role->delete();
        return response()->json(null, 204);
    }
}
