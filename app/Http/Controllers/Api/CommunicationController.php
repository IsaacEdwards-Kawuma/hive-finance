<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Communication;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommunicationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Communication::with('user:id,name');
        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', '%' . $term . '%')
                    ->orWhere('body', 'like', '%' . $term . '%');
            });
        }
        if ($request->filled('pinned')) {
            $query->where('pinned', $request->boolean('pinned'));
        }
        $query->orderByDesc('pinned');
        $dir = ($request->get('sort') === 'oldest') ? 'asc' : 'desc';
        $query->orderBy('created_at', $dir);
        $items = $query->paginate($request->get('per_page', 20));
        return response()->json($items);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'target_role_ids' => 'nullable|array',
            'target_role_ids.*' => 'exists:roles,id',
            'pinned' => 'boolean',
        ]);
        $companyId = session('current_company_id');
        $validated['company_id'] = $companyId;
        $validated['user_id'] = $request->user()->id;
        $validated['pinned'] = $validated['pinned'] ?? false;
        $comm = Communication::withoutGlobalScope(\App\Scopes\CompanyScope::class)->create($validated);

        $company = \App\Models\Company::find($companyId);
        $userIds = $company->users()->pluck('users.id')->toArray();
        if (!empty($validated['target_role_ids'])) {
            $userIds = $company->users()->wherePivotIn('role_id', $validated['target_role_ids'])->pluck('users.id')->toArray();
        }
        foreach ($userIds as $uid) {
            Notification::create([
                'user_id' => $uid,
                'company_id' => $companyId,
                'type' => 'announcement',
                'title' => $comm->title,
                'body' => $comm->body,
                'communication_id' => $comm->id,
            ]);
        }

        $comm->load('user:id,name');
        return response()->json(['data' => $comm], 201);
    }

    public function show(Communication $communication): JsonResponse
    {
        $communication->load('user:id,name');
        return response()->json(['data' => $communication]);
    }

    public function update(Request $request, Communication $communication): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'body' => 'sometimes|string',
            'target_role_ids' => 'nullable|array',
            'target_role_ids.*' => 'exists:roles,id',
            'pinned' => 'boolean',
        ]);
        $communication->update($validated);
        $communication->load('user:id,name');
        return response()->json(['data' => $communication]);
    }

    public function destroy(Communication $communication): JsonResponse
    {
        $communication->delete();
        return response()->json(null, 204);
    }
}
