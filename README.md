## Refactor summary (DoctorSlotsSynchronizer)

- Extracted pure business components:
  - `NameNormalizer` (normalizes doctor names with special O' handling).
  - `JsonDecoder` (throws on invalid JSON).
  - `HttpClientInterface` + `FileGetContentsHttpClient` (abstracted HTTP).
  - `SlotsParser` (parses slots and updates stale Slot entities).
  - `ErrorReportingPolicy` (encapsulates "don't report on Sundays").
  - Repository interfaces for Doctor and Slot persistence.

- Introduced `ClockInterface` to make time-based rules testable.

### Why this improves things
- Business logic is now decoupled from HTTP & persistence. Unit tests can target the pure logic.
- Single Responsibility: small classes focused on one job each.
- Time-dependent logic (reporting policy) is deterministic in tests via `ClockInterface`.
- Repositories abstract away ORM details; Doctrine remains what implements the interfaces.

### Assumptions
- The `Slot` & `Doctor` entity methods used in the original code are preserved (e.g., `isStale`, `setEnd`, `markError`, `clearError`).
- Repository methods `find`, `save`, and `findByDoctorAndStart` exist / will be implemented by Doctrine adapters.

### TODOs (out of scope for this change)
- Add a proper `PSR-3` logger injection into synchronizer (currently commented placeholder).
- Replace `FileGetContentsHttpClient` with a Guzzle client (would improve error handling & timeouts).
- Add retries/backoff for transient HTTP errors.
- Add integration tests that run against the dockerized vendor API at http://localhost:2137.
- Add configuration for time zone handling (DateTime creation currently uses default time zone).
