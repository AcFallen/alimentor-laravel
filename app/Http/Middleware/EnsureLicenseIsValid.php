<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureLicenseIsValid
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->license_expires_at && now()->greaterThan($user->license_expires_at)) {
            $user->currentAccessToken()->delete();

            return response()->json([
                'message' => 'Tu licencia ha expirado. Inicia sesión nuevamente para verificar tu suscripción.',
                'status' => 'expired',
            ], 403);
        }

        return $next($request);
    }
}
