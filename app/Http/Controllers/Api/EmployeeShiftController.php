<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use App\Services\Permissions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeShiftController extends Controller
{
    private function hasPermission(Request $request, string $permission): bool
    {
        $companyId = session('current_company_id');
        $pivot = $request->user()->companies()->where('companies.id', $companyId)->first()?->pivot;
        $roleId = $pivot->role_id ?? null;
        if (!$roleId) {
            return in_array($permission, Permissions::ALL, true);
        }
        $role = \App\Models\Role::where('company_id', $companyId)->find($roleId);
        $perms = Permissions::forRole($role?->permissions);
        return in_array($permission, $perms, true);
    }

    public function index(Request $request): JsonResponse
    {
        if (!$this->hasPermission($request, 'employee.dashboard')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $userId = $request->user()->id;
        $canManage = $this->hasPermission($request, 'shifts.manage');
        $query = Shift::with('user:id,name', 'createdBy:id,name');
        if (!$canManage) {
            $query->where('user_id', $userId);
        }
        if ($request->filled('user_id')) {
            if ($canManage) {
                $query->where('user_id', $request->user_id);
            }
        }
        if ($request->filled('from')) {
            $query->where('start_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->where('start_at', '<=', $request->to . ' 23:59:59');
        }
        $shifts = $query->orderBy('start_at')->paginate($request->get('per_page', 50));
        return response()->json($shifts);
    }

    public function store(Request $request): JsonResponse
    {
        $canManage = $this->hasPermission($request, 'shifts.manage');
        if (!$canManage) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'start_at' => 'required|date',
            'end_at' => 'required|date|after:start_at',
            'title' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);
        $validated['company_id'] = session('current_company_id');
        $validated['created_by'] = $request->user()->id;
        $shift = Shift::withoutGlobalScope(\App\Scopes\CompanyScope::class)->create($validated);
        $shift->load('user:id,name');
        return response()->json(['data' => $shift], 201);
    }

    public function update(Request $request, Shift $shift): JsonResponse
    {
        if (!$this->hasPermission($request, 'shifts.manage')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $validated = $request->validate([
            'start_at' => 'sometimes|date',
            'end_at' => 'sometimes|date',
            'title' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);
        $shift->update($validated);
        $shift->load('user:id,name');
        return response()->json(['data' => $shift]);
    }

    public function destroy(Request $request, Shift $shift): JsonResponse
    {
        if (!$this->hasPermission($request, 'shifts.manage')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $shift->delete();
        return response()->json(null, 204);
    }
}
