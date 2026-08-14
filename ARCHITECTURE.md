# Arsitektur Modularization: Laravel Starterkit API

Status: Final. Dokumen ini adalah source of truth untuk struktur modul, aturan, dan keputusan arsitektur. Semua keputusan dijelaskan langsung di sini. `docs/architecture.md` (versi restrukturisasi, English) dan `.ai/rules/` adalah turunan yang wajib sinkron.

## 1. Prinsip Desain

Tujuh prinsip golden yang menjadi dasar seluruh struktur modul. Semua aturan di dokumen ini harus konsisten dengan prinsip berikut.

### 1.1 Modul me-mirror struktur app (mirror principle)

Struktur folder modul mencerminkan struktur `app/` bawaan Laravel, termasuk folder kontainer `Http/` (Controllers, Middleware, Requests, Resources) dan `Console/` (Commands) yang menampung layer HTTP/CLI persis seperti `app/Http` dan `app/Console`. Adopter yang sudah mengenal layout Laravel langsung paham modul tanpa dokumentasi tambahan. Hal yang module-scoped tinggal di dalam modul; hal yang shared tinggal di `app/`. Sumber inspirasi: Nuxt Layers ("the layers structure is almost identical to a standard Nuxt application"), struktur package Laravel resmi, dan nWidart/laravel-modules.

### 1.2 Aktivasi via config, tanpa env (config-driven activation)

Kapabilitas diaktifkan dengan mendaftarkannya pada array di config, bukan environment variable. `config/modules.php` adalah central registry: satu-satunya tempat me-manage modul (aktif/non-aktif) dan toggle feature-nya. Sumber inspirasi: Laravel Fortify (`features` array di `config/fortify.php`), Nuxt Layers (file config sebagai penanda layer). Central registry adalah allow-list: modul yang tidak terdaftar sepenuhnya inert, tanpa auto-discovery.

### 1.3 Actions single-responsibility, di-wire oleh provider

Logika bisnis adalah kelas action yang melakukan satu operasi bisnis. Service provider bertugas me-wire: mendaftarkan config, action, dan routes ke framework. Setiap modul punya satu provider (`modules/{Module}/Providers/{Module}ServiceProvider.php`) yang extends base abstract `ModuleServiceProvider` (`app/Providers/`); orchestrator `ModuleLoaderServiceProvider` me-load provider modul AKTIF dari central registry. Sumber inspirasi: Laravel Fortify (`app/Actions/Fortify/CreateNewUser.php`), nWidart/laravel-modules (service provider per modul).

### 1.4 Modul self-contained

Migrations, factories, seeders, routes, request, resource, dan tests hidup di dalam modul. Modul bisa dipindah, dihapus, atau diaktifkan tanpa menyentuh file di luar modul (kecuali central registry `config/modules.php`). Sumber inspirasi: nWidart/laravel-modules.

### 1.5 Shared vocabulary terpisah di app

Kontrak yang dipakai lintas modul (interface, enum shared, response contract) tinggal di `app/`. Modul tidak saling import langsung; komunikasi lewat kontrak. Sumber inspirasi: `shared/` di Nuxt Layers.

### 1.6 Tanpa overhead per modul

Deviasi sadar dari nWidart/laravel-modules: modul TIDAK punya `composer.json`, `module.json`, `resources/assets`, atau `vite.config.js` sendiri. Satu repo, satu dependency graph, satu build. Filosofi: production-ready, not overengineered.

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
│   │   └── Commands/            # Artisan command global (make:module, security:check)
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
│   ├── Providers/               # AppServiceProvider, ModuleLoaderServiceProvider,
│   │                           # ModuleServiceProvider (base abstract), RouteServiceProvider
│   └── Support/                 # Util teknis global (ProductionSecurityCheck)
├── config/                      # Config global (modules.php = central registry modul)
├── database/
│   ├── factories/               # Factory shared
│   ├── migrations/              # Migration shared
│   └── seeders/                 # Seeder shared
├── modules/
│   └── {Module}/                # Satu modul (folder TitleCase, alias lowercase)
│       ├── Http/                # Mirror app/Http: semua layer HTTP di sini
│       │   ├── Controllers/     # V1/, V2/ untuk API versioning (invokable single-action)
│       │   ├── Middleware/      # Middleware module-specific
│       │   ├── Requests/        # V1/ (FormRequest validation)
│       │   └── Resources/       # API resource transformer
│       ├── Console/
│       │   └── Commands/        # Artisan command module-specific
│       ├── Exceptions/          # Exception class module-specific
│       ├── Features/            # Pennant class-based feature module-specific (runtime flag)
│       ├── Jobs/                # Queue job module-specific
│       ├── Mail/                # Mail module-specific
│       ├── Rules/               # Custom validation rule module-specific
│       ├── Events/              # Event module-specific
│       ├── Listeners/           # Listener module-specific
│       ├── Lang/                # {locale}/ (translasi modul, dimuat saat aktif)
│       ├── Models/              # Model Eloquent modul
│       ├── Observers/           # Observer model (didaftarkan #[ObservedBy])
│       ├── Policies/            # Policy authorization modul (didaftarkan #[UsePolicy])
│       ├── Scopes/              # Global scope (didaftarkan #[ScopedBy])
│       ├── Providers/           # (wajib) {Module}ServiceProvider extends ModuleServiceProvider (base)
│       ├── Notifications/       # Notifikasi module-specific
│       ├── Actions/             # Kit-specific: satu operasi bisnis, final readonly, handle()
│       ├── Builders/            # Kit-specific: query builder, extends BaseQueryBuilder
│       ├── Services/            # Kit-specific: logika lintas use-case
│       ├── Payloads/            # Kit-specific: DTO input action
│       ├── Support/             # Kit-specific: util teknis murni
│       ├── Contracts/           # Kit-specific: kontrak module-specific
│       ├── Enums/               # Kit-specific: enum module-specific
│       ├── Config/              # Kit-specific: {alias}.php (di-merge base provider)
│       ├── Routes/              # (wajib) V1.php, V2.php (dimuat base provider)
│       ├── Database/            # Kit-specific: Migrations, Factories, Seeders
│       └── Tests/               # (wajib) Feature dan unit test modul
├── routes/
│   ├── api.php                  # Reserved; routes API didaftarkan modul (Routes/V1.php)
│   ├── console.php              # Route console
│   └── web.php                  # Route web
└── tests/                       # Test app global
    ├── Architecture/            # Architecture tests (konvensi)
    ├── Feature/                 # Test infrastruktur (middleware, responses, dll.)
    ├── Unit/                    # Test unit app
    └── Helpers.php              # Seam akses model modul (bukan import langsung)
```

Prinsip mirror (1.1): `modules/{Module}/` adalah mirror skeleton `app/` bawaan Laravel; folder kontainer `Http/` dan `Console/` menampung layer HTTP/CLI persis seperti `app/Http` dan `app/Console`. Folder wajib pada modul AKTIF hanya 3: `Providers`, `Routes`, `Tests`; sisanya optional dan dibuat saat dibutuhkan. Layer kit-specific tanpa padanan di skeleton: Actions, Services, Payloads, Builders, Features, Config, Routes, Database, Tests, Lang. `app/Http/Responses` adalah kontrak global dan tidak dimirror ke modul.

### 2.2 Folder matrix

Folder required (wajib ada pada modul AKTIF):

| Folder | Isi |
|---|---|
| Providers | {Module}ServiceProvider extends base ModuleServiceProvider |
| Routes | File route (V1.php) |
| Tests | Feature dan unit test |

Folder optional (hanya dibuat jika berisi minimal 1 file, dilarang folder kosong):

| Folder | Isi |
|---|---|
| Http | Controllers, Middleware, Requests, Resources (mirror app/Http) |
| Console | Commands (mirror app/Console) |
| Exceptions | Exception class module-specific |
| Features | Pennant class-based feature (runtime flag) |
| Jobs | Queue job module-specific |
| Mail | Mail module-specific |
| Rules | Custom validation rule module-specific |
| Events | Event module-specific |
| Listeners | Listener module-specific |
| Lang | Translasi modul ({locale}/) |
| Models | Model Eloquent |
| Observers | Observer model (via #[ObservedBy]) |
| Policies | Policy authorization (via #[UsePolicy]) |
| Scopes | Global scope (via #[ScopedBy]) |
| Notifications | Notifikasi module-specific |
| Actions | Logika bisnis, satu operasi per kelas |
| Builders | Query builder (extends BaseQueryBuilder) |
| Services | Logika lintas use-case |
| Payloads | DTO input action |
| Support | Util teknis murni |
| Contracts | Kontrak module-specific |
| Enums | Enum module-specific |
| Config | {alias}.php (di-merge base provider) |
| Database | Migrations, Factories, Seeders |

Modul non-aktif (tidak terdaftar aktif di central registry) minimal berisi `Providers`, `Tests`. Contoh: Organization. Sisa struktur muncul saat modul diaktifkan.

### 2.3 Sumber inspirasi dan deviasi

| Aspek | Nuxt Layers | Laravel Fortify | nWidart/laravel-modules | Keputusan kit |
|---|---|---|---|---|
| Struktur modul | Mirror app standar | Paket terstruktur | app/ + resources + vite | Mirror app/ (Http/ kontainer) |
| Aktivasi | Config layer | Config features array | module.json + auto-discovery | Central registry config/modules.php (allow-list) |
| Metadata modul | nuxt.config | - | module.json di dalam modul | Central registry (active, features) |
| Feature toggle | - | features array | - | Registry (build-time) + Pennant class (runtime) |
| Logika bisnis | Composables/utils | Actions | Service classes | Actions + Services |
| Resource DB | - | Migrations via publish | Migrations/factories/seeders di modul | Di dalam modul |
| Overhead per modul | nuxt.config | composer package | composer.json + module.json | Tidak ada |
| Shared code | shared/ | Vendor namespace | Modules namespace | app/ (shared vocabulary) |
| Repositories | - | - | Repositories layer | TIDAK dipakai (Eloquent adalah repository) |

Catatan keputusan: flag `--repository` dihapus dari generator (Eloquent adalah repository); `--event` dipertahankan (`Events/` optional, dibuat saat dibutuhkan). Eksekusi saat implementasi generator.

## 3. Tanggung Jawab Layer

Setiap layer: definisi, aturan, larangan, contoh.

### 3.1 Actions

Definisi: kelas `final readonly` yang melakukan SATU operasi bisnis, dipanggil oleh controller, dipanggil action lain, atau dipakai service. Sumber inspirasi: Fortify Actions.

Aturan:
1. `final readonly`, satu method publik `handle()`, parameter bertipe eksplisit
2. TIDAK menerima `Request`; controller mengekstrak data dan meneruskannya
3. TIDAK menulis logika HTTP (status code, redirect, json)
4. Validasi terjadi di Request layer, bukan di action
5. Setiap action punya unit test di `modules/*/Tests/Unit`
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

Definisi: kelas `final readonly` invokable single-action di `modules/{Module}/Http/Controllers/V1/`. Hanya menangani urusan HTTP: parse request, panggil action, kembalikan response.

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

Definisi: model Eloquent di `modules/{Module}/Models/`. Data access milik modul.

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

Definisi: FormRequest per endpoint di `modules/{Module}/Http/Requests/V1/`. Request lintas modul (pagination, bulk action) hidup di `app/Http/Requests/` (shared).

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

Definisi: API resource transformer di `modules/{Module}/Http/Resources/`.

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

Definisi: `modules/{Module}/Providers/{Module}ServiceProvider.php` yang me-wire modul ke framework. Setiap provider modul extends base abstract `ModuleServiceProvider` (`app/Providers/`); orchestrator `ModuleLoaderServiceProvider` (app) yang me-load provider modul AKTIF dari central registry `config/modules.php`.

Aturan:
1. Base class menyediakan boilerplate loading: merge `Config/{alias}.php`, merge `features` dari registry, load migrations, load routes `Routes/V1.php`, load translations `Lang/`, register commands `Console/Commands` (tidak ada `withCommands` di `bootstrap/app.php`; command modul didaftarkan base provider)
2. Provider modul hanya deklarasi: `moduleName()` (abstract) dan hook `bootModule()` untuk middleware alias, Pennant features, binding (policy via `#[UsePolicy]` di model)
3. `register()`/`boot()` base bersifat `final`; urutan loading tidak bisa diacak subclass
4. Aktivasi modul hanya lewat central registry (allow-list); modul tidak terdaftar = provider tidak pernah di-boot
5. Tanpa registrasi tersembunyi; middleware alias didaftarkan eksplisit, bukan magic discovery
6. Alias modul diturunkan dari `moduleName()` via `Str::snake()` (`'Media'` ke `'media'`); alias dipakai untuk key config (`config('media.*')`), merge `Config/{alias}.php`, dan route prefix (`api/v1/{module}`, 3.18)

Larangan:
- Dilarang provider modul extends `ServiceProvider` langsung (harus base `ModuleServiceProvider`)
- Dilarang provider mendaftarkan routes di luar `Routes/`
- Dilarang env() di provider

### 3.12 Middleware

Definisi: middleware module-specific di `modules/{Module}/Http/Middleware/`; middleware global di `app/Http/Middleware/`.

Aturan:
1. Middleware yang hanya dipakai route modul tertentu tinggal di modul
2. Middleware global (auth, throttle, security headers) di app
3. Alias middleware didaftarkan eksplisit, bukan magic discovery

Larangan:
- Dilarang middleware global di dalam modul
- Dilarang middleware tanpa alias

### 3.13 Enums

Definisi: enum khusus modul di `modules/{Module}/Enums/`; enum shared vocab (dipakai 2+ modul) di `app/Enums/`.

Aturan:
1. 1 call-site modul saja: di modul. 2+ modul: di app
2. Nilai TitleCase; label native via method (tanpa dependency library label)
3. Cast model ke enum class

Larangan:
- Dilarang enum module-specific tinggal di app/Enums
- Dilarang enum shared tinggal di modul

### 3.14 Contracts

Definisi: kontrak modul di `modules/{Module}/Contracts/`; kontrak lintas modul (shared vocabulary) di `app/Contracts/`.

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
4. Event pub/sub untuk decoupling longgar: shared event class di `app/Events/` (module A dispatch), listener di modul pendengar didaftarkan eksplisit di `bootModule()` (3.21); listener global di `app/Listeners` ter-auto-discovery

Kapan model langsung vs interface:

- Data + relasi Eloquent: model langsung (Eloquent butuh class konkret, `belongsTo(User::class)`); interface tidak bisa dipakai untuk relasi
- Perilaku/decoupling/2+ implementasi mungkin: interface di `app/Contracts/` (contoh: `Identity`)
- 1 implementasi pasti dan tidak akan 2+: cukup model langsung; interface = YAGNI

Rule of thumb base class/interface per layer: hanya jika (1) ada logika yang dieksekusi bersama, (2) butuh polimorfisme/decoupling nyata, (3) kontrak lintas modul, (4) container binding. Dilarang demi "konsistensi" belaka; konvensi struktur di-enforce ArchitectureTest, bukan inheritance.

### 3.15 Config

Definisi: config global di `config/`; config modul di `modules/{Module}/Config/{alias}.php` (alias lowercase dari central registry, bukan nama folder TitleCase).

Aturan:
1. Config modul di-merge provider saat modul aktif
2. Akses config via helper typed (`config()->integer(...)`) agar tipe terjaga
3. Features array ala Fortify (lihat section 6)

Larangan:
- Dilarang env() di luar config files
- Dilarang config modul dimuat saat modul non-aktif

### 3.16 Notifications

Definisi: notifikasi di `app/Notifications/` (global) atau `modules/{Module}/Notifications/` (module-specific).

Aturan:
1. Queue-able, via `ShouldQueue`
2. Naming deskriptif (VerifyEmail, ResetPassword)

Larangan:
- Dilarang notifikasi dipanggil langsung di controller (via action/service)

### 3.17 Commands

Definisi: Artisan command di `app/Console/Commands/` (global) atau `modules/{Module}/Console/Commands/` (module-specific).

Aturan:
1. PHP 8 attributes: `#[Signature]`, `#[Description]`, `#[Help]`, `#[Usage]`
2. `handle(): int` dengan exit code
3. Command modul didaftarkan base `ModuleServiceProvider` saat modul aktif (tidak ada `withCommands` di `bootstrap/app.php`); command global di `app/Console/Commands` ter-auto-discovery

Larangan:
- Dilarang command tanpa attributes signature

### 3.18 Routes

Definisi: route file modul di `modules/{Module}/Routes/V1.php`, dimuat base `ModuleServiceProvider` saat modul aktif (menggantikan discover sentral di RouteServiceProvider).

Aturan:
1. Base prefix `api/v1/{module}`; route name `v1.{module}.{name}`
2. Middleware eksplisit di route group (auth:sanctum, throttle, permission, feature.flag)
3. Route file hanya dimuat jika modul aktif

Larangan:
- Dilarang registrasi route di luar `Routes/`
- Dilarang middleware tersembunyi di provider

### 3.19 Database

Definisi: schema modul di `modules/{Module}/Database/` (Migrations, Factories, Seeders), dimuat base `ModuleServiceProvider` saat modul aktif.

Aturan:
1. Enum value sebagai default kolom (`->default(StatusEnum::Pending->value)`)
2. Dilarang chain perintah migration dengan && atau ; (timestamp identik)
3. Factory + Seeder untuk tiap model
4. Perubahan schema = review gate (butuh persetujuan)
5. Seeder modul dieksekusi via `php artisan db:seed --class=Modules\{Module}\Database\Seeders\{Name}Seeder` atau dari `database/seeders/DatabaseSeeder`; dilarang seeder memanggil seeder modul lain (dependensi di-seed berurutan dari caller, contoh: `MediaSeeder` tidak memanggil `IAMSeeder`)
6. Rollback migration modul via `php artisan migrate:rollback --path=modules/{Module}/Database/Migrations` (tanpa `--path`, rollback hanya batch terakhir global)

Larangan:
- Dilarang edit schema tanpa persetujuan
- Dilarang migration di luar modul

### 3.20 Features

Definisi: feature flag modul. Build-time toggle: array `features` di central registry (`config/modules.php`). Runtime per-user: Pennant class di `modules/{Module}/Features/` (dipakai 2+ modul: `app/Features/`), diperiksa via `FeatureFlagMiddleware`.

Aturan:
1. Build-time: nilai boolean di registry; di-merge base provider ke `config('{alias}.features')`
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
2. `Lang/` dimuat base `ModuleServiceProvider` saat modul aktif
3. Aturan detail cukup mengikuti konvensi Laravel; tidak dibuat rule file terpisah per folder
4. Listener modul TIDAK ter-auto-discovery (bootstrap hanya scan `app/Listeners`); daftarkan listener eksplisit di `bootModule()` via `Event::listen`/`Event::subscribe`

Larangan:
- Dilarang folder kosong sebagai placeholder

### 3.22 Bulk Action

Definisi: endpoint mutasi massal (delete, restore) yang memproses banyak id sekaligus. Request shared `App\Http\Requests\BulkActionRequest` (validasi `ids` max 50 + `action`); controller delegasi ke Action; Action mengeksekusi satu query bulk.

Aturan:
1. Wajib `BulkActionRequest` (shared) untuk semua endpoint bulk; otorisasi per aksi via `authorize()` berbasis route name
2. Action bulk = satu query `whereIn` (delete/restore), return count
3. `Bus::bulk`/`Bus::batch` TIDAK dipakai untuk mutasi sinkron; hanya untuk per-item processing berat yang butuh queue (belum ada use case; aturan ditambah saat muncul)
4. Routing: `POST /{resource}/bulk/{action}`, route name `v1.{module}.{resource}.bulk.{action}`
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
5. Route name `v1.{module}.{name}`; modul lowercase di central registry
6. Kelas operasional: `final readonly`; gunakan constructor property promotion
7. Dokumen (docs, rule, roadmap): ASCII murni, tanpa emoji, tanpa em/en dash, tanpa arrow, pakai hyphen
8. Bahasa kode dan komentar: English

## 5. Siklus Hidup Modul

### 5.1 Membuat modul

```bash
php artisan make:module Blog
```

Generator membuat struktur required: `Providers`, `Routes`, `Tests`. Optional layer ditambah saat dibutuhkan (bukan di awal).

### 5.2 Mengaktifkan modul

Central registry `config/modules.php` adalah satu-satunya tempat me-manage modul dan fiturnya:

```php
return [
    'modules' => [
        'iam' => [
            'active'   => true,
            'features' => [
                'register'    => true,
                'social-auth' => true,
            ],
        ],
        'media' => [
            'active'   => true,
            'features' => [
                'upload'     => true,
                'signed-url' => false,
            ],
        ],
        'organization' => [
            'active'   => false,
            'features' => [
                'multi-tenancy' => false,
            ],
        ],
    ],
];
```

`ModuleLoaderServiceProvider` membaca registry: entry dengan `active => true` me-load provider modul via konvensi `Modules\{Name}\Providers\{Name}ServiceProvider` (guard `class_exists`; folder modul absen = aman, tidak fatal). Base `ModuleServiceProvider` meng-merge config + features, lalu load migrations, routes, dan translations modul. Modul yang tidak terdaftar = sepenuhnya inert: provider, config, migrations, routes tidak dimuat (dibuktikan tes).

Setelah mengubah registry, jalankan `php artisan config:cache` (+ `route:cache` bila routes ter-cache) agar perubahan aktif; di production registry ter-bake ke cache, lupa refresh = modul tetap pada status sebelumnya.

Urutan boot antar modul = urutan deklarasi di central registry; key `priority` tidak dipakai sampai ada dependensi boot lintas modul yang nyata.

### 5.3 Menonaktifkan modul

Hapus dari registry dengan `active => false` (atau hapus entry). Data modul tetap di database (migration tidak di-rollback otomatis); schema tetap ada, behavior off.

### 5.4 Modul privat

Folder modul privat disimpan di disk + ditambahkan ke `.gitignore` + tidak didaftarkan di central registry. Tidak pernah dikirim ke repo publik.

### 5.5 Kasus khusus: Organization (tenancy)

Organization adalah modul non-aktif minimal (Providers, Tests) yang membungkus stancl/tenancy (opsi opt-in tenancy). Deviasi deliberate:
- Tenant model memakai UUID (stancl default), deviasi dari aturan ULID, terkurung di modul
- Config `tenancy.php` di dalam modul
- Sisa struktur tumbuh saat modul diaktifkan (MVP 2)

### 5.6 Menghapus modul

Hapus folder modul dan entry central registry `config/modules.php`. Provider tidak di-boot (guard `class_exists`); folder absen tidak fatal. Data database tetap ada (migration tidak auto-rollback).

## 6. Toggle & Native-First

### 6.1 Model 3 level toggle

| Level | Mekanisme | Waktu | Contoh |
|---|---|---|---|
| Module | Central registry `config/modules.php` (`active`) | Build-time | `organization` off = tenancy inert |
| Feature (static) | Array `features` di registry per modul (ala Fortify) | Build-time | Media: upload vs signedUrl |
| Feature (runtime) | Pennant flags (class di `Features/`) + FeatureFlagMiddleware | Runtime, per-user | beta flag, gradual rollout |

### 6.2 Draf code: central registry

```php
// config/modules.php
return [
    'modules' => [
        'media' => [
            'active'   => true,
            'features' => [
                'upload'     => true,
                'signed-url' => false,
            ],
        ],
    ],
];
```

Base `ModuleServiceProvider` meng-merge `features` ke `config('media.features')` saat boot; provider modul tinggal deklarasi + hook:

```php
// modules/Media/Providers/MediaServiceProvider.php
final class MediaServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'Media';
    }

    protected function bootModule(): void
    {
        if (MediaFeatures::enabled(MediaFeatures::signedUrl())) {
            // register signed URL routes or middleware only when enabled
        }
    }
}
```

```php
// modules/Media/Support/MediaFeatures.php
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

Feature yang butuh keputusan runtime (per user, gradual rollout) didefinisikan sebagai Pennant class di `modules/{Module}/Features/`:

```php
// modules/Media/Features/MediaUpload.php
final class MediaUpload extends Feature
{
    public function resolve(User $user): bool
    {
        return $user->hasRole(RoleEnum::SuperAdmin); // keputusan runtime per-user
    }
}
```

Route dilindungi middleware `feature.flag` (FeatureFlagMiddleware). Feature yang dipakai 2+ modul tinggal di `app/Features/`.

Catatan: Pennant class hanya untuk keputusan runtime (per-user, gradual rollout); toggle statis cukup memakai features array di registry (6.1/6.2) tanpa class Pennant.

### 6.4 Chisel markers

Pola `/* @chisel-{feature} */` dan `/* @end-chisel-{feature} */` (dari vue-starter-kit Laravel) DITUNDA: keputusan menyusul evaluasi `laravel/chisel` (backlog). Tidak diadopsi dulu.

### 6.5 Native-first

Setiap wrapper wajib punya escape hatch native yang terdokumentasi:
- BaseQueryBuilder: action tetap boleh `User::where(...)` biasa
- Responses: handler tetap memetakan exception ke problem details
- Middleware: route boleh tanpa middleware khusus bila tidak perlu
Bukti: tes yang membuktikan jalur native tetap berfungsi.

## 7. Pengujian

1. Placement: tes modul di `modules/*/Tests/` (Feature, Unit); tes app di `tests/` (Feature, Unit, Architecture)
2. Struktur folder tes modul: `Tests/Feature/` (opsional subfolder `V1/` mirror `Http/Controllers/V1`) dan `Tests/Unit/`; tes shared: `tests/{Architecture,Feature,Unit}/`, `tests/Datasets/`, `tests/Helpers.php`, `tests/Pest.php`
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

1. Migrasi folder modul existing (IAM, Media) ke anatomi baru (`Http/Controllers`, `Http/Requests`, `Http/Resources`, `Console/Commands`) adalah breaking change: dieksekusi di phase berikutnya, bukan bagian review dokumen ini?
2. Versioning API V2 belum terdefinisi: anatomi menyebut `V1/`, `V2/` di Controllers/Requests dan `Routes/V2.php`, tapi aturan 3.18 hanya mendefinisikan `api/v1/{module}`. Mekanisme V2 (header vs URL, kebijakan hidup/mati V1) ditunda sampai ada use case V2 pertama?
