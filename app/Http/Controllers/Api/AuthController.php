<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
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

        $status = $this->getLicenseStatus($sheetsService, $user->email);

        if ($status === null) {
            Auth::guard('web')->logout();

            return response()->json([
                'message' => 'No se encontró una licencia asociada a este correo.',
            ], 403);
        }

        if ($status !== 'active') {
            Auth::guard('web')->logout();

            return response()->json([
                'message' => 'Tu licencia está en estado: '.$status.'. Contacta al administrador.',
                'status' => $status,
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

    private function getLicenseStatus(Sheets $sheetsService, string $email): ?string
    {
        $sheetId = config('google_sheets.sheet_id');

        $response = $sheetsService->spreadsheets_values->get($sheetId, 'A:G');
        $rows = $response->getValues();

        if (! $rows) {
            return null;
        }

        foreach ($rows as $row) {
            if (isset($row[2]) && $row[2] === $email) {
                return $row[3] ?? null; // D: status
            }
        }

        return null;
    }
}
