<?php

use App\Models\User;
use Google\Service\Sheets;
use Google\Service\Sheets\Resource\SpreadsheetsValues;
use Google\Service\Sheets\ValueRange;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function mockSheetsWithLicense(string $email, string $status, string $expiresAt = ''): void
{
    $mockValues = Mockery::mock(SpreadsheetsValues::class);
    $mockValues->shouldReceive('get')
        ->andReturn(new ValueRange([
            'values' => [
                ['machine_key', 'client_name', 'email', 'status', 'expires_at', 'registered_at'],
                ['uuid-123', 'Test Client', $email, $status, $expiresAt, '2026-03-24'],
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

it('allows login when license is active with valid expiration', function () {
    User::factory()->create([
        'email' => 'active@example.com',
        'password' => 'machine-key-123',
    ]);

    mockSheetsWithLicense('active@example.com', 'active', '2027-12-31');

    $response = $this->postJson('/api/login', [
        'email' => 'active@example.com',
        'password' => 'machine-key-123',
    ]);

    $response->assertOk()
        ->assertJson(['message' => 'Login exitoso.'])
        ->assertJsonStructure(['message', 'user', 'token']);

    $this->assertDatabaseHas('users', [
        'email' => 'active@example.com',
        'license_expires_at' => '2027-12-31 00:00:00',
    ]);
});

it('syncs expires_at on each login', function () {
    User::factory()->create([
        'email' => 'user@example.com',
        'password' => 'machine-key-123',
        'license_expires_at' => '2026-04-01',
    ]);

    mockSheetsWithLicense('user@example.com', 'active', '2026-06-01');

    $this->postJson('/api/login', [
        'email' => 'user@example.com',
        'password' => 'machine-key-123',
    ])->assertOk();

    $this->assertDatabaseHas('users', [
        'email' => 'user@example.com',
        'license_expires_at' => '2026-06-01 00:00:00',
    ]);
});

it('rejects login when license is expired', function () {
    User::factory()->create([
        'email' => 'expired@example.com',
        'password' => 'machine-key-123',
    ]);

    mockSheetsWithLicense('expired@example.com', 'active', '2025-01-01');

    $response = $this->postJson('/api/login', [
        'email' => 'expired@example.com',
        'password' => 'machine-key-123',
    ]);

    $response->assertForbidden()
        ->assertJson(['status' => 'expired']);
});

it('rejects login when license is pending', function () {
    User::factory()->create([
        'email' => 'pending@example.com',
        'password' => 'machine-key-123',
    ]);

    mockSheetsWithLicense('pending@example.com', 'pending');

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

    mockSheetsWithLicense('suspended@example.com', 'suspended');

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
