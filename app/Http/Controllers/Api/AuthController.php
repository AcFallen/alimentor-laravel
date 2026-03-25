<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        if (! Auth::attempt($request->validated())) {
            return response()->json([
                'message' => 'Credenciales incorrectas.',
            ], 401);
        }

        $user = Auth::user();

        $license = $this->getLicenseFromSupabase($user->email);

        if ($license === null) {
            Auth::guard('web')->logout();

            return response()->json([
                'message' => 'No se encontró una licencia asociada a este correo.',
            ], 403);
        }

        if ($license['status'] !== 'active') {
            Auth::guard('web')->logout();

            return response()->json([
                'message' => 'Tu licencia está en estado: '.$license['status'].'. Contacta al administrador.',
                'status' => $license['status'],
            ], 403);
        }

        $expiresAt = $this->parseExpirationDate($license['expires_at']);
        $user->update(['license_expires_at' => $expiresAt]);

        if ($expiresAt && now()->greaterThan($expiresAt)) {
            Auth::guard('web')->logout();

            return response()->json([
                'message' => 'Tu licencia venció el '.$expiresAt->format('d/m/Y').'. Contacta al administrador.',
                'status' => 'expired',
            ], 403);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'message' => 'Login exitoso.',
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Sesión cerrada.',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }

    /**
     * @return array{status: string, expires_at: string|null}|null
     */
    private function getLicenseFromSupabase(string $email): ?array
    {
        $apiKey = config('services.supabase.api_key');

        $response = Http::withHeaders([
            'apikey' => $apiKey,
            'Authorization' => 'Bearer '.$apiKey,
        ])->get(config('services.supabase.url').'/rest/v1/licenses', [
            'email' => 'eq.'.$email,
            'select' => 'status,expires_at',
            'limit' => 1,
        ]);

        if ($response->failed()) {
            return null;
        }

        $data = $response->json();

        if (empty($data)) {
            return null;
        }

        return [
            'status' => $data[0]['status'],
            'expires_at' => $data[0]['expires_at'] ?? null,
        ];
    }

    private function parseExpirationDate(?string $date): ?Carbon
    {
        if (empty($date)) {
            return null;
        }

        $formats = ['Y-m-d\TH:i:sP', 'Y-m-d\TH:i:s.uP', 'Y-m-d', 'd/m/Y', 'd-m-Y'];

        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, $date)->startOfDay();
            } catch (\Exception) {
                continue;
            }
        }

        return null;
    }
}
