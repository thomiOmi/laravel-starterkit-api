# Arsitektur Modularization: Laravel Starterkit API

Status: Final. Dokumen ini adalah source of truth untuk struktur modul, aturan, dan keputusan arsitektur. Semua keputusan dijelaskan langsung di sini. `docs/architecture.md` (versi restrukturisasi, English) dan `.ai/rules/` adalah turunan yang wajib sinkron.

## 1. Prinsip Desain

Tujuh prinsip golden yang menjadi dasar seluruh struktur modul. Semua aturan di dokumen ini harus konsisten dengan prinsip berikut.

### 1.1 Modul me-mirror struktur app (mirror principle)

Struktur folder modul mencerminkan struktur `app/` bawaan Laravel, termasuk folder kontainer `Http/` (Controllers, Middleware, Requests, Resources) dan `Console/` (Commands) yang menampung layer HTTP/CLI persis seperti `app/Http` dan `app/Console`. Adopter yang sudah mengenal layout Laravel langsung paham modul tanpa dokumentasi tambahan. Hal yang module-scoped tinggal di dalam modul; hal yang shared tinggal di `app/`. Sumber inspirasi: Nuxt Layers ("the layers structure is almost identical to a standard Nuxt application"), struktur package Laravel resmi, dan nWidart/laravel-modules.

### 1.2 Aktivasi via config, tanpa env (config-driven activation)

Kapabilitas diaktifkan lewat status operasional, bukan environment variable. Aktivasi modul dikelola `nwidart/laravel-modules` (FileActivator): status live di `modules_statuses.json` (mis. `{"iam": true}`), dan modul hanya di-boot saat entry-nya `true`. Tidak ada auto-discovery tanpa status; modul yang tidak aktif sepenuhnya inert. Sumber inspirasi: nWidart/laravel-modules (keputusan ini merekam ADR-0029).

### 1.3 Actions single-responsibility, di-wire oleh provider

Logika bisnis adalah kelas action yang melakukan satu operasi bisnis. Service provider bertugas me-wire: mendaftarkan config, action, dan routes ke framework. Setiap modul punya satu provider (`modules/{Module}/app/Providers/{Module}ServiceProvider.php`) yang extends base `Nwidart\Modules\Support\ModuleServiceProvider`; nWidart yang me-load provider modul AKTIF. Sumber inspirasi: Laravel Fortify (`app/Actions/Fortify/CreateNewUser.php`), nWidart/laravel-modules (service provider per modul).

### 1.4 Modul self-contained

Migrations, factories, seeders, routes, request, resource, dan tests hidup di dalam modul. Modul bisa dipindah, dihapus, atau diaktifkan tanpa menyentuh file di luar modul (kecuali status aktivasi di `modules_statuses.json`). Sumber inspirasi: nWidart/laravel-modules.

### 1.5 Shared vocabulary terpisah di app

Kontrak yang dipakai lintas modul (interface, enum shared, response contract) tinggal di `app/`. Modul tidak saling import langsung; komunikasi lewat kontrak. Sumber inspirasi: `shared/` di Nuxt Layers.

### 1.6 Metadata modul minimal

Setiap modul membawa metadata nWidart: `composer.json` + `module.json` (deviasi dari Laravel app skeleton). Satu repo, satu dependency graph, satu build; modul TIDAK punya `resources/assets` atau `vite.config.js` sendiri. Filosofi: production-ready, not overengineered.

### 1.7 Native-first escape hatch

Setiap customisasi harus mempertahankan jalur native Laravel. Adopter selalu bisa melewatkan wrapper dan memakai API bawaan. Customisasi mempermudah, tidak memblokir.

## 2. Anatomi Modul

### 2.1 Struktur folder proyek

```
project/
├── app/                         # Kode shared (shared vocabulary)
│   ├── Builders/                # BaseQueryBuilder (filter/sort/search/include whitelist)
│   ├── Concerns/                # Trait shared (HasDefaultBehavior, FormatDate, dll.)
│   ├── Console/
│   │   └── Commands/            # Artisan command global (security:check, dll.)
│   ├── Contracts/               # Interface lintas modul (Identity)
│   ├── Enums/                   # Enum shared vocab lintas modul (RoleEnum, PermissionEnum)
│   ├── Events/                  # Event shared lintas modul (module A dispatch, module B listen)
│   ├── Features/                # Pennant class-based feature, dipakai 2+ modul
│   ├── Http/
│   │   ├── Controllers/         # Base Controller
│   │   ├── Middleware/          # Global: Sunset, TraceId, SetLocale, SecurityHeaders, Idempotency
│   │   ├── Requests/            # Request umum (PaginationRequest, BulkActionRequest)
│   │   └── Responses/           # SuccessResponse, ProblemResponse (RFC 9457)
│   ├── Jobs/                    # Queue job shared lintas modul
│   ├── Models/                  # Model shared (Sanctum PersonalAccessToken)
│   ├── Notifications/           # Notifikasi shared (VerifyEmail, ResetPassword)
│   ├── Payloads/                # DTO shared (IdempotencyPayload)
│   ├── Providers/               # AppServiceProvider, RouteServiceProvider
│   └── Support/                 # Util teknis global (ProductionSecurityCheck)
├── config/                      # Config global (modules.php = config nWidart)
├── database/
│   ├── factories/               # Factory shared
│   ├── migrations/              # Migration shared
│   └── seeders/                 # Seeder shared
├── modules/
│   └── {Module}/                # Satu modul (folder TitleCase, alias lowercase)
│       ├── app/                 # Mirror skeleton app/ Laravel
│       │   ├── Http/            # Mirror app/Http: semua layer HTTP di sini
│       │   │   ├── Controllers/ # V1/, V2/ untuk API versioning (invokable single-action)
│       │   │   ├── Middleware/  # Middleware module-specific
│       │   │   ├── Requests/    # V1/ (FormRequest validation)
│       │   │   └── Resources/   # API resource transformer
│       │   ├── Console/
│       │   │   └── Commands/    # Artisan command module-specific
│       │   ├── Exceptions/      # Exception class module-specific
│       │   ├── Features/        # Pennant class-based feature module-specific (runtime flag)
│       │   ├── Jobs/            # Queue job module-specific
│       │   ├── Mail/            # Mail module-specific
│       │   ├── Rules/           # Custom validation rule module-specific
│       │   ├── Events/          # Event module-specific
│       │   ├── Listeners/       # Listener module-specific
│       │   ├── Lang/            # {locale}/ (translasi modul, dimuat saat aktif)
│       │   ├── Models/          # Model Eloquent modul
│       │   │   └── Scopes/      # Global scope (didaftarkan #[ScopedBy])
│       │   ├── Observers/       # Observer model (didaftarkan #[ObservedBy])
│       │   ├── Policies/        # Policy authorization modul (didaftarkan #[UsePolicy])
│       │   ├── Providers/       # (wajib) {Module}ServiceProvider extends nWidart base + RouteServiceProvider
│       │   ├── Notifications/   # Notifikasi module-specific
│       │   ├── Actions/         # Kit-specific: satu operasi bisnis, final readonly, handle()
│       │   ├── Builders/        # Kit-specific: query builder, extends BaseQueryBuilder
│       │   ├── Services/        # Kit-specific: logika lintas use-case
│       │   ├── Payloads/        # Kit-specific: DTO input action
│       │   ├── Support/         # Kit-specific: util teknis murni
│       │   ├── Contracts/       # Kit-specific: kontrak module-specific
│       │   └── Enums/           # Kit-specific: enum module-specific
│       ├── config/              # Kit-specific: config.php (di-merge nWidart base provider)
│       ├── database/            # Kit-specific: migrations, factories, seeders (lowercase)
│       ├── routes/              # (wajib) V1.php, V2.php (dimuat RouteServiceProvider modul)
│       ├── tests/               # (wajib) Feature dan unit test modul (lowercase)
│       ├── composer.json        # Metadata package modul (nWidart)
│       └── module.json          # Manifest modul nWidart (name, priority, providers)
├── routes/
│   ├── api.php                  # Reserved; routes API didaftarkan modul (routes/V1.php)
│   ├── console.php              # Route console
│   └── web.php                  # Route web
└── tests/                       # Test app global
    ├── Architecture/            # Architecture tests (konvensi)
    ├── Feature/                 # Test infrastruktur (middleware, responses, dll.)
    ├── Unit/                    # Test unit app
    └── Helpers.php              # Seam akses model modul (bukan import langsung)
```

Prinsip mirror (1.1): `modules/{Module}/app/` adalah mirror skeleton `app/` bawaan Laravel; folder kontainer `Http/` dan `Console/` menampung layer HTTP/CLI persis seperti `app/Http` dan `app/Console`. Folder wajib pada modul AKTIF: `app/Providers`, `routes`, `tests`; sisanya optional dan dibuat saat dibutuhkan. Layer kit-specific tanpa padanan di skeleton: Actions, Services, Payloads, Builders, Features, Config, Routes, Database, Tests, Lang. `app/Http/Responses` adalah kontrak global dan tidak dimirror ke modul.

### 2.2 Folder matrix

Folder required (wajib ada pada modul AKTIF):

| Folder | Isi |
|---|---|
| app/Providers | {Module}ServiceProvider extends nWidart base ModuleServiceProvider + RouteServiceProvider |
| routes | File route (V1.php) |
| tests | Feature dan unit test |

Folder optional (hanya dibuat jika berisi minimal 1 file, dilarang folder kosong):

| Folder | Isi |
|---|---|
| app/Http | Controllers, Middleware, Requests, Resources (mirror app/Http) |
| app/Console | Commands (mirror app/Console) |
| app/Exceptions | Exception class module-specific |
| app/Features | Pennant class-based feature (runtime flag) |
| app/Jobs | Queue job module-specific |
| app/Mail | Mail module-specific |
| app/Rules | Custom validation rule module-specific |
| app/Events | Event module-specific |
| app/Listeners | Listener module-specific |
| app/Lang | Translasi modul ({locale}/) |
| app/Models | Model Eloquent |
| app/Observers | Observer model (via #[ObservedBy]) |
| app/Policies | Policy authorization (via #[UsePolicy]) |
| app/Models/Scopes | Global scope (via #[ScopedBy]) |
| app/Notifications | Notifikasi module-specific |
| app/Actions | Logika bisnis, satu operasi per kelas |
| app/Builders | Query builder (extends BaseQueryBuilder) |
| app/Services | Logika lintas use-case |
| app/Payloads | DTO input action |
| app/Support | Util teknis murni |
| app/Contracts | Kontrak module-specific |
| app/Enums | Enum module-specific |
| config | config.php (di-merge nWidart base provider) |
| database | migrations, factories, seeders (lowercase) |

Modul non-aktif (tidak enabled di `modules_statuses.json`) minimal berisi `app/Providers`, `tests`. Contoh: Organization. Sisa struktur muncul saat modul diaktifkan.

### 2.3 Sumber inspirasi dan deviasi

| Aspek | Nuxt Layers | Laravel Fortify | nWidart/laravel-modules | Keputusan kit |
|---|---|---|---|---|
| Struktur modul | Mirror app standar | Paket terstruktur | app/ + resources + vite | Mirror app/ (Http/ kontainer), root lowercase |
| Aktivasi | Config layer | Config features array | module.json + FileActivator | Status live modules_statuses.json (ADR-0029) |
| Metadata modul | nuxt.config | - | module.json di dalam modul | module.json + composer.json di dalam modul |
| Feature toggle | - | features array | - | Config modul (build-time) + Pennant class (runtime) |
| Logika bisnis | Composables/utils | Actions | Service classes | Actions + Services |
| Resource DB | - | Migrations via publish | Migrations/factories/seeders di modul | Di dalam modul |
| Overhead per modul | nuxt.config | composer package | composer.json + module.json | module.json + composer.json |
| Shared code | shared/ | Vendor namespace | Modules namespace | app/ (shared vocabulary) |
| Repositories | - | - | Repositories layer | TIDAK dipakai (Eloquent adalah repository) |

## 3. Tanggung Jawab Layer

Setiap layer: definisi, aturan, larangan, contoh.

### 3.1 Actions

Definisi: kelas `final readonly` yang melakukan SATU operasi bisnis, dipanggil oleh controller, dipanggil action lain, atau dipakai service. Sumber inspirasi: Fortify Actions.

Aturan:
1. `final readonly`, satu method publik `handle()`, parameter bertipe eksplisit
2. TIDAK menerima `Request`; controller mengekstrak data dan meneruskannya
3. TIDAK menulis logika HTTP (status code, redirect, json)
4. Validasi terjadi di Request layer, bukan di action
5. Setiap action punya unit test di `modules/*/tests/Unit`
6. Error bisnis via `throw_if`/`throw_unless` + exception domain (`InvalidArgumentException` dipetakan ke 422, `ModelNotFoundException` ke 404 untuk ownership check)
7. Write multi-langkah yang saling terkait (2+ write) wajib dibungkus `DB::transaction` atau setara (`saveOrFail`/`deleteOrFail` untuk instance, `syncOrFail`/`attachOrFail` dst untuk pivot); single-model write pakai `create`/`update`/`save`/`delete` biasa
8. TIDAK ada base class/interface untuk Action: struktur (`final readonly`, `handle()`) adalah konvensi yang di-enforce ArchitectureTest, bukan inheritance (prinsip 1.6 tanpa overhead per modul); interface hanya bila butuh polimorfisme lintas modul nyata (lihat 3.14)

Larangan:
- Dilarang method selain `handle()` yang publik
- Dilarang dependency HTTP (Request, Response)
- Dilarang query Eloquent dengan kondisi domain inline di controller; query ada di action atau builder. Query murni (paginate + filter/search/sort whitelist BaseQueryBuilder, tanpa kondisi domain) dibolehkan langsung di controller (lihat 3.2 aturan 3)
- Dilarang helper HTTP (`abort`, `abort_if`, `abort_unless`) di action
- Dilarang `createOrFail` (tidak ada di framework) dan `updateOrFail`/`deleteOrFail` untuk lookup (return false diam-diam saat model tidak exists)

Contoh:

```php
final readonly class CreateUserAction
{
    public function handle(UserPayload $payload): User
    {
        return User::create($payload->toArray());
    }
}
```

### 3.2 Controllers

Definisi: kelas `final readonly` invokable single-action di `modules/{Module}/app/Http/Controllers/V1/`. Hanya menangani urusan HTTP: parse request, panggil action, kembalikan response.

Aturan:
1. `final readonly`, extend base `Controller`, satu method `__invoke(Request|FormRequest $request): SuccessResponse`; parameter boleh type-hint FormRequest subclass (contoh: `RegisterController`); error tidak dikembalikan controller, tapi dilempar sebagai exception yang dipetakan handler ke `ProblemResponse` (3.23)
2. Delegasi logika ke Action via `->handle()`
3. Query murni (paginate + filter/search/sort whitelist BaseQueryBuilder, tanpa kondisi domain) boleh langsung di controller
4. Return type-hint `SuccessResponse` (semua controller existing konsisten, 0 penggunaan `JsonResponse`); `ProblemResponse` hanya ditulis handler
5. Ikuti struktur sibling controller yang ada

Larangan:
- Dilarang query dengan kondisi domain di controller (wajib lewat Action)
- Dilarang business logic
- Dilarang response non-kontrak

Contoh:

```php
final readonly class RegisterController extends Controller
{
    public function __invoke(RegisterRequest $request): SuccessResponse
    {
        $user = (new CreateUserAction)->handle(UserRegistrationPayload::fromRequest($request));

        return new SuccessResponse(
            data: UserResource::make($user),
            status: Response::HTTP_CREATED,
        );
    }
}
```

### 3.3 Models

Definisi: model Eloquent di `modules/{Module}/app/Models/`. Data access milik modul.

Aturan:
1. ULID primary key via `HasDefaultBehavior` (HasUlids + serializeDate Y-m-d H:i:s)
2. Atribut via PHP 8 attributes: `#[Fillable]`, `#[Hidden]`, `#[UseFactory]`, `#[UseEloquentBuilder]`
3. Registrasi terkait model via attribute: `#[UsePolicy]` (policy), `#[ObservedBy]` (observer), `#[ScopedBy]` (global scope)
4. `#[Table]`, `#[UseResource]`, `#[UseResourceCollection]` hanya untuk deviasi konvensi (table non-baku, pivot, naming resource non-baku)
5. Cast enum column ke enum class (`'status' => StatusEnum::class`)
6. `declare(strict_types=1)` di setiap file
7. Factory wajib ada untuk tiap model
8. App-layer (tests/) mengakses model modul hanya lewat seam `tests/Helpers.php`, bukan import langsung
9. Soft delete memakai trait `Illuminate\Database\Eloquent\SoftDeletes` (attribute `#[UseSoftDeletes]` tidak ada di Laravel 13); query `withTrashed`/`onlyTrashed` hanya di action/builder

Larangan:
- Dilarang UUID primary key
- Dilarang `$fillable`/`$hidden` properties
- Dilarang model lintas modul

### 3.4 Services

Definisi: logika bisnis yang dipakai 2+ call-site atau menyatukan flow kompleks lintas use-case. Pembeda dari Action: Action = 1 use-case; Service = logika bersama.

Aturan:
1. `final readonly`, dependencies di-inject via constructor
2. TIDAK menerima `Request`
3. Dapat memanggil Actions dan model
4. Minimal 2 call-site atau flow kompleks; 1 call-site harusnya Action

Larangan:
- Dilarang service untuk 1 call-site
- Dilarang service memanggil controller/HTTP layer

Contoh: `UserAuthorizationService` (menentukan token abilities dan membuat access token, dipakai login dan register).

### 3.5 Support

Definisi: util teknis murni, self-contained, tanpa state bisnis dan tanpa dependency Eloquent.

Aturan:
1. Statis atau `final readonly`, murni teknis (crypt, format, validasi teknis)
2. Jika punya logika bisnis, itu Services; jika 1 use-case, itu Action
3. Tidak dipanggil langsung dari controller (via Service/Action)

Larangan:
- Dilarang dependency Eloquent
- Dilarang logika bisnis domain

Contoh: `SocialState` (membuat dan memverifikasi state token OAuth dengan expiry).

### 3.6 Builders

Definisi: custom query builder Eloquent yang didaftarkan via `#[UseEloquentBuilder]`.

Aturan:
1. `BaseQueryBuilder` adalah satu-satunya mekanisme filter, search, sort, include whitelist
2. Method whitelist: `allowedSearch`, `allowedFilters`, `allowedSorts`, `allowedFields`, `allowedIncludes`
3. Model mendaftar builder via attribute, bukan `newBuilder()`
4. Native Eloquent (`where`, `orderBy`, scopes) tetap sah dipakai di action/builder

Larangan:
- Dilarang parsing query string di controller
- Dilarang melewati whitelist dengan parameter acak

Contoh:

```php
User::query()
    ->with(['roles'])
    ->allowedSearch()
    ->allowedFilters()
    ->allowedSorts()
    ->allowedFields()
    ->allowedIncludes()
    ->paginate();
```

### 3.7 Payloads

Definisi: DTO immutable `final readonly` dengan constructor promotion, input untuk action.

Aturan:
1. `final readonly`, property typed, constructor promotion
2. Validasi tetap di Request; Payload tidak memvalidasi
3. Dipakai untuk data lintas lapisan (Request ke Action, queue job, CLI)

Larangan:
- Dilarang payload dengan logika validasi
- Dilarang payload mutable

### 3.8 Requests

Definisi: FormRequest per endpoint di `modules/{Module}/app/Http/Requests/V1/`. Request lintas modul (pagination, bulk action) hidup di `app/Http/Requests/` (shared).

Aturan:
1. Satu FormRequest per endpoint/aksi; pengecualian hanya request shared di `app/Http/Requests/` (`PaginationRequest`, `BulkActionRequest`) yang dipakai lintas endpoint
2. Validasi di method `rules()`; otorisasi via `authorize()` atau policy/permission
3. Dilarang validasi inline di controller
4. Endpoint list wajib type-hint `{Resource}ListRequest` di modul yang extends `App\Http\Requests\PaginationRequest` (bukan PaginationRequest langsung): tempat `authorize()` permission dan rule tambahan untuk filter/sort/search; subclass kosong dibolehkan bila hanya butuh pagination (pola existing: `UserListRequest`, `RoleListRequest`, `PermissionListRequest`, `DeviceListRequest` di `modules/IAM/Requests/V1/`)
5. Naming request mengikuti controller: `{Resource}ListRequest` untuk `{Resource}ListController`

Larangan:
- Dilarang validasi array panjang di controller
- Dilarang Request memanggil model langsung
- Dilarang controller list type-hint `PaginationRequest` langsung dari app

### 3.9 Resources

Definisi: API resource transformer di `modules/{Module}/app/Http/Resources/`.

Aturan:
1. `extends JsonResource`, envelope kontrak via SuccessResponse
2. Format tanggal `Y-m-d H:i:s`
3. Resource milik modul; app-wide shape global

Larangan:
- Dilarang resource mengubah struktur envelope global

### 3.10 Policies

Definisi: policy authorization per modul, didaftarkan via `#[UsePolicy]` pada model (satu sumber kebenaran, tanpa registrasi tersembunyi di provider); `Gate::policy` manual di provider TIDAK dipakai untuk modul.

Aturan:
1. Satu policy per model bila ada otorisasi resource
2. Registrasi via attribute `#[UsePolicy(Policy::class)]` di model
3. Gunakan Spatie permission di dalam policy

Larangan:
- Dilarang `Gate::policy` di service provider modul
- Dilarang otorisasi tersembunyi di dalam controller
- Dilarang dua sumber kebenaran sekaligus (Spatie permission ATAU Sanctum abilities, pilih satu per route)

### 3.11 Providers

Definisi: `modules/{Module}/app/Providers/{Module}ServiceProvider.php` yang me-wire modul ke framework. Setiap provider modul extends base `Nwidart\Modules\Support\ModuleServiceProvider`; nWidart me-load provider modul AKTIF dari `module.json` + status di `modules_statuses.json`.

Aturan:
1. Base nWidart menyediakan boilerplate loading: merge `config/config.php`, load `database/migrations` + `database/factories`, register provider dari array `$providers` (EventServiceProvider, RouteServiceProvider)
2. Provider modul hanya deklarasi: `$name`, `$nameLower`, array `$providers`, dan hook `boot()` untuk middleware alias, Pennant features, binding (policy via `#[UsePolicy]` di model)
3. Aktivasi modul via FileActivator (status live di `modules_statuses.json`); modul non-enabled = provider tidak pernah di-boot
4. Tanpa registrasi tersembunyi; middleware alias didaftarkan eksplisit, bukan magic discovery
5. Alias modul = lowercase nama modul (`'Media'` ke `'media'`); alias dipakai untuk key config (`config('media.*')`) dan route name prefix (`api.v1.media.`, 3.18)

Larangan:
- Dilarang provider modul extends `Illuminate\Support\ServiceProvider` langsung (harus nWidart base `ModuleServiceProvider`)
- Dilarang provider mendaftarkan routes di luar `routes/` modul (route dimuat RouteServiceProvider modul sendiri)
- Dilarang env() di provider

### 3.12 Middleware

Definisi: middleware module-specific di `modules/{Module}/app/Http/Middleware/`; middleware global di `app/Http/Middleware/`.

Aturan:
1. Middleware yang hanya dipakai route modul tertentu tinggal di modul
2. Middleware global (auth, throttle, security headers) di app
3. Alias middleware didaftarkan eksplisit, bukan magic discovery

Larangan:
- Dilarang middleware global di dalam modul
- Dilarang middleware tanpa alias

### 3.13 Enums

Definisi: enum khusus modul di `modules/{Module}/app/Enums/`; enum shared vocab (dipakai 2+ modul) di `app/Enums/`.

Aturan:
1. 1 call-site modul saja: di modul. 2+ modul: di app
2. Nilai TitleCase; label native via method (tanpa dependency library label)
3. Cast model ke enum class

Larangan:
- Dilarang enum module-specific tinggal di app/Enums
- Dilarang enum shared tinggal di modul

### 3.14 Contracts

Definisi: kontrak modul di `modules/{Module}/app/Contracts/`; kontrak lintas modul (shared vocabulary) di `app/Contracts/`.

Aturan:
1. Modul berkomunikasi lewat kontrak atau public API seam, bukan import kelas internal modul lain
2. `app/Contracts` hanya untuk interface yang dipakai 2+ modul atau core
3. Model Eloquent dan contract = public API seam modul: boleh diimport modul lain secara langsung (contoh existing: modul Media import `Modules\IAM\Models\User`, `Role`, `Permission`); kelas internal (Actions, Services, Payloads, Support, Builders, Enums) dilarang

Larangan:
- Dilarang import kelas internal antar modul (Actions, Services, Payloads, Support, Builders, Enums); import model + contract dibolehkan (public API seam, aturan 3)

Mekanisme komunikasi antar modul (4 jalur, dari yang paling disukai):

1. Shared vocabulary di `app/`: enum shared, contract, shared request, response contract dipakai 2+ modul tanpa import lintas modul
2. Public API seam: model + contract modul lain boleh diimport langsung - data + relasi Eloquent (contoh: `Media::uploadedBy()` import `Modules\IAM\Models\User`), otorisasi (`MediaPolicy` type-hint `User` + `App\Enums\PermissionEnum`), seeder (`MediaSeeder` firstOrCreate `Role`/`Permission` IAM)
3. Kontrak untuk perilaku lintas modul: interface di `app/Contracts/` yang diimplementasikan modul pemilik dan di-binding provider (contoh: `Identity` mengabstraksi actor auth)
4. Event pub/sub untuk decoupling longgar: shared event class di `app/Events/` (module A dispatch), listener di modul pendengar didaftarkan eksplisit di `boot()` (3.21); listener global di `app/Listeners` ter-auto-discovery

Kapan model langsung vs interface:

- Data + relasi Eloquent: model langsung (Eloquent butuh class konkret, `belongsTo(User::class)`); interface tidak bisa dipakai untuk relasi
- Perilaku/decoupling/2+ implementasi mungkin: interface di `app/Contracts/` (contoh: `Identity`)
- 1 implementasi pasti dan tidak akan 2+: cukup model langsung; interface = YAGNI

Rule of thumb base class/interface per layer: hanya jika (1) ada logika yang dieksekusi bersama, (2) butuh polimorfisme/decoupling nyata, (3) kontrak lintas modul, (4) container binding. Dilarang demi "konsistensi" belaka; konvensi struktur di-enforce ArchitectureTest, bukan inheritance.

### 3.15 Config

Definisi: config global di `config/`; config modul di `modules/{Module}/config/config.php` (di-merge nWidart menggunakan nama modul TitleCase, diakses via alias lowercase `config('iam.*')`).

Aturan:
1. Config modul di-merge provider saat modul aktif
2. Akses config via helper typed (`config()->integer(...)`) agar tipe terjaga
3. Features array ala Fortify (lihat section 6)

Larangan:
- Dilarang env() di luar config files
- Dilarang config modul dimuat saat modul non-aktif

### 3.16 Notifications

Definisi: notifikasi di `app/Notifications/` (global) atau `modules/{Module}/app/Notifications/` (module-specific).

Aturan:
1. Queue-able, via `ShouldQueue`
2. Naming deskriptif (VerifyEmail, ResetPassword)

Larangan:
- Dilarang notifikasi dipanggil langsung di controller (via action/service)

### 3.17 Commands

Definisi: Artisan command di `app/Console/Commands/` (global) atau `modules/{Module}/app/Console/Commands/` (module-specific).

Aturan:
1. PHP 8 attributes: `#[Signature]`, `#[Description]`, `#[Help]`, `#[Usage]`
2. `handle(): int` dengan exit code
3. Command modul didaftarkan dengan menambahkan class command ke array `$commands` pada `{Module}ServiceProvider` (base nWidart `ModuleServiceProvider` mendaftarkan apa yang terdaftar di array itu, tidak auto-discovery folder); command global di `app/Console/Commands` ter-auto-discovery

Larangan:
- Dilarang command tanpa attributes signature

### 3.18 Routes

Definisi: route file modul di `modules/{Module}/routes/V1.php`, dimuat `app/Providers/RouteServiceProvider.php` modul sendiri (extends `Illuminate\Foundation\Support\Providers\RouteServiceProvider`) saat modul aktif; provider mengiterasi `apiroute.supported_versions` (default `['V1']`), meng-guard `file_exists`, dan me-mount `api/{version}` dengan name prefix `api.{version}.{alias}.`.

Aturan:
1. Base prefix `api/v1/{path}` (tanpa segmen modul di URL); route name `api.{version}.{module}.{name}`
2. Middleware eksplisit di route group (auth:sanctum, throttle, permission, feature.flag)
3. Route file hanya dimuat jika modul aktif

Larangan:
- Dilarang registrasi route di luar `routes/` modul
- Dilarang middleware tersembunyi di provider

### 3.19 Database

Definisi: schema modul di `modules/{Module}/database/` (migrations, factories, seeders), dimuat nWidart base `ModuleServiceProvider` saat modul aktif.

Aturan:
1. Enum value sebagai default kolom (`->default(StatusEnum::Pending->value)`)
2. Dilarang chain perintah migration dengan && atau ; (timestamp identik)
3. Factory + Seeder untuk tiap model
4. Perubahan schema = review gate (butuh persetujuan)
5. Seeder modul dieksekusi via `php artisan db:seed --class=Modules\{Module}\Database\Seeders\{Name}Seeder` atau dari `database/seeders/DatabaseSeeder`; dilarang seeder memanggil seeder modul lain (dependensi di-seed berurutan dari caller, contoh: `MediaSeeder` tidak memanggil `IAMSeeder`)
6. Rollback migration modul via `php artisan migrate:rollback --path=modules/{Module}/database/migrations` (tanpa `--path`, rollback hanya batch terakhir global)

Larangan:
- Dilarang edit schema tanpa persetujuan
- Dilarang migration di luar modul

### 3.20 Features

Definisi: feature flag modul. Build-time toggle: array `features` di `config/config.php` modul (di-merge nWidart ke `config('{alias}.features')`). Runtime per-user: Pennant class di `modules/{Module}/app/Features/` (dipakai 2+ modul: `app/Features/`), diperiksa via `FeatureFlagMiddleware`.

Aturan:
1. Build-time: nilai boolean di config modul; di-merge base provider ke `config('{alias}.features')`
2. Runtime: `final class {Feature} extends Feature`, `resolve()` berisi logika per-user
3. Naming: `{module}.{feature}` (mis. `iam.self-registration`)
4. Feature yang tidak terdaftar dianggap off (default false)

Larangan:
- Dilarang env() untuk toggle feature
- Dilarang dua sumber kebenaran (registry vs Pennant untuk hal yang sama)

### 3.21 Layer on-demand (Jobs, Events, Listeners, Mail, Rules, Exceptions, Lang, Observers, Scopes)

Definisi: folder optional yang hidup di modul ketika dibutuhkan, mengikuti konvensi Laravel: Jobs (queue job), Events + Listeners (event bus), Mail (email), Rules (custom validation rule), Exceptions (exception class module-specific), Lang/{locale} (translasi modul), Observers (observer model via `#[ObservedBy]`), Scopes (global scope via `#[ScopedBy]`).

Aturan:
1. Dibuat hanya jika berisi minimal 1 file (dilarang folder kosong)
2. `Lang/` dimuat base nWidart `ModuleServiceProvider` saat modul aktif
3. Aturan detail cukup mengikuti konvensi Laravel; tidak dibuat rule file terpisah per folder
4. Listener modul TIDAK ter-auto-discovery (bootstrap hanya scan `app/Listeners`); daftarkan listener eksplisit di `boot()` provider modul via `Event::listen`/`Event::subscribe`

Larangan:
- Dilarang folder kosong sebagai placeholder

### 3.22 Bulk Action

Definisi: endpoint mutasi massal (delete, restore) yang memproses banyak id sekaligus. Request shared `App\Http\Requests\BulkActionRequest` (validasi `ids` max 50 + `action`); controller delegasi ke Action; Action mengeksekusi satu query bulk.

Aturan:
1. Wajib `BulkActionRequest` (shared) untuk semua endpoint bulk; otorisasi per aksi via `authorize()` berbasis route name
2. Action bulk = satu query `whereIn` (delete/restore), return count
3. `Bus::bulk`/`Bus::batch` TIDAK dipakai untuk mutasi sinkron; hanya untuk per-item processing berat yang butuh queue (belum ada use case; aturan ditambah saat muncul)
4. Routing: `POST /{resource}/bulk/{action}`, route name `api.{version}.{module}.{resource}.bulk.{action}`
5. Catatan: query bulk tidak memicu model events/observer per row (trade-off disengaja)

Larangan:
- Dilarang dispatch job per item untuk delete/restore sederhana
- Dilarang loop query di controller; loop (bila ada) hanya di Action

### 3.23 Error Handling & Exception Helpers

Definisi: error dikomunikasikan via exception dan di-map ke `ProblemResponse` (RFC 9457) oleh handler di `bootstrap/app.php`. Helper Laravel `abort*`/`throw*` dipakai sesuai layer untuk menghindari boilerplate try/catch.

Aturan:
1. HTTP layer (controller, middleware, request): `abort`/`abort_if`/`abort_unless` untuk kondisi HTTP (403, 404, 409); status mengikuti mapping handler
2. Domain layer (Action, Payload, Support): `throw_if`/`throw_unless` + exception domain: `InvalidArgumentException` (dipetakan ke 422), `ModelNotFoundException` (dipetakan ke 404, untuk ownership check), exception kustom di `Exceptions/` bila butuh status/type khusus
3. Mapping exception ke ProblemResponse hanya di handler; controller tidak menulis response error manual
4. Pesan error via translation key `__()`, bukan string hardcode
5. Lookup yang harus ada memakai `findOrFail`/`firstOrFail`/`valueOrFail` (throw ModelNotFoundException ke 404); jangan pakai `updateOrFail`/`deleteOrFail`/`saveOrFail` sebagai pengganti lookup (semuanya return false diam-diam saat model tidak exists)

Larangan:
- Dilarang `abort`/`abort_if`/`abort_unless` di domain layer (Actions, Payloads, Support)
- Dilarang try/catch di controller untuk memetakan error (handler yang menangani)
- Dilarang hardcode pesan error di throw

## 4. Konvensi Lintas-Modul

1. Response contract: `SuccessResponse` / `ProblemResponse` (RFC 9457), tanpa `success` boolean; error type dari `config/errors.php` typeKey
2. Format tanggal semua field response: `Y-m-d H:i:s`
3. `declare(strict_types=1)` di setiap file PHP
4. PHP 8 attributes diutamakan atas properties (model, job, command)
5. Route name `api.{version}.{module}.{name}`; modul lowercase di status aktivasi
6. Kelas operasional: `final readonly`; gunakan constructor property promotion
7. Dokumen (docs, rule, roadmap): ASCII murni, tanpa emoji, tanpa em/en dash, tanpa arrow, pakai hyphen
8. Bahasa kode dan komentar: English

## 5. Siklus Hidup Modul

### 5.1 Membuat modul

```bash
php artisan module:make Blog
```

Generator nWidart (stub kit di `stubs/module-generator/`) membuat struktur: `module.json`, `composer.json`, `config/config.php`, `routes/V1.php`, `app/Providers/*`, `app/Http/Controllers/{Module}Controller.php`, `database/seeders/`, `tests/`. Opsi: `--api`, `--disabled`, `--plain`. Optional layer ditambah saat dibutuhkan (bukan di awal).

### 5.2 Mengaktifkan modul

Status aktivasi di `modules_statuses.json` (FileActivator) dikelola command artisan:

```bash
php artisan module:enable Blog    # atau module:disable
```

`module.json` di dalam modul menyimpan metadata (name, priority, providers). `modules_statuses.json` berisi map alias lowercase ke boolean:

```json
{
  "iam": true,
  "media": true,
  "organization": false
}
```

Modul yang tidak enabled = sepenuhnya inert: provider, config, migrations, routes tidak dimuat (dibuktikan tes). Urutan boot antar modul mengikuti `priority` di `module.json`; default 0.

### 5.3 Menonaktifkan modul

`php artisan module:disable {Module}` (status `false` di `modules_statuses.json`). Data modul tetap di database (migration tidak di-rollback otomatis); schema tetap ada, behavior off.

### 5.4 Modul privat

Folder modul privat disimpan di disk + ditambahkan ke `.gitignore` + tidak di-enable di `modules_statuses.json`. Tidak pernah dikirim ke repo publik.

### 5.5 Kasus khusus: Organization (tenancy)

Organization adalah modul non-aktif minimal (app/Providers, tests) yang membungkus stancl/tenancy (opsi opt-in tenancy). Deviasi deliberate:
- Tenant model memakai UUID (stancl default), deviasi dari aturan ULID, terkurung di modul
- Config `tenancy.php` di dalam modul
- Sisa struktur tumbuh saat modul diaktifkan (MVP 2)

### 5.6 Menghapus modul

`php artisan module:delete {Module} --force` (hapus folder + status di `modules_statuses.json`). Provider tidak di-boot saat modul non-enabled; folder absen tidak fatal. Data database tetap ada (migration tidak auto-rollback).

## 6. Toggle & Native-First

### 6.1 Model 3 level toggle

| Level | Mekanisme | Waktu | Contoh |
|---|---|---|---|
| Module | `modules_statuses.json` (FileActivator, `module:enable`/`disable`) | Build-time | `organization` off = tenancy inert |
| Feature (static) | Array `features` di config modul (ala Fortify) | Build-time | Media: upload vs signedUrl |
| Feature (runtime) | Pennant flags (class di `app/Features/`) + FeatureFlagMiddleware | Runtime, per-user | beta flag, gradual rollout |

### 6.2 Draf code: config modul

```php
// modules/Media/config/config.php
return [
    'name' => 'Media',
    'features' => [
        'upload'     => true,
        'signed-url' => false,
    ],
];
```

Base nWidart `ModuleServiceProvider` meng-merge config ke `config('media.*')` saat boot (termasuk `features`); provider modul tinggal deklarasi + hook:

```php
// modules/Media/app/Providers/MediaServiceProvider.php
final class MediaServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Media';

    protected string $nameLower = 'media';

    public function boot(): void
    {
        parent::boot();

        if (MediaFeatures::enabled(MediaFeatures::signedUrl())) {
            // register signed URL routes or middleware only when enabled
        }
    }
}
```

```php
// modules/Media/app/Support/MediaFeatures.php
final class MediaFeatures
{
    public static function upload(): string
    {
        return 'upload';
    }

    public static function signedUrl(): string
    {
        return 'signed-url';
    }

    public static function enabled(string $feature): bool
    {
        return config()->boolean("media.features.{$feature}", false);
    }
}
```

### 6.3 Pennant class (runtime, per-user)

Feature yang butuh keputusan runtime (per user, gradual rollout) didefinisikan sebagai Pennant class di `modules/{Module}/app/Features/`:

```php
// modules/Media/app/Features/MediaUpload.php
final class MediaUpload extends Feature
{
    public function resolve(User $user): bool
    {
        return $user->hasRole(RoleEnum::SuperAdmin); // keputusan runtime per-user
    }
}
```

Route dilindungi middleware `feature.flag` (FeatureFlagMiddleware). Feature yang dipakai 2+ modul tinggal di `app/Features/`.

Catatan: Pennant class hanya untuk keputusan runtime (per-user, gradual rollout); toggle statis cukup memakai features array di config modul (6.1/6.2) tanpa class Pennant.

### 6.4 Chisel markers

Pola `/* @chisel-{feature} */` dan `/* @end-chisel-{feature} */` (dari vue-starter-kit Laravel) DITUNDA: keputusan menyusul evaluasi `laravel/chisel` (backlog). Tidak diadopsi dulu.

### 6.5 Native-first

Setiap wrapper wajib punya escape hatch native yang terdokumentasi:
- BaseQueryBuilder: action tetap boleh `User::where(...)` biasa
- Responses: handler tetap memetakan exception ke problem details
- Middleware: route boleh tanpa middleware khusus bila tidak perlu
Bukti: tes yang membuktikan jalur native tetap berfungsi.

## 7. Pengujian

1. Placement: tes modul di `modules/*/tests/` (Feature, Unit); tes app di `tests/` (Feature, Unit, Architecture)
2. Struktur folder tes modul: `tests/Feature/` (opsional subfolder `V1/` mirror `app/Http/Controllers/V1`) dan `tests/Unit/`; tes shared: `tests/{Architecture,Feature,Unit}/`, `tests/Datasets/`, `tests/Helpers.php`, `tests/Pest.php`
3. Suite yang didukung saat ini: `unit`, `feature`, `profanity`. Gate coverage, mutation, type-coverage ditangguhkan sementara (script dihapus)
4. Script composer utama: `composer test` dan `composer test:profanity`; `test:quality`/`test:mutation` dihapus untuk sekarang
5. Group: shared test di-group di `tests/Pest.php` (`app`, `feature`, `unit`, `arch`); tes modul `->group('module:{name}')` + group `feature`/`unit` dari `tests/Pest.php`. Filter: `vendor/bin/pest --group=app`, `--group=feature`, `--group=module:iam`. Lihat https://pestphp.com/docs/grouping-tests
6. Import: shared test BOLEH import class modul langsung (model, factory, seeder, contract, enum) - ArchitectureTest mengizinkan `Tests` memakai `Modules\*\*`; `tests/Helpers.php` tetap seam kenyamanan untuk helper bersama, bukan batas keras
7. Menulis tes mengikuti pest-testing skill: feature-first, factory daripada buat manual, dataset untuk hindari duplikasi, assertion spesifik, fake daripada mock. Lihat https://github.com/matula/laravel-claude-marketplace/tree/main/pest-testing
8. Setiap perubahan kode wajib tes
9. ArchitectureTest (tests/Architecture/ArchitectureTest.php) adalah single source of truth konvensi; perubahan assertion butuh persetujuan manusia (report dulu, jangan auto-fix)
10. Quality flow: `composer lint` (pint) -> `composer rector:dry` (rector) -> `composer types:check` (phpstan) -> `composer test` (pest) -> `composer test:profanity`; `composer ci:check` menjalankan semuanya berurutan

## 8. Pemetaan ke Rules

Dokumen ini dipecah ke `.ai/rules/` (format standar: frontmatter paths + Goal + Rules + Forbidden + Example) sebagai turunan yang di-enforce. 25 file rule sudah dibuat; mapping di bawah adalah mapping hidup yang wajib sinkron jika dokumen ini berubah.

| Section | Rule file |
|---|---|
| 2 (anatomi) | .ai/rules/modules-structure.md (BARU, termasuk layer on-demand 3.21: Jobs, Events, Listeners, Mail, Rules, Exceptions, Lang, Observers, Scopes) |
| 3.1 | .ai/rules/actions.md (refactor) |
| 3.2 | .ai/rules/controllers.md (refactor) |
| 3.3 | .ai/rules/models.md (refactor) |
| 3.4 | .ai/rules/services.md (BARU) |
| 3.5 | .ai/rules/support.md (BARU) |
| 3.6 | .ai/rules/builders.md (BARU) |
| 3.7 | .ai/rules/payloads.md (BARU) |
| 3.8 | .ai/rules/requests.md (BARU) |
| 3.9 | .ai/rules/resources.md (BARU) |
| 3.10 | .ai/rules/policies.md (BARU) |
| 3.11 | .ai/rules/providers.md (refactor) |
| 3.12 | .ai/rules/middleware.md (BARU) |
| 3.13 | .ai/rules/enums.md (BARU) |
| 3.14 | .ai/rules/contracts.md (BARU) |
| 3.15 | .ai/rules/config.md (BARU) |
| 3.16 | .ai/rules/notifications.md (BARU) |
| 3.17 | .ai/rules/commands.md (BARU) |
| 3.18 | .ai/rules/routes.md (refactor) |
| 3.19 | .ai/rules/database.md (refactor) |
| 3.20 + 6 | .ai/rules/features.md (BARU) |
| 3.22 | .ai/rules/bulk-actions.md (BARU) |
| 3.23 | .ai/rules/error-handling.md (BARU) |
| 4 | .ai/rules/responses.md (refactor) + index.md |
| 7 | .ai/rules/tests.md (refactor) |

## 9. Pertanyaan Terbuka (untuk review)

1. Versioning API V2 belum terdefinisi: anatomi menyebut `V1/`, `V2/` di Controllers/Requests dan `routes/V2.php`, tapi aturan 3.18 hanya mendefinisikan `api/v1/{path}`. Mekanisme V2 (header vs URL, kebijakan hidup/mati V1) ditunda sampai ada use case V2 pertama?
