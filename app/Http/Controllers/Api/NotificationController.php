<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $companyId = session('current_company_id');
        $query = Notification::where('user_id', $request->user()->id)
            ->where('company_id', $companyId)
            ->orderByDesc('created_at');
        $items = $query->paginate($request->get('per_page', 30));
        return response()->json($items);
    }

    public function unreadCount(): JsonResponse
    {
        $companyId = session('current_company_id');
        $count = Notification::where('user_id', request()->user()->id)
            ->where('company_id', $companyId)
            ->whereNull('read_at')
            ->count();
        return response()->json(['data' => ['count' => $count]]);
    }

    public function markRead(Notification $notification): JsonResponse
    {
        if ((int) $notification->user_id !== (int) request()->user()->id) {
            abort(403);
        }
        $notification->update(['read_at' => now()]);
        return response()->json(['data' => $notification]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $companyId = session('current_company_id');
        Notification::where('user_id', $request->user()->id)
            ->where('company_id', $companyId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
        return response()->json(['data' => ['ok' => true]]);
    }
}
