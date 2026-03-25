<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Carbon\Carbon;
use Google\Service\Sheets;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(LoginRequest $request, Sheets $sheetsService): JsonResponse
    {
        if (! Auth::attempt($request->validated())) {
            return response()->json([
                'message' => 'Credenciales incorrectas.',
            ], 401);
        }

        $user = Auth::user();

        $license = $this->getLicenseData($sheetsService, $user->email);

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

        // Sincronizar expires_at del Sheet a la BD local
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

    private function parseExpirationDate(?string $date): ?Carbon
    {
        if (empty($date)) {
            return null;
        }

        $formats = ['d/m/Y', 'Y-m-d', 'd-m-Y', 'm/d/Y'];

        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, $date)->startOfDay();
            } catch (\Exception) {
                continue;
            }
        }

        return null;
    }

    /**
     * @return array{status: string, expires_at: string|null}|null
     */
    private function getLicenseData(Sheets $sheetsService, string $email): ?array
    {
        $sheetId = config('google_sheets.sheet_id');

        $response = $sheetsService->spreadsheets_values->get($sheetId, 'A:G');
        $rows = $response->getValues();

        if (! $rows) {
            return null;
        }

        foreach ($rows as $row) {
            if (isset($row[2]) && $row[2] === $email) {
                return [
                    'status' => $row[3] ?? null,
                    'expires_at' => ! empty($row[4]) ? $row[4] : null,
                ];
            }
        }

        return null;
    }
}
