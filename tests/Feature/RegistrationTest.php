<?php

use App\Models\User;
use Google\Service\Sheets;
use Google\Service\Sheets\AppendValuesResponse;
use Google\Service\Sheets\Resource\SpreadsheetsValues;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $mockValues = Mockery::mock(SpreadsheetsValues::class);
    $mockValues->shouldReceive('append')
        ->andReturn(new AppendValuesResponse);

    $mockSheets = Mockery::mock(Sheets::class);
    $mockSheets->spreadsheets_values = $mockValues;

    $this->app->instance(Sheets::class, $mockSheets);
});

it('registers successfully, creates user and returns machine_key', function () {
    $response = $this->postJson('/api/register', [
        'client_name' => 'Test Client',
        'email' => 'test@example.com',
    ]);

    $response->assertCreated()
        ->assertJsonStructure([
            'message',
            'machine_key',
            'registered_at',
        ])
        ->assertJson(['message' => 'Registro exitoso.']);

    $this->assertDatabaseHas('users', [
        'name' => 'Test Client',
        'email' => 'test@example.com',
    ]);
});

it('validates required fields for registration', function () {
    $response = $this->postJson('/api/register', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['client_name', 'email']);
});

it('validates email format for registration', function () {
    $response = $this->postJson('/api/register', [
        'client_name' => 'Test Client',
        'email' => 'not-an-email',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

it('rejects duplicate email registration', function () {
    User::factory()->create(['email' => 'test@example.com']);

    $response = $this->postJson('/api/register', [
        'client_name' => 'Test Client',
        'email' => 'test@example.com',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

it('returns error when google sheets api fails', function () {
    $mockValues = Mockery::mock(SpreadsheetsValues::class);
    $mockValues->shouldReceive('append')
        ->once()
        ->andThrow(new Exception('Google API error'));

    $mockSheets = Mockery::mock(Sheets::class);
    $mockSheets->spreadsheets_values = $mockValues;

    $this->app->instance(Sheets::class, $mockSheets);

    $response = $this->postJson('/api/register', [
        'client_name' => 'Test Client',
        'email' => 'test@example.com',
    ]);

    $response->assertStatus(500)
        ->assertJson(['message' => 'Error al registrar en Google Sheets.']);

    $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
});
