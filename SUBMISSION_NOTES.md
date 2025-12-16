# Submission Notes

## Overview

This submission implements the two tasks from the assessment brief using conventional Laravel patterns (FormRequests, Resources, Commands, Jobs, and the Scheduler). All behaviour is verified by automated tests runnable via `php artisan test` using the provided in-memory SQLite configuration.

## How to run

-   `composer install`
-   `php artisan test`

## Task 1 - Expose a new API endpoint to list all applications

### Key decisions (why)

-   Reused the existing `auth:sanctum` pattern (same as `/api/user`) to keep auth out of scope and avoid introducing new auth behaviour.
-   Used a FormRequest to validate `plan_type` to keep the API contract strict and predictable.
-   Used an API Resource to ensure ONLY the required fields are returned and to keep formatting/composition logic out of the controller.
-   Used eager loading + pagination for scalability and to avoid N+1 queries.

### Implementation (what)

-   Route: `GET /api/applications` (authenticated).
-   Optional query: `plan_type` (`nbn|opticomm|mobile`).
-   Ordering: oldest first (`created_at` ascending).
-   Response item fields:
    -   `application_id`
    -   `customer_full_name`
    -   `address` (composed from `address_1`, optional `address_2`, `city` + `postcode`; state returned separately)
    -   `plan_type`
    -   `plan_name`
    -   `state`
    -   `plan_monthly_cost` (cents -> dollar string with 2dp)
    -   `order_id` only when status is `complete`

### Evidence

-   Code: `routes/api.php`, `app/Http/Controllers/Api/ApplicationController.php`, `app/Http/Requests/ApplicationIndexRequest.php`, `app/Http/Resources/ApplicationResource.php`, `app/Models/Application.php`
-   Tests: `tests/Feature/ApplicationsIndexTest.php` (empty response, pagination/shape, ordering, filtering, cents formatting, conditional `order_id`, validation, auth behaviour)
-   Auth note: this repo’s `app/Http/Middleware/Authenticate.php` aborts unauthenticated API requests with `403`. The guest test tolerates `401` or `403` to stay stable across common Sanctum/Laravel defaults.

## Task 2 - Automate the ordering of all nbn applications

### Key decisions (why)

-   Used scheduler + command + queued job to match Laravel conventions and keep periodic work out of web requests.
-   Dispatcher uses `chunkById()` and selects only `id` to keep processing scalable.
-   Job re-checks eligibility (status still `order`, plan still `nbn`) to reduce race-condition risk.
-   A single failure path (`order failed`) for failure responses, missing config, or exceptions keeps state transitions easy to reason about.

### Implementation (what)

-   Scheduler: runs `nbn:dispatch-orders` every five minutes and uses `withoutOverlapping()`.
-   Dispatcher command: finds applications where `status=order` and `plan.type=nbn`, dispatching one job per application.
-   Job:
    -   Posts to `NBN_B2B_ENDPOINT` (exposed as `services.nbn.endpoint`) with payload: `address_1`, `address_2`, `city`, `state`, `postcode`, `plan_name` (plan name value).
    -   Success (per provided stub contract): response is HTTP 200 + JSON `status` = `Successful` + non-empty `id` → persist `order_id` and set status to `complete`.
    -   Failure/exception/missing endpoint/missing plan name → clear `order_id` and set status to `order failed` (enum value includes a space).

### Evidence

-   Code: `app/Console/Kernel.php`, `app/Console/Commands/DispatchNbnOrders.php`, `app/Jobs/OrderNbnApplication.php`, `config/services.php`
-   Stubs: `tests/stubs/nbn-successful-response.json`, `tests/stubs/nbn-fail-response.json`
-   Tests:
    -   `tests/Feature/DispatchNbnOrdersCommandTest.php` (only eligible apps dispatch jobs)
    -   `tests/Feature/OrderNbnApplicationTest.php` (success path, failure response, exception, non-nbn, non-order, missing endpoint, missing plan name; asserts payload and `Http::assertSentCount(1)`)

## Notes (non-task changes)

These changes were made purely to keep `php artisan test` runnable in this repo, and do not affect the task logic:

-   CORS middleware: replaced missing `Fruitcake\\Cors\\HandleCors` reference with Laravel’s built-in `Illuminate\\Http\\Middleware\\HandleCors` (`app/Http/Kernel.php`).
-   PHP deprecation notice: adjusted SSL CA PDO constant usage (`config/database.php`) to keep tests clean on newer PHP versions; no impact on the in-memory SQLite test suite.
-   HTTP client dependency: added `guzzlehttp/guzzle` because Laravel’s HTTP client (`Http::post()` / `Http::fake()`) requires it; this repo did not include it by default. The assessor brief allows adding packages if justified. This keeps the implementation aligned with the requirement to use `Http::post()` and prevents real network calls in tests.

## If I had more time

-   Add stronger idempotency guarantees around ordering (e.g. unique jobs or external idempotency keys) and structured logging.
-   Add retry/backoff + timeouts around the B2B call once API/SLA expectations are known.
-   Add a few pagination edge-case tests (e.g. multiple pages) if the UI requirements were clearer.

## Submission packaging

-   Per the brief: submit a zip/tarball excluding `vendor/` while preserving git history.
