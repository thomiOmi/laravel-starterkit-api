<?php

test('new users can register via api', function () {
    $payload = [
        'name' => 'User Test',
        'email' => 'test'.time().'@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ];

    $response = $this->postJson('/api/v1/users/register', $payload);

    $response->assertStatus(201)
        ->assertJsonPath('status', 'Success');
});

test('registration fails if email is invalid', function () {
    $response = $this->postJson('/api/v1/users/register', [
        'name' => 'User',
        'email' => 'not-an-email',
        'password' => '123',
        'password_confirmation' => '123',
    ]);

    $response->assertStatus(422);
});
