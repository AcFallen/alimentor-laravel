<?php

namespace App\Http\Controllers;

use App\Http\Requests\Registration\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class RegistrationController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $machineKey = Str::uuid()->toString();

        $apiKey = config('services.supabase.api_key');

        $response = Http::withHeaders([
            'apikey' => $apiKey,
            'Authorization' => 'Bearer '.$apiKey,
            'Prefer' => 'return=representation',
        ])->post(config('services.supabase.url').'/rest/v1/licenses', [
            'machine_key' => $machineKey,
            'name' => $request->validated('client_name'),
            'email' => $request->validated('email'),
            'phone' => $request->validated('phone'),
            'status' => 'pending',
        ]);

        if ($response->conflict()) {
            return response()->json([
                'message' => 'Esta máquina ya fue registrada.',
            ], 409);
        }

        if ($response->failed()) {
            return response()->json([
                'message' => 'Error al registrar la licencia.',
            ], 500);
        }

        User::query()->create([
            'name' => $request->validated('client_name'),
            'email' => $request->validated('email'),
            'password' => $request->validated('password'),
        ]);

        return response()->json([
            'message' => 'Registro exitoso.',
            'machine_key' => $machineKey,
        ], 201);
    }
}
