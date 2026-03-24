<?php

use App\Models\User;
use Google\Service\Sheets;
use Google\Service\Sheets\Resource\SpreadsheetsValues;
use Google\Service\Sheets\ValueRange;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function mockSheetsWithStatus(string $email, string $status): void
{
    $mockValues = Mockery::mock(SpreadsheetsValues::class);
    $mockValues->shouldReceive('get')
        ->andReturn(new ValueRange([
            'values' => [
                ['machine_key', 'client_name', 'email', 'status', 'expires_at', 'registered_at'],
                ['uuid-123', 'Test Client', $email, $status, '', '2026-03-24'],
            ],
        ]));

    $mockSheets = Mockery::mock(Sheets::class);
    $mockSheets->spreadsheets_values = $mockValues;

    app()->instance(Sheets::class, $mockSheets);
}

function mockSheetsWithNoLicense(): void
{
    $mockValues = Mockery::mock(SpreadsheetsValues::class);
    $mockValues->shouldReceive('get')
        ->andReturn(new ValueRange([
            'values' => [
                ['machine_key', 'client_name', 'email', 'status', 'expires_at', 'registered_at'],
            ],
        ]));

    $mockSheets = Mockery::mock(Sheets::class);
    $mockSheets->spreadsheets_values = $mockValues;

    app()->instance(Sheets::class, $mockSheets);
}

it('allows login when license is active', function () {
    User::factory()->create([
        'email' => 'active@example.com',
        'password' => 'machine-key-123',
    ]);

    mockSheetsWithStatus('active@example.com', 'active');

    $response = $this->postJson('/api/login', [
        'email' => 'active@example.com',
        'password' => 'machine-key-123',
    ]);

    $response->assertOk()
        ->assertJson(['message' => 'Login exitoso.'])
        ->assertJsonStructure(['message', 'user', 'token']);
});

it('rejects login when license is pending', function () {
    User::factory()->create([
        'email' => 'pending@example.com',
        'password' => 'machine-key-123',
    ]);

    mockSheetsWithStatus('pending@example.com', 'pending');

    $response = $this->postJson('/api/login', [
        'email' => 'pending@example.com',
        'password' => 'machine-key-123',
    ]);

    $response->assertForbidden()
        ->assertJson(['status' => 'pending']);
});

it('rejects login when license is suspended', function () {
    User::factory()->create([
        'email' => 'suspended@example.com',
        'password' => 'machine-key-123',
    ]);

    mockSheetsWithStatus('suspended@example.com', 'suspended');

    $response = $this->postJson('/api/login', [
        'email' => 'suspended@example.com',
        'password' => 'machine-key-123',
    ]);

    $response->assertForbidden()
        ->assertJson(['status' => 'suspended']);
});

it('rejects login when no license found', function () {
    User::factory()->create([
        'email' => 'unknown@example.com',
        'password' => 'machine-key-123',
    ]);

    mockSheetsWithNoLicense();

    $response = $this->postJson('/api/login', [
        'email' => 'unknown@example.com',
        'password' => 'machine-key-123',
    ]);

    $response->assertForbidden()
        ->assertJson(['message' => 'No se encontró una licencia asociada a este correo.']);
});

it('rejects login with wrong credentials', function () {
    User::factory()->create([
        'email' => 'user@example.com',
        'password' => 'correct-password',
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'user@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertUnauthorized();
});
