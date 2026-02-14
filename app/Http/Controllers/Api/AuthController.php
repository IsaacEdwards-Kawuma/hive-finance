<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);
        $company = Company::create([
            'name' => $validated['name'] . "'s Company",
            'default_currency' => 'USD',
        ]);
        $user->companies()->attach($company->id);
        $token = $user->createToken('spa')->plainTextToken;
        session(['current_company_id' => $company->id]);
        return response()->json([
            'user' => $user,
            'token' => $token,
            'companies' => $user->companies,
            'current_company_id' => $company->id,
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);
        $user = User::where('email', $request->email)->first();
        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages(['email' => ['The provided credentials are incorrect.']]);
        }
        $user->tokens()->delete();
        $token = $user->createToken('spa')->plainTextToken;
        $companies = $user->companies;
        $currentCompanyId = $companies->first()?->id;
        session(['current_company_id' => $currentCompanyId]);
        return response()->json([
            'user' => $user,
            'token' => $token,
            'companies' => $companies,
            'current_company_id' => $currentCompanyId,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out']);
    }
}
