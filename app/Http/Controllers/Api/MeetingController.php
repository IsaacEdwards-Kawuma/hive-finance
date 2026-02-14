<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Meeting;
use App\Scopes\CompanyScope;
use App\Services\Permissions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeetingController extends Controller
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
        $perms = $role?->permissions ?? [];
        if (is_array($perms) && in_array(Permissions::ADMIN_FULL, $perms, true)) {
            return true;
        }
        $perms = Permissions::forRole($perms);
        return in_array($permission, $perms, true);
    }

    public function index(Request $request): JsonResponse
    {
        if (!$this->hasPermission($request, 'meetings.view')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $query = Meeting::with('createdByUser:id,name');
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('from')) {
            $query->where('meeting_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->where('meeting_at', '<=', $request->to);
        }
        if ($request->filled('upcoming') && $request->boolean('upcoming')) {
            $query->where('meeting_at', '>=', now())->where('status', 'scheduled');
        }
        $query->orderBy('meeting_at');
        $items = $query->paginate($request->get('per_page', 20));
        return response()->json($items);
    }

    public function store(Request $request): JsonResponse
    {
        if (!$this->hasPermission($request, 'meetings.create')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'location' => 'nullable|string|max:255',
            'meeting_at' => 'required|date',
            'duration_minutes' => 'nullable|integer|min:5|max:480',
            'status' => 'nullable|in:scheduled,completed,cancelled',
            'attendee_user_ids' => 'nullable|array',
            'attendee_user_ids.*' => 'integer|exists:users,id',
        ]);
        $validated['company_id'] = session('current_company_id');
        $validated['created_by'] = $request->user()->id;
        $validated['duration_minutes'] = $validated['duration_minutes'] ?? 60;
        $validated['status'] = $validated['status'] ?? 'scheduled';
        $meeting = Meeting::withoutGlobalScope(CompanyScope::class)->create($validated);
        $meeting->load('createdByUser:id,name');
        return response()->json(['data' => $meeting], 201);
    }

    public function show(Request $request, Meeting $meeting): JsonResponse
    {
        if (!$this->hasPermission($request, 'meetings.view')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $meeting->load('createdByUser:id,name');
        return response()->json(['data' => $meeting]);
    }

    public function update(Request $request, Meeting $meeting): JsonResponse
    {
        if (!$this->hasPermission($request, 'meetings.edit')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'location' => 'nullable|string|max:255',
            'meeting_at' => 'sometimes|date',
            'duration_minutes' => 'nullable|integer|min:5|max:480',
            'status' => 'nullable|in:scheduled,completed,cancelled',
            'attendee_user_ids' => 'nullable|array',
            'attendee_user_ids.*' => 'integer|exists:users,id',
        ]);
        $meeting->update($validated);
        $meeting->load('createdByUser:id,name');
        return response()->json(['data' => $meeting]);
    }

    public function destroy(Request $request, Meeting $meeting): JsonResponse
    {
        if (!$this->hasPermission($request, 'meetings.delete')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $meeting->delete();
        return response()->json(['message' => 'Deleted'], 200);
    }
}
