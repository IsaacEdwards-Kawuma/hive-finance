<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Webhook;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function index(): JsonResponse
    {
        $webhooks = Webhook::all();
        return response()->json(['data' => $webhooks]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'url' => 'required|url',
            'event' => 'required|string|in:invoice.paid,bill.paid',
            'secret' => 'nullable|string|max:255',
        ]);
        $validated['company_id'] = session('current_company_id');
        $validated['enabled'] = true;
        $webhook = Webhook::withoutGlobalScope(\App\Scopes\CompanyScope::class)->create($validated);
        return response()->json(['data' => $webhook], 201);
    }

    public function destroy(Webhook $webhook): JsonResponse
    {
        $webhook->delete();
        return response()->json(null, 204);
    }
}
