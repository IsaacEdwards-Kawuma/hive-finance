<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use App\Models\EmployeeMessage;
use App\Services\Permissions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeController extends Controller
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

    public function dashboard(Request $request): JsonResponse
    {
        if (!$this->hasPermission($request, 'employee.dashboard')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $userId = $request->user()->id;
        $companyId = session('current_company_id');
        $nextShifts = Shift::where('user_id', $userId)->where('start_at', '>=', now())->orderBy('start_at')->limit(5)->get();
        $unreadCount = EmployeeMessage::where(function ($q) use ($userId) {
            $q->where('to_user_id', $userId)->orWhere(function ($q2) use ($userId) {
                $q2->whereNull('to_user_id')->where('from_user_id', '!=', $userId);
            });
        })->whereNull('read_at')->count();

        $now = now();
        $weekStart = $now->copy()->startOfWeek();
        $weekEnd = $now->copy()->endOfWeek();
        $monthStart = $now->copy()->startOfMonth();
        $monthEnd = $now->copy()->endOfMonth();
        $shiftsWeek = Shift::where('user_id', $userId)->whereBetween('start_at', [$weekStart, $weekEnd])->get();
        $shiftsMonth = Shift::where('user_id', $userId)->whereBetween('start_at', [$monthStart, $monthEnd])->get();
        $hoursWeek = $shiftsWeek->sum(fn ($s) => $s->end_at && $s->start_at ? $s->end_at->diffInMinutes($s->start_at) / 60 : 0);
        $hoursMonth = $shiftsMonth->sum(fn ($s) => $s->end_at && $s->start_at ? $s->end_at->diffInMinutes($s->start_at) / 60 : 0);

        return response()->json([
            'data' => [
                'next_shifts' => $nextShifts,
                'unread_messages_count' => $unreadCount,
                'hours_this_week' => round($hoursWeek, 1),
                'hours_this_month' => round($hoursMonth, 1),
            ],
        ]);
    }

    /** Announcements (communications) visible to the current employee. */
    public function announcements(Request $request): JsonResponse
    {
        if (!$this->hasPermission($request, 'employee.dashboard')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $pivot = $request->user()->companies()->where('companies.id', session('current_company_id'))->first()?->pivot;
        $myRoleId = $pivot->role_id ?? null;

        $items = \App\Models\Communication::with('user:id,name')
            ->orderByDesc('pinned')
            ->orderByDesc('created_at')
            ->limit(100)
            ->get()
            ->filter(function ($comm) use ($myRoleId) {
                $ids = $comm->target_role_ids;
                if ($ids === null || (is_array($ids) && count($ids) === 0)) {
                    return true;
                }
                if ($myRoleId === null) {
                    return false;
                }
                return in_array((int) $myRoleId, array_map('intval', (array) $ids), true);
            })
            ->values()
            ->take(50);

        return response()->json(['data' => $items]);
    }

    /** Return first company user that has dashboard.view (manager). */
    public function manager(Request $request): JsonResponse
    {
        if (!$this->hasPermission($request, 'employee.dashboard')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $companyId = session('current_company_id');
        $company = \App\Models\Company::find($companyId);
        $manager = null;
        foreach ($company->users as $u) {
            $pivot = $u->companies()->where('companies.id', $companyId)->first()?->pivot;
            $roleId = $pivot->role_id ?? null;
            if ($roleId) {
                $role = \App\Models\Role::where('company_id', $companyId)->find($roleId);
                $perms = Permissions::forRole($role?->permissions);
                if (in_array('dashboard.view', $perms, true)) {
                    $manager = ['id' => $u->id, 'name' => $u->name, 'email' => $u->email];
                    break;
                }
            }
        }
        return response()->json(['data' => $manager]);
    }

    /** List company users (for team chat / colleague list). */
    public function colleagues(Request $request): JsonResponse
    {
        if (!$this->hasPermission($request, 'employee.dashboard')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $companyId = session('current_company_id');
        $company = \App\Models\Company::find($companyId);
        $me = $request->user()->id;
        $list = $company->users()->where('users.id', '!=', $me)->get(['users.id', 'users.name', 'users.email'])->map(fn ($u) => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email]);
        return response()->json(['data' => $list]);
    }
}
