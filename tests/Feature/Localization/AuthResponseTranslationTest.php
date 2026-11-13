<?php

declare(strict_types=1);

use Modules\IAM\Database\Factories\UserFactory;

describe('translated auth responses', function (): void {

    it('renders a translated access denied response for banned users', function (string $locale, string $title, string $detail): void {
        loginAsUser(UserFactory::new()->banned()->createOne(['email_verified_at' => now()]));

        $response = $this->withHeader('Accept-Language', $locale)->getJson('/api/v1/auth/me');

        $response->assertForbidden()
            ->assertJsonPath('title', $title)
            ->assertJsonPath('detail', $detail);
    })->with([
        'en' => ['en', 'Forbidden', 'This account has been banned. Please contact support.'],
        'id' => ['id', 'Terlarang', 'Akun ini telah diblokir. Silakan hubungi dukungan.'],
    ]);

    it('renders a translated validation failure for failed logins', function (string $locale, string $title, string $detailFragment): void {
        $response = $this->withHeader('Accept-Language', $locale)
            ->postJson('/api/v1/auth/login', [
                'email' => 'ghost@example.com',
                'password' => 'password123',
            ]);

        $response->assertUnprocessable()
            ->assertJsonPath('title', $title)
            ->assertJsonPath(
                'detail',
                static fn (mixed $detail): bool => is_string($detail) && str_contains($detail, $detailFragment),
            );
    })->with([
        'en' => ['en', 'Validation Failed', 'Invalid email or password. Please try again.'],
        'id' => ['id', 'Validasi gagal', 'Email atau kata sandi tidak valid. Silakan coba lagi.'],
    ]);

});
