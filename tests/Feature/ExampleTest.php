<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('an authenticated user can view the home page', function () {
    $response = $this->actingAs(User::factory()->create())->get('/');

    $response->assertOk();
});
