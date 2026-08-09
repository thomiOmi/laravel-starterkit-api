<?php

return [

    // Laravel internal — do not rename
    'failed' => 'Email atau kata sandi tidak valid. Silakan coba lagi.',
    'password' => 'Kata sandi yang diberikan salah.',
    'throttle' => 'Terlalu banyak upaya masuk. Silahkan coba lagi dalam :seconds detik.',

    // Auth flow
    'register_success' => 'Pendaftaran berhasil.',
    'login_success' => 'Berhasil masuk.',
    'logout_success' => 'Berhasil keluar.',
    'password_invalid' => 'Kata sandi yang diberikan tidak cocok dengan kata sandi Anda saat ini.',
    'password_reset_link_sent' => 'Kami telah mengirimkan tautan atur ulang kata sandi ke email Anda.',
    'password_reset_success' => 'Kata sandi Anda telah diatur ulang.',
    'password_updated' => 'Kata sandi Anda telah diperbarui.',
    'password_expired' => 'Kata sandi Anda telah kedaluwarsa. Silakan perbarui kata sandi Anda.',
    'profile_updated' => 'Profil Anda telah diperbarui.',
    'device_logout_success' => 'Berhasil keluar dari perangkat.',
    'other_devices_logout_success' => 'Berhasil keluar dari perangkat lainnya.',
    'social_login_success' => 'Login sosial berhasil.',
    'social_link_success' => 'Akun sosial berhasil ditautkan.',
    'social_unlink_success' => 'Akun sosial berhasil dilepas.',
    'social_unlink_blocked' => 'Anda tidak dapat melepas metode masuk terakhir tanpa kata sandi.',
    'social_denied' => 'Anda telah menolak permintaan otorisasi.',
    'email_change_verify' => 'Email berhasil diubah. Silakan verifikasi alamat email baru Anda.',
    'email_verified' => 'Email berhasil diverifikasi.',
    'email_verification_sent' => 'Tautan verifikasi baru telah dikirim ke alamat email Anda.',
    'email_not_verified' => 'Email Belum Diverifikasi',
    'email_verify_required' => 'Silakan verifikasi alamat email Anda sebelum mengakses sumber daya ini.',
    'email_verify_subject' => 'Verifikasi Alamat Email',
    'email_verify_line' => 'Klik tombol di bawah untuk memverifikasi alamat email Anda.',
    'email_verify_action' => 'Verifikasi Alamat Email',
    'email_verify_footer' => 'Jika Anda tidak membuat akun, tidak diperlukan tindakan lebih lanjut.',
    'password_reset_subject' => 'Atur Ulang Kata Sandi',
    'password_reset_line' => 'Anda menerima email ini karena kami menerima permintaan atur ulang kata sandi untuk akun Anda.',
    'password_reset_action' => 'Atur Ulang Kata Sandi',
    'password_reset_expire' => 'Tautan atur ulang kata sandi ini akan kedaluwarsa dalam :count menit.',
    'password_reset_footer' => 'Jika Anda tidak meminta atur ulang kata sandi, tidak diperlukan tindakan lebih lanjut.',
    'account_banned' => 'Akun ini telah diblokir. Silakan hubungi dukungan.',
    'account_suspended' => 'Akun ini sedang ditangguhkan. Silakan hubungi dukungan.',
    'account_inactive' => 'Akun ini tidak aktif. Silakan hubungi dukungan.',

    // HTTP status titles (RFC 9457 Problem Details)
    'http_unauthorized' => 'Tidak terautentikasi',
    'unauthenticated' => 'Anda harus terautentikasi untuk mengakses sumber daya ini.',
    'http_bad_request' => 'Permintaan Buruk',
    'http_forbidden' => 'Terlarang',
    'http_not_found' => 'Tidak ditemukan',
    'http_validation_failed' => 'Validasi gagal',
    'http_too_many_requests' => 'Terlalu banyak permintaan',
    'http_internal_error' => 'Kesalahan Server Internal',
    'http_gone' => 'Hilang',

    // Detail HTTP status (fallback untuk ProblemResponse)
    'validation_failed' => 'Data yang diberikan tidak valid.',
    'invalid_signature' => 'Tanda tangan permintaan tidak valid atau telah kedaluwarsa.',
    'access_denied' => 'Anda tidak diizinkan melakukan tindakan ini.',
    'not_found_detail' => 'URL yang diminta tidak ditemukan.',
    'rate_limited_detail' => 'Anda telah melampaui batas permintaan. Silakan coba lagi nanti.',
    'bad_request_detail' => 'Permintaan tidak dapat dipahami oleh server karena sintaksis yang salah.',
    'internal_error_detail' => 'Terjadi kesalahan server internal.',
];
