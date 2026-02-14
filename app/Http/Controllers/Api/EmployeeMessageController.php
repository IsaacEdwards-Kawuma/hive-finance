<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmployeeMessage;
use App\Services\Permissions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeMessageController extends Controller
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
        $query = EmployeeMessage::with('fromUser:id,name')
            ->where(function ($q) use ($userId) {
                $q->where('from_user_id', $userId)
                    ->orWhere('to_user_id', $userId)
                    ->orWhere(function ($q2) use ($userId) {
                        $q2->whereNull('to_user_id')->where('from_user_id', '!=', $userId);
                    });
            })
            ->orderByDesc('created_at')
            ->limit($request->get('limit', 100));
        $messages = $query->get();
        return response()->json(['data' => $messages]);
    }

    public function store(Request $request): JsonResponse
    {
        if (!$this->hasPermission($request, 'employee.dashboard')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $validated = $request->validate([
            'to_user_id' => 'nullable|exists:users,id', // null = team message
            'body' => 'required|string|max:5000',
        ]);
        $validated['company_id'] = session('current_company_id');
        $validated['from_user_id'] = $request->user()->id;
        $msg = EmployeeMessage::withoutGlobalScope(\App\Scopes\CompanyScope::class)->create($validated);
        $msg->load('fromUser:id,name');
        return response()->json(['data' => $msg], 201);
    }

    public function markRead(Request $request, EmployeeMessage $employee_message): JsonResponse
    {
        if (!$this->hasPermission($request, 'employee.dashboard')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $userId = $request->user()->id;
        if ((int) $employee_message->to_user_id === $userId || ($employee_message->to_user_id === null && (int) $employee_message->from_user_id !== $userId)) {
            $employee_message->update(['read_at' => now()]);
        }
        return response()->json(['data' => $employee_message]);
    }
}
