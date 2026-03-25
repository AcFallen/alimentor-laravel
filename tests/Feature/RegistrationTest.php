<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

it('registers successfully, creates user and returns machine_key', function () {
    Http::fake(['*/rest/v1/licenses' => Http::response([['id' => 1]], 201)]);

    $response = $this->postJson('/api/register', [
        'client_name' => 'Test Client',
        'email' => 'test@example.com',
        'password' => 'secret123',
        'phone' => '+51 999 999 999',
    ]);

    $response->assertCreated()
        ->assertJsonStructure(['message', 'machine_key'])
        ->assertJson(['message' => 'Registro exitoso.']);

    $this->assertDatabaseHas('users', [
        'name' => 'Test Client',
        'email' => 'test@example.com',
    ]);

    Http::assertSent(function ($request) {
        return str_contains($request->url(), '/rest/v1/licenses')
            && $request['name'] === 'Test Client'
            && $request['email'] === 'test@example.com'
            && $request['phone'] === '+51 999 999 999'
            && ! empty($request['machine_key']);
    });
});

it('validates required fields for registration', function () {
    $response = $this->postJson('/api/register', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['client_name', 'email', 'password']);
});

it('validates email format for registration', function () {
    $response = $this->postJson('/api/register', [
        'client_name' => 'Test Client',
        'email' => 'not-an-email',
        'password' => 'secret123',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

it('validates password minimum length', function () {
    $response = $this->postJson('/api/register', [
        'client_name' => 'Test Client',
        'email' => 'test@example.com',
        'password' => '123',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);
});

it('rejects duplicate email registration', function () {
    User::factory()->create(['email' => 'test@example.com']);

    $response = $this->postJson('/api/register', [
        'client_name' => 'Test Client',
        'email' => 'test@example.com',
        'password' => 'secret123',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

it('allows phone to be optional', function () {
    Http::fake(['*/rest/v1/licenses' => Http::response([['id' => 1]], 201)]);

    $response = $this->postJson('/api/register', [
        'client_name' => 'Test Client',
        'email' => 'test@example.com',
        'password' => 'secret123',
    ]);

    $response->assertCreated();
});

it('returns error when supabase fails', function () {
    Http::fake([
        '*/rest/v1/licenses' => Http::response(['error' => 'Server error'], 500),
    ]);

    $response = $this->postJson('/api/register', [
        'client_name' => 'Test Client',
        'email' => 'test@example.com',
        'password' => 'secret123',
    ]);

    $response->assertStatus(500)
        ->assertJson(['message' => 'Error al registrar la licencia.']);

    $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
});

it('returns 409 when machine key conflicts', function () {
    Http::fake([
        '*/rest/v1/licenses' => Http::response(['error' => 'Conflict'], 409),
    ]);

    $response = $this->postJson('/api/register', [
        'client_name' => 'Test Client',
        'email' => 'test@example.com',
        'password' => 'secret123',
    ]);

    $response->assertStatus(409);
});
