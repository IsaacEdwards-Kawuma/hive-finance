<?php

namespace App\Http\Controllers\Api\Portal;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'company_id' => 'required|exists:companies,id',
        ]);
        $customer = Customer::withoutGlobalScope(\App\Scopes\CompanyScope::class)
            ->where('company_id', $validated['company_id'])
            ->where('email', $validated['email'])
            ->first();
        if (!$customer || !$customer->password || !\Illuminate\Support\Facades\Hash::check($validated['password'], $customer->password)) {
            return response()->json(['message' => 'Invalid credentials'], 422);
        }
        $customer->tokens()->delete();
        $token = $customer->createToken('portal')->plainTextToken;
        return response()->json([
            'token' => $token,
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'company_id' => $customer->company_id,
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $customer = $request->user();
        return response()->json([
            'data' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'company_id' => $customer->company_id,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out']);
    }
}
