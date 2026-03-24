<?php

use Google\Service\Sheets;
use Google\Service\Sheets\AppendValuesResponse;
use Google\Service\Sheets\Resource\SpreadsheetsValues;

it('registers successfully and returns machine_key', function () {
    $mockValues = Mockery::mock(SpreadsheetsValues::class);
    $mockValues->shouldReceive('append')
        ->once()
        ->withArgs(function ($sheetId, $range, $values, $params) {
            $row = $values->getValues()[0];

            return $range === 'A:G'
                && $row[1] === 'Test Client'
                && $row[2] === 'test@example.com'
                && $row[3] === 'pending'
                && $params['valueInputOption'] === 'USER_ENTERED';
        })
        ->andReturn(new AppendValuesResponse);

    $mockSheets = Mockery::mock(Sheets::class);
    $mockSheets->spreadsheets_values = $mockValues;

    $this->app->instance(Sheets::class, $mockSheets);

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
});
