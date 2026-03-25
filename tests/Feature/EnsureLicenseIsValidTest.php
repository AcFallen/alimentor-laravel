<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('allows request when license has not expired', function () {
    $user = User::factory()->create([
        'license_expires_at' => now()->addMonth(),
    ]);

    Sanctum::actingAs($user);

    $this->getJson('/api/me')->assertOk();
});

it('allows request when license_expires_at is null', function () {
    $user = User::factory()->create([
        'license_expires_at' => null,
    ]);

    Sanctum::actingAs($user);

    $this->getJson('/api/me')->assertOk();
});

it('blocks request when license has expired', function () {
    $user = User::factory()->create([
        'license_expires_at' => now()->subDay(),
    ]);

    Sanctum::actingAs($user);

    $this->getJson('/api/me')
        ->assertForbidden()
        ->assertJson([
            'message' => 'Tu licencia ha expirado. Inicia sesión nuevamente para verificar tu suscripción.',
            'status' => 'expired',
        ]);
});

it('deletes token when license has expired', function () {
    $user = User::factory()->create([
        'license_expires_at' => now()->subDay(),
    ]);

    Sanctum::actingAs($user);

    $this->getJson('/api/me')->assertForbidden();

    expect($user->tokens()->count())->toBe(0);
});
