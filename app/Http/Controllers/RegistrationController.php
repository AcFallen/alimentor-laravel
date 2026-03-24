<?php

namespace App\Http\Controllers;

use App\Http\Requests\Registration\RegisterRequest;
use Google\Service\Sheets;
use Google\Service\Sheets\ValueRange;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class RegistrationController extends Controller
{
    public function register(RegisterRequest $request, Sheets $sheetsService): JsonResponse
    {
        $machineKey = Str::uuid()->toString();
        $registeredAt = now()->toDateTimeString();
        $sheetId = config('google_sheets.sheet_id');

        $values = new ValueRange([
            'values' => [
                [
                    $machineKey,                            // A: machine_key
                    $request->validated('client_name'),     // B: client_name
                    $request->validated('email'),           // C: email
                    'pending',                              // D: status
                    '',                                     // E: expires_at
                    $registeredAt,                          // F: registered_at
                ],
            ],
        ]);

        try {
            $sheetsService->spreadsheets_values->append(
                $sheetId,
                'A:G',
                $values,
                ['valueInputOption' => 'USER_ENTERED']
            );
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al registrar en Google Sheets.',
                'error' => $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'message' => 'Registro exitoso.',
            'machine_key' => $machineKey,
            'registered_at' => $registeredAt,
        ], 201);
    }
}
