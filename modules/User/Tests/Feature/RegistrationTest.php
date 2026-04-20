<?php

test('pengguna baru dapat mendaftar melalui api', function () {
    $payload = [
        'name' => 'User Test',
        'email' => 'test'.time().'@example.com',
        'password' => 'password123',
    ];

    $response = $this->postJson('/api/v1/users/register', $payload);

    $response->assertStatus(201)
        ->assertJsonPath('status', 'Success');
});

test('pendaftaran gagal jika email tidak valid', function () {
    $response = $this->postJson('/api/v1/users/register', [
        'name' => 'User',
        'email' => 'bukan-email',
        'password' => '123',
    ]);

    $response->assertStatus(422);
});
