# Solution notes

This document explains what was built for the two tasks in `README.md`, the decisions behind it, how it was tested, and what I would do with more time.

No additional packages were added. Everything uses Laravel 12 and Sanctum as shipped in `composer.json`.

For a step-by-step run of both tasks with screenshots and real responses, see [WALKTHROUGH.md](WALKTHROUGH.md).

## Running it

```
docker compose up
docker compose exec app php artisan test               # 40 tests, 112 assertions
docker compose exec app php artisan queue:work         # task 2: process queued orders
docker compose exec app php artisan schedule:work      # task 2: run the five-minute schedule
```

Task 2 needs `NBN_B2B_ENDPOINT` set in `.env` before the scheduler will queue anything (see [Safety](#safety)).

## Commits

The branch has six code commits on top of `main`, each self-contained and green, plus two documentation commits (this document, then the walkthrough):

| Commit | What it does |
| --- | --- |
| `fix(factories): hash the UserFactory password…` | Fixes a scaffold bug that made `User::factory()` throw in tests (see below). |
| `feat(models): add relationships, PlanType enum…` | Model groundwork shared by both tasks. |
| `feat(api): list applications endpoint (task 1)` | Task 1. |
| `feat(orders): automate nbn B2B ordering… (task 2)` | Task 2. |
| `fix(orders): report applications found…` | Corrects a misleading log line found during an end-to-end run. |
| `fix(api): answer unauthenticated API requests with a JSON 401…` | Guests without a JSON `Accept` header got a 500, not a 401. Found in the browser walkthrough. |

## Groundwork shared by both tasks

- **Relationships.** `Application` belongs to `Customer` and `Plan`. `Customer` and `Plan` each have many `Application`s.
- **`App\Enums\PlanType`** (`nbn`, `opticomm`, `mobile`) is cast on `plans.type`, in the same way `ApplicationStatus` is already cast on `applications.status`. The filter validation, the query scope and the factory all use the enum, so the list of plan types lives in one place.
- **Accessors.** `Customer::full_name` trims the result, because `last_name` is nullable. `Plan::formatted_monthly_cost` turns cents into dollars.
- **Query scopes** `Application::ofPlanType()` and `withStatus()` are used by both tasks.
- **State transitions** `Application::markAsOrdered($orderId)` and `markAsOrderFailed()` keep the status rules on the model, not in the job.

## Task 1: list applications

**`GET /api/applications?plan_type={nbn|opticomm|mobile}`**, behind `auth:sanctum` (route name `applications.index`).

| File | Role |
| --- | --- |
| `app/Http/Requests/ListApplicationsRequest.php` | Validates `plan_type` as nullable and one of `PlanType`. `planType()` returns the enum or `null`. |
| `app/Http/Controllers/Api/ApplicationController.php` | Builds the query and paginates it. |
| `app/Http/Resources/ApplicationResource.php` | Decides exactly which fields leave the API. |

Example item:

```json
{
  "id": 1,
  "customer_full_name": "Jane Citizen",
  "address": { "address_1": "1 Example Street", "address_2": "Unit 2", "city": "Morwell", "postcode": "3840" },
  "state": "VIC",
  "plan_type": "nbn",
  "plan_name": "nbn 100/20",
  "plan_monthly_cost": "$89.99",
  "order_id": "ORD000000000000"
}
```

How each requirement is met:

- **Only the agreed fields.** An API resource whitelists them, so adding a column later never leaks it.
- **`order_id` only on complete applications.** The resource uses `$this->when(...)`, so the key is absent on other statuses, not `null`. A test gives every other status an `order_id` to prove it is hidden by status and not by being empty.
- **Paginated.** Uses Laravel's default page size of 15 and the standard `data` / `links` / `meta` envelope an SPA expects. `withQueryString()` keeps `plan_type` in the `next` and `prev` links, so page 2 of a filtered list stays filtered.
- **Oldest first.** Ordered by `created_at`, then `id`. The `id` tie-break keeps page boundaries stable when several applications share a timestamp; without it, rows can repeat or go missing between pages.
- **Human-readable dollars.** `8999` becomes `"$89.99"` and `123456` becomes `"$1,234.56"`. I used `number_format` rather than `Number::currency()` because the Docker image does not include the `intl` extension that `Number::currency()` needs.
- **Filter.** A missing or empty `plan_type` lists everything. An unknown value returns `422` with a validation error, so a typo is not silently treated as "all".
- **Authentication.** A request without a valid token gets `401 {"message":"Unauthenticated."}`, even from a plain browser or `curl` that does not send `Accept: application/json`. Out of the box, Laravel redirects such guests to a `login` route, which this API-only app does not define, so the request crashed with a 500. `bootstrap/app.php` now skips that redirect for `api/*` and always renders API errors as JSON. This was found while exercising the endpoint in a real browser: the original test used `getJson()`, which always sends the JSON header, so it could not see the bug.
- **Scalability.** `customer` and `plan` are eager loaded. `AppServiceProvider` enables `Model::preventLazyLoading()` outside production, so an N+1 regression fails the test suite instead of reaching production.

### Interpretation to confirm

- **"State"** is read as the `applications.state` column (NSW, VIC, …), since the field list mirrors column names. The application status is not in the requested list, so it is not returned. If "State" meant status, it is a one-line change in the resource.
- **"Address"** is returned as an object (`address_1`, `address_2`, `city`, `postcode`) so the SPA controls formatting. `state` sits beside it as its own requested field.

## Task 2: automated nbn ordering

```
every 5 min ─▶ applications:order-nbn ─▶ for each nbn application in "order":
                (QueueNbnOrders)             dispatch OrderNbnApplication ─▶ queue
                                                                              │
                                   worker ◀───────────────────────────────────┘
                                     │  Http::post(NBN_B2B_ENDPOINT, address + plan name)
                                     ├─ 2xx and status "Successful" and an id ─▶ order_id = id, status = complete
                                     └─ anything else, or an exception       ─▶ status = order failed
```

| File | Role |
| --- | --- |
| `routes/console.php` | `Schedule::command(QueueNbnOrders::class)->everyFiveMinutes()->withoutOverlapping()` |
| `app/Console/Commands/QueueNbnOrders.php` | Selects nbn applications in `order` (`lazyById`, so memory stays flat) and dispatches one job each. |
| `app/Jobs/OrderNbnApplication.php` | Places one order and records the outcome. |

Against the task's lettered requirements:

- **a. Every five minutes.** A scheduled command. It is also runnable by hand (`php artisan applications:order-nbn`), which helps operations and testing.
- **b. Only `order` status.** Enforced in the query with `withStatus(Order)` and `ofPlanType(Nbn)`.
- **c. One application per queued job.** `OrderNbnApplication implements ShouldQueue`.
- **d. Success stores the order id and moves to `complete`.** Success requires all three of: a 2xx response, `status === "Successful"`, and a non-empty `id`. The fail stub has `"id": ""`, so the empty-id case must not count as success.
- **e. Failure or error moves to `order failed`.** A `Failed` body or a non-2xx response is handled in `handle()`. Exceptions such as timeouts and connection errors are handled in `failed()`. Both paths log the application id and the reason.

The payload is `address_1`, `address_2`, `city`, `state`, `postcode` and `plan_name`. The endpoint comes from `config('services.nbn.endpoint')`, which is already wired to `NBN_B2B_ENDPOINT`.

### Safety

Placing an order is not idempotent: a duplicate order is a real-world problem, not just a data problem. So the job is built to never order the same application twice:

- **`$tries = 1`.** A failure is not retried automatically. It lands in `order failed` for a person to review, which is what requirement (e) asks for.
- **`ShouldBeUnique`, keyed on application id.** If workers fall behind, the next five-minute run cannot queue a second job for an application that is still waiting. `uniqueFor` releases the lock after an hour in case a worker dies mid-job.
- **Status re-check.** The model is re-read when the job runs, and the job does nothing unless the application is still in `order`.
- **Missing endpoint.** If `NBN_B2B_ENDPOINT` is empty, the command exits non-zero and queues nothing. Otherwise a missing environment variable would push every nbn application to `order failed` within five minutes.

## Tests

All HTTP is faked. The base `TestCase` calls `Http::preventStrayRequests()`, so any unfaked request fails the test rather than reaching the network.

| File | Covers |
| --- | --- |
| `tests/Feature/Api/ListApplicationsTest.php` | Guests get 401, with and without a JSON `Accept` header; the exact field set; `order_id` only when complete; oldest first; stable tie-break; filter by each plan type; no filter; empty filter; invalid filter returns 422; page size and page 2; filter kept in pagination links; dollar formatting. |
| `tests/Feature/Jobs/OrderNbnApplicationTest.php` | Success using the provided stub; exact payload and URL; failed stub; 400/422/500/503 responses; "Successful" with an empty id; connection error; an application that left `order` is not re-ordered. |
| `tests/Feature/Console/QueueNbnOrdersTest.php` | Scheduled at `*/5 * * * *`; only nbn applications in `order` are queued; no duplicate while a job is pending; nothing queued without an endpoint; end to end through the sync queue to `complete` and `order failed`. |
| `tests/Unit/Models/*` | `full_name` with and without a last name; cents-to-dollars formatting. |

**Checking the tests themselves.** I broke the implementation on purpose, one change at a time, and confirmed at least one test failed each time. The changes were: removing eager loading, `withQueryString`, the oldest-first order, the `id` tie-break, `ShouldBeUnique`, the status re-check, the `failed()` handler, and the `order_id` condition, plus scheduling every ten minutes instead of five.

**End-to-end run.** Beyond the suite, I ran the real stack locally with the `database` queue driver and `queue:work`, against a throwaway local server that returned the two stub files. One application completed with `ORD000000000000`, one moved to `order failed`, and mobile and opticomm applications were untouched. Running the command twice before the worker started queued each application only once. That run is what led to the last commit: the command used to print "Queued 2" on a run where uniqueness had actually skipped both.

## Changes to existing code

- **`database/factories/UserFactory.php`.** It hardcoded a cost-10 bcrypt hash, but `phpunit.xml` sets `BCRYPT_ROUNDS=4`, and Laravel 12's `hashed` cast rejects a hash it cannot verify against the configured cost. As shipped, any test calling `User::factory()` threw `Could not verify the hashed value's configuration`. It now uses the current Laravel skeleton's `Hash::make('password')`, memoised across the run.
- **`database/factories/PlanFactory.php`** picks from `PlanType::cases()`.
- **`bootstrap/app.php`.** Guests on `api/*` routes are no longer redirected to the undefined `login` route, and API errors always render as JSON (see Task 1, Authentication). This also fixes the existing `/api/user` route, which had the same 500.
- **`routes/api.php`.** The existing `/user` route moved into an `auth:sanctum` group alongside the new route. Its behaviour is unchanged.

## Assumptions and questions

- The B2B API's HTTP status for a failed order is not specified; the fail stub could arrive as a 200. Both a non-2xx response and a 2xx with a non-`Successful` status are treated as failures.
- `customers` has a `deleted_at` column, but the model does not use `SoftDeletes`. I left that alone because it is outside both tasks. Whether applications of deleted customers should be listed is worth confirming.
- "The appropriate B2B integration for the plan type" suggests opticomm and mobile will follow. Only nbn is in scope, so I did not abstract an integration layer ahead of a second implementation.

## With more time

- **Idempotency at the provider.** Send the application id as the order's `externalId` (the stubs carry one), so the B2B side can reject duplicates even if something upstream failed.
- **Record why an order failed.** Store the failure reason (HTTP status, `note[].text`), for example in an `order_attempts` table, so staff reviewing `order failed` applications do not need the logs. Notify the relevant team when an order fails.
- **A B2B client class.** Move the HTTP call behind an `NbnB2bClient`, and later an interface chosen by plan type, when a second integration arrives.
- **Indexes.** Add `applications (created_at, id)` for the listing and `applications (status)` for the scheduler query once volumes grow.
- **Page size.** Allow a bounded `per_page` parameter if the SPA needs it.
- **Money formatting.** Add `intl` to the image and use locale-aware formatting, or a small `Money` value object, if prices appear in more places.
- **Resilience.** Apply `RateLimited` or throttling job middleware to protect the B2B API when a large batch reaches `order`.
- **Local stack.** Add `worker` and `scheduler` services to `compose.yaml`. I left them out because the scaffold deliberately does not prescribe them.
