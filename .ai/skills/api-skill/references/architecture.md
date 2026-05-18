# Architecture Reference (2026)

Proyek ini menggunakan **Domain-Driven Modular Architecture** dengan pengawasan otomatis melalui Architecture Testing.

---

## 1. Module Layout

Setiap modul di `modules/` harus mengikuti struktur ini:

```text
modules/
  {Module}/
    Actions/            # Logika Bisnis (Atomic/Orchestrator) - Final Readonly
    Controllers/
      V1/               # Single-action controllers - Final Readonly
    Payloads/
      V1/               # Data Objects - Final Readonly with Property Hooks
    Requests/
      V1/               # Validation & Authorization - Final
    Resources/          # Eloquent Resources - Final
    Models/             # Eloquent Models
    Filters/            # BaseFilter implementations - Final
    Routes/
      V1.php            # Route definitions
    Database/
      Migrations/
      Factories/
    Tests/
      Feature/
      Architecture/     # Pest Arch rules spesifik modul
```

## 2. Communication Rules (The Rules of 2026)

### Synchronous (Read-only)
Modul diperbolehkan mengakses **Model** dari modul lain untuk keperluan baca (data retrieval).

### Asynchronous (State-change)
Modul **DILARANG** memanggil Action dari modul lain secara langsung. Gunakan **Events** dan **Listeners** untuk efek samping antar modul.

### Observability
Semua interaksi antar modul harus tetap membawa `trace_id` yang tersimpan di Laravel **Context**.

## 3. The Orchestrator Pattern

Untuk operasi kompleks, gunakan satu Action utama (Orchestrator) yang memanggil beberapa Action atomik di dalam modul yang sama.

```php
final readonly class CheckoutAction
{
    public function handle(CheckoutPayload $payload): Order
    {
        return $this->database->transaction(function() use ($payload) {
            $order = $this->createOrder->handle($payload->toOrderPayload());

            // Side effect via Event
            event(new OrderPlaced($order));

            // Background task via defer
            defer(fn() => $this->notifyAdmin($order));

            return $order;
        });
    }
}
```

## 4. Architecture Verification (Automated)

Integritas struktur modular ini wajib diverifikasi oleh **Pest Arch**. Jika pengembang melanggar batasan (misal: Controller akses Model), pengujian akan gagal secara otomatis.
