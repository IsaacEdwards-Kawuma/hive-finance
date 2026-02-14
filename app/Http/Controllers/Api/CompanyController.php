<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $companies = $request->user()->companies()->withPivot('role_id')->get(['companies.id', 'companies.name', 'companies.default_currency']);
        return response()->json(['data' => $companies]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'address' => 'nullable|string',
            'default_currency' => 'sometimes|string|size:3',
            'fiscal_year_start' => 'nullable|string|max:5',
        ]);
        $validated['default_currency'] = $validated['default_currency'] ?? 'USD';
        $validated['fiscal_year_start'] = $validated['fiscal_year_start'] ?? '01-01';
        $company = Company::create($validated);
        $request->user()->companies()->attach($company->id);
        return response()->json(['data' => $company], 201);
    }

    public function show(Company $company): JsonResponse
    {
        $this->authorize('view', $company);
        return response()->json(['data' => $company]);
    }

    public function update(Request $request, Company $company): JsonResponse
    {
        $this->authorize('view', $company);
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'nullable|email',
            'address' => 'nullable|string',
            'logo' => 'nullable|string|max:500',
            'fiscal_year_start' => 'sometimes|string|max:5',
            'default_currency' => 'sometimes|string|size:3',
            'tax_id' => 'nullable|string|max:50',
            'settings' => 'nullable|array',
        ]);
        if (isset($validated['settings'])) {
            $validated['settings'] = array_merge($company->settings ?? [], $validated['settings']);
        }
        $company->update($validated);
        return response()->json(['data' => $company]);
    }

    public function switchCompany(Request $request): JsonResponse
    {
        $request->validate(['company_id' => 'required|exists:companies,id']);
        $companyId = (int) $request->company_id;
        $allowed = $request->user()->companies()->where('companies.id', $companyId)->exists();
        if (!$allowed) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        session(['current_company_id' => $companyId]);
        return response()->json(['data' => ['company_id' => $companyId]]);
    }

    public function updateMyRole(Request $request, Company $company): JsonResponse
    {
        $this->authorize('view', $company);
        $validated = $request->validate(['role_id' => 'nullable|exists:roles,id']);
        $roleId = $validated['role_id'] ?? null;
        $role = $roleId ? \App\Models\Role::where('company_id', $company->id)->find($roleId) : null;
        if ($roleId && !$role) {
            return response()->json(['message' => 'Role not found in this company'], 422);
        }
        $request->user()->companies()->updateExistingPivot($company->id, ['role_id' => $roleId]);
        return response()->json(['data' => ['role_id' => $roleId]]);
    }

    public function members(Request $request, Company $company): JsonResponse
    {
        $this->authorize('view', $company);
        $members = $company->users()->get()->map(function ($user) use ($company) {
            $pivot = $user->pivot;
            return [
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role_id' => $pivot->role_id,
            ];
        });
        return response()->json(['data' => $members]);
    }

    public function updateMemberRole(Request $request, Company $company, int $userId): JsonResponse
    {
        $this->authorize('view', $company);
        $validated = $request->validate(['role_id' => 'nullable|exists:roles,id']);
        $roleId = $validated['role_id'] ?? null;
        $role = $roleId ? \App\Models\Role::where('company_id', $company->id)->find($roleId) : null;
        if ($roleId && !$role) {
            return response()->json(['message' => 'Role not found in this company'], 422);
        }
        if (!$company->users()->where('users.id', $userId)->exists()) {
            return response()->json(['message' => 'User is not a member of this company'], 404);
        }
        $company->users()->updateExistingPivot($userId, ['role_id' => $roleId]);
        return response()->json(['data' => ['role_id' => $roleId]]);
    }
}
