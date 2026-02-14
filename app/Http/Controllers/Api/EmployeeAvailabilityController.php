<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmployeeAvailability;
use App\Services\Permissions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeAvailabilityController extends Controller
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
        $items = EmployeeAvailability::where('user_id', $request->user()->id)->orderBy('day_of_week')->orderBy('start_time')->get();
        return response()->json(['data' => $items]);
    }

    public function store(Request $request): JsonResponse
    {
        if (!$this->hasPermission($request, 'employee.dashboard')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $validated = $request->validate([
            'day_of_week' => 'required|integer|min:0|max:6',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'notes' => 'nullable|string|max:500',
        ]);
        $validated['company_id'] = session('current_company_id');
        $validated['user_id'] = $request->user()->id;
        $item = EmployeeAvailability::withoutGlobalScope(\App\Scopes\CompanyScope::class)->create($validated);
        return response()->json(['data' => $item], 201);
    }

    public function destroy(Request $request, EmployeeAvailability $employee_availability): JsonResponse
    {
        if (!$this->hasPermission($request, 'employee.dashboard')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        if ((int) $employee_availability->user_id !== (int) $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $employee_availability->delete();
        return response()->json(null, 204);
    }
}
