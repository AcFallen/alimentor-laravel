<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows login with correct credentials', function () {
    User::factory()->create([
        'email' => 'user@example.com',
        'password' => 'machine-key-123',
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'user@example.com',
        'password' => 'machine-key-123',
    ]);

    $response->assertOk()
        ->assertJson(['message' => 'Login exitoso.'])
        ->assertJsonStructure(['message', 'user', 'token']);
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
