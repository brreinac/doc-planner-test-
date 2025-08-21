# Doctor Slots Synchronizer — Refactor & Tests

## What this is
A framework-agnostic refactor of `DoctorSlotsSynchronizer.php` (and static-data counterpart) into small, testable components with clear separation of concerns. PHPUnit is used for unit tests.

## Architecture Overview

### Key Components
- **Synchronizers**
  - `App\Service\DoctorSlotsSynchronizer` — Orchestrates sync from any `DataProviderInterface`. Uses UoW to wrap per-doctor persistence; dispatches `DoctorSyncedEvent` on success.

  - `App\Synchronizer\StaticDoctorSlotsSynchronizer` — Reads local/static payloads, reuses `NameNormalizer` + `SlotsParser`, marks doctor as error if slot parsing fails.

- **Providers (Strategy)**
  - `App\Provider\DataProviderInterface` — `getDoctors(): iterable<DoctorData>`

  - `App\Provider\Http\HttpDataProvider` — Fetches doctors and lazily fetches slots per doctor via `HttpClientInterface`.

  - `App\Provider\Static\StaticDataProvider` — Reads JSON from file via `FileReaderInterface` + `JsonDecoder`.

- **Domain Factories**
  - `App\Factory\DoctorFactory` and `App\Factory\SlotFactory` — Centralize construction & invariants.

- **Repositories (Ports)**
  - `DoctorRepositoryInterface`, `SlotRepositoryInterface` — Abstract persistence (DB/ORM agnostic).

  - Implementation detail (e.g., Doctrine) is out of scope and can be wired later.

- **Unit of Work**
  - `UnitOfWorkInterface` — `begin/commit/rollback` for atomic per-doctor sync.

- **Parsing & Normalization**
  - `NameNormalizer` — Trims and capitalizes; supports hyphen/apostrophe; special-cases `Mc`/`Mac`.

  - `SlotsParser` — Streaming conversion of slot payloads into `Slot` entities; updates end-time if different.

- **Policies**
  - `ErrorReportingPolicy` — Encapsulates “don’t report on Sundays”; optional rate-limiting via `ReportLimiter`.

  - `ClockInterface` — Time abstraction for deterministic tests.

- **HTTP**
  - `HttpClientInterface` and `FileGetContentsHttpClient` — Lightweight HTTP port and FGC adapter.

- **Events (Observer)**
  - `DoctorSyncedEvent` + `EventDispatcherInterface` — Optional domain event after successful per-doctor sync.

### Design Patterns Applied

- **Strategy**: Provider interface to support HTTP/static sources.

- **Template Method/Orchestrator**: Synchronizers drive a fixed flow while delegating provider/parse/persist steps.

- **Factory**: Centralized domain creation.

- **Repository (DDD Port)**: Decouples business logic from storage.

- **Unit of Work**: Transaction boundary per doctor.

- **Policy**: Pluggable error-reporting rule.

- **Observer (optional)**: Domain event after sync.

### Why this improves testability & maintainability

- Pure business logic is isolated (no HTTP/DB in the core), so unit tests are simple and fast.

- Smaller classes with single responsibilities make changes safer.

- Time-dependent logic is testable via `ClockInterface`.

- Providers make adding a new source (CSV, gRPC, etc.) straightforward.

## Behavior & Assumptions

- Doctors are normalized via `NameNormalizer` every sync.

- For each doctor’s slot:
  - If slot doesn’t exist → create.
  - If start matches but end changed → update end.

- Errors while fetching a given doctor’s slots:
  - Transaction rolls back,
  - Doctor is marked with error and saved (best-effort),
  - Reporting obeys `ErrorReportingPolicy` (skip Sundays and/or limiter).

- Top-level provider errors:
  - **HTTP provider**: bubbles up today (can be wrapped by retry decorators).

  - **Static provider**: caught; reports per policy; continues gracefully.

## Getting Started

### Requirements
- PHP 8.1+
- Composer

## One-shot install commands

# clean slate
rm -rf vendor composer.lock

# install with pinned deps
composer install --no-interaction

# verify autoload
composer dump-autoload -o

# run tests
composer test

### Install
```bash
composer install
# or if lock was removed/changed
composer update

