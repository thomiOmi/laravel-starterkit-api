<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Language Lines
    |--------------------------------------------------------------------------
    |
    | Framework keys below are consumed by Laravel without a namespace, so
    | they must live in the application lang directory, not in a module.
    |
    */

    'failed' => 'Email atau kata sandi tidak valid. Silakan coba lagi.',
    'password' => 'Kata sandi yang diberikan salah.',
    'throttle' => 'Terlalu banyak upaya masuk. Silahkan coba lagi dalam :seconds detik.',

    // Emails sent by the application.
    'email_verify_subject' => 'Verifikasi Alamat Email',
    'email_verify_line' => 'Klik tombol di bawah untuk memverifikasi alamat email Anda.',
    'email_verify_action' => 'Verifikasi Alamat Email',
    'email_verify_footer' => 'Jika Anda tidak membuat akun, tidak diperlukan tindakan lebih lanjut.',
    'password_reset_subject' => 'Atur Ulang Kata Sandi',
    'password_reset_line' => 'Anda menerima email ini karena kami menerima permintaan atur ulang kata sandi untuk akun Anda.',
    'password_reset_action' => 'Atur Ulang Kata Sandi',
    'password_reset_expire' => 'Tautan atur ulang kata sandi ini akan kedaluwarsa dalam :count menit.',
    'password_reset_footer' => 'Jika Anda tidak meminta atur ulang kata sandi, tidak diperlukan tindakan lebih lanjut.',

    // Middleware messages.
    'email_verify_required' => 'Silakan verifikasi alamat email Anda sebelum mengakses sumber daya ini.',
    'unauthenticated' => 'Anda harus terautentikasi untuk mengakses sumber daya ini.',
    'account_banned' => 'Akun ini telah diblokir. Silakan hubungi dukungan.',
    'account_suspended' => 'Akun ini sedang ditangguhkan. Silakan hubungi dukungan.',
    'account_inactive' => 'Akun ini tidak aktif. Silakan hubungi dukungan.',

    // HTTP status titles (RFC 9457 Problem Details).
    'http_unauthorized' => 'Tidak terautentikasi',
    'http_bad_request' => 'Permintaan Buruk',
    'http_forbidden' => 'Terlarang',
    'http_not_found' => 'Tidak ditemukan',
    'http_validation_failed' => 'Validasi gagal',
    'http_too_many_requests' => 'Terlalu banyak permintaan',
    'http_internal_error' => 'Kesalahan Server Internal',
    'http_gone' => 'Hilang',
    'http_conflict' => 'Konflik',

    // HTTP status detail (fallback for ProblemResponse).
    'validation_failed' => 'Data yang diberikan tidak valid.',
    'invalid_signature' => 'Tanda tangan permintaan tidak valid atau telah kedaluwarsa.',
    'access_denied' => 'Anda tidak diizinkan melakukan tindakan ini.',
    'not_found_detail' => 'URL yang diminta tidak ditemukan.',
    'rate_limited_detail' => 'Anda telah melampaui batas permintaan. Silakan coba lagi nanti.',
    'bad_request_detail' => 'Permintaan tidak dapat dipahami oleh server karena sintaksis yang salah.',
    'conflict_detail' => 'Permintaan bertentangan dengan status server saat ini.',
    'internal_error_detail' => 'Terjadi kesalahan server internal.',

];
