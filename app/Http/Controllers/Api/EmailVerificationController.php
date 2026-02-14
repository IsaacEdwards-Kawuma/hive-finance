<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class EmailVerificationController extends Controller
{
    public function send(Request $request): JsonResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json(['message' => 'Already verified']);
        }
        $request->user()->sendEmailVerificationNotification();
        return response()->json(['message' => 'Verification link sent']);
    }

    /** Verify from signed link (no auth required). Route: GET /email/verify/{id}/{hash}?expires=&signature= */
    public function verifyFromLink(Request $request, $id, $hash): JsonResponse|RedirectResponse
    {
        if (!URL::hasValidSignature($request)) {
            $frontend = rtrim(config('app.frontend_url', config('app.url')), '/');
            return redirect($frontend . '/login?error=verification_expired');
        }
        $user = User::find($id);
        if (!$user || !hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            $frontend = rtrim(config('app.frontend_url', config('app.url')), '/');
            return redirect($frontend . '/login?error=verification_invalid');
        }
        if ($user->hasVerifiedEmail()) {
            $frontend = rtrim(config('app.frontend_url', config('app.url')), '/');
            return redirect($frontend . '/login?verified=1');
        }
        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }
        $frontend = rtrim(config('app.frontend_url', config('app.url')), '/');
        return redirect($frontend . '/login?verified=1');
    }
}
