# Event-Driven Architecture & Background Processing

Proyek ini menerapkan pola *Event-Driven* untuk memisahkan logika utama dengan proses sekunder, guna meningkatkan skalabilitas dan responsivitas API.

## 1. Alur Registrasi User

Saat user mendaftar melalui `RegisterController`, proses tidak langsung mengirimkan email verifikasi. Sebagai gantinya, sistem memicu sebuah event.

**Alur Kerja:**
1. `RegisterUser` action membuat data user di database.
2. Action memicu event `App\Events\UserRegistered`.
3. Listener `Modules\Auth\Listeners\SendEmailVerificationNotification` menangkap event tersebut.
4. Listener mengirimkan notifikasi `App\Notifications\VerifyEmail`.

## 2. Background Processing (Queues)

Notifikasi `VerifyEmail` telah dikonfigurasi untuk mengimplementasikan interface `ShouldQueue`. Ini berarti proses pengiriman email akan dipindahkan ke antrean latar belakang (Background Queue) dan tidak akan membebani waktu respon request pendaftaran.

### Cara Kerja:
- Saat notifikasi dikirim, Laravel akan memasukkannya ke dalam tabel `jobs` (atau driver queue lain yang dikonfigurasi).
- Worker queue (`php artisan queue:work`) akan memproses antrean tersebut secara asinkron.

## 3. Menambahkan Event Baru

Untuk menambahkan pola serupa pada modul lain:

1. Buat kelas Event di `app/Events`.
2. Buat kelas Listener di folder `Listeners` modul terkait.
3. Daftarkan pemetaan Event dan Listener di `app/Providers/EventServiceProvider` atau melalui `Event::listen()` di Service Provider modul.

## 4. Keuntungan Skalabilitas
Dengan pola ini, kita dapat dengan mudah menambahkan listener baru (misalnya mengirim data ke sistem CRM atau Analytics) tanpa mengubah kode utama pada proses registrasi user.
