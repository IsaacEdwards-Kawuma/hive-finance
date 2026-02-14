<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorController extends Controller
{
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();
        return response()->json([
            'enabled' => (bool) ($user->two_factor_enabled ?? false),
        ]);
    }

    public function enable(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user->two_factor_enabled) {
            return response()->json(['message' => '2FA is already enabled'], 422);
        }
        $google2fa = new Google2FA();
        $secret = $google2fa->generateSecretKey(32);
        $user->update(['two_factor_secret' => encrypt($secret), 'two_factor_enabled' => false]);
        $companyName = config('app.name');
        $qrCodeUrl = $google2fa->getQRCodeUrl($companyName, $user->email, $secret);
        return response()->json([
            'secret' => $secret,
            'qr_code_url' => $qrCodeUrl,
        ]);
    }

    public function confirm(Request $request): JsonResponse
    {
        $request->validate(['code' => 'required|string|size:6']);
        $user = $request->user();
        $secret = $user->two_factor_secret ? decrypt($user->two_factor_secret) : null;
        if (!$secret) {
            return response()->json(['message' => 'Start 2FA setup first'], 422);
        }
        $google2fa = new Google2FA();
        if (!$google2fa->verifyKey($secret, $request->code)) {
            return response()->json(['message' => 'Invalid verification code'], 422);
        }
        $user->update(['two_factor_enabled' => true]);
        return response()->json(['message' => '2FA enabled successfully']);
    }

    public function verify(Request $request): JsonResponse
    {
        $request->validate(['code' => 'required|string|size:6']);
        $user = $request->user();
        $secret = $user->two_factor_secret ? decrypt($user->two_factor_secret) : null;
        if (!$secret || !$user->two_factor_enabled) {
            return response()->json(['message' => '2FA is not enabled'], 422);
        }
        $google2fa = new Google2FA();
        if (!$google2fa->verifyKey($secret, $request->code)) {
            return response()->json(['message' => 'Invalid code'], 422);
        }
        return response()->json(['message' => 'Verified']);
    }

    public function disable(Request $request): JsonResponse
    {
        $request->validate(['password' => 'required|string']);
        $user = $request->user();
        if (!Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Password is incorrect'], 422);
        }
        $user->update(['two_factor_secret' => null, 'two_factor_enabled' => false]);
        return response()->json(['message' => '2FA disabled']);
    }
}
