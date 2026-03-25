<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

function fakeSupabaseLicense(string $email, string $status, ?string $expiresAt = null): void
{
    Http::fake([
        '*/rest/v1/licenses*' => Http::response([[
            'status' => $status,
            'expires_at' => $expiresAt,
        ]], 200),
    ]);
}

function fakeSupabaseNoLicense(): void
{
    Http::fake([
        '*/rest/v1/licenses*' => Http::response([], 200),
    ]);
}

it('allows login when license is active with valid expiration', function () {
    User::factory()->create([
        'email' => 'active@example.com',
        'password' => 'secret123',
    ]);

    fakeSupabaseLicense('active@example.com', 'active', '2027-12-31T00:00:00+00:00');

    $response = $this->postJson('/api/login', [
        'email' => 'active@example.com',
        'password' => 'secret123',
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
        'password' => 'secret123',
        'license_expires_at' => '2026-04-01',
    ]);

    fakeSupabaseLicense('user@example.com', 'active', '2026-06-01T00:00:00+00:00');

    $this->postJson('/api/login', [
        'email' => 'user@example.com',
        'password' => 'secret123',
    ])->assertOk();

    $this->assertDatabaseHas('users', [
        'email' => 'user@example.com',
        'license_expires_at' => '2026-06-01 00:00:00',
    ]);
});

it('rejects login when license is expired', function () {
    User::factory()->create([
        'email' => 'expired@example.com',
        'password' => 'secret123',
    ]);

    fakeSupabaseLicense('expired@example.com', 'active', '2025-01-01T00:00:00+00:00');

    $response = $this->postJson('/api/login', [
        'email' => 'expired@example.com',
        'password' => 'secret123',
    ]);

    $response->assertForbidden()
        ->assertJson(['status' => 'expired']);
});

it('rejects login when license is pending', function () {
    User::factory()->create([
        'email' => 'pending@example.com',
        'password' => 'secret123',
    ]);

    fakeSupabaseLicense('pending@example.com', 'pending');

    $response = $this->postJson('/api/login', [
        'email' => 'pending@example.com',
        'password' => 'secret123',
    ]);

    $response->assertForbidden()
        ->assertJson(['status' => 'pending']);
});

it('rejects login when license is suspended', function () {
    User::factory()->create([
        'email' => 'suspended@example.com',
        'password' => 'secret123',
    ]);

    fakeSupabaseLicense('suspended@example.com', 'suspended');

    $response = $this->postJson('/api/login', [
        'email' => 'suspended@example.com',
        'password' => 'secret123',
    ]);

    $response->assertForbidden()
        ->assertJson(['status' => 'suspended']);
});

it('rejects login when no license found', function () {
    User::factory()->create([
        'email' => 'unknown@example.com',
        'password' => 'secret123',
    ]);

    fakeSupabaseNoLicense();

    $response = $this->postJson('/api/login', [
        'email' => 'unknown@example.com',
        'password' => 'secret123',
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

it('allows login when license has no expiration date', function () {
    User::factory()->create([
        'email' => 'noexpiry@example.com',
        'password' => 'secret123',
    ]);

    fakeSupabaseLicense('noexpiry@example.com', 'active');

    $response = $this->postJson('/api/login', [
        'email' => 'noexpiry@example.com',
        'password' => 'secret123',
    ]);

    $response->assertOk()
        ->assertJsonStructure(['message', 'user', 'token']);
});
