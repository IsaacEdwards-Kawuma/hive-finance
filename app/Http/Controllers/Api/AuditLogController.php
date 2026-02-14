<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = AuditLog::with('user:id,name,email')->orderByDesc('created_at');
        if ($request->auditable_type) {
            $query->where('auditable_type', $request->auditable_type);
        }
        if ($request->user_id) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->action) {
            $query->where('action', $request->action);
        }
        $logs = $query->paginate($request->get('per_page', 25));
        return response()->json($logs);
    }
}
