# Submission Notes

## Overview

This submission implements two tasks from the technical assessment: an API endpoint for listing applications and an automated NBN order processing system. The implementation follows Laravel conventions using FormRequests, API Resources, Artisan Commands, Queued Jobs, and the Task Scheduler. All functionality is covered by automated tests.

## Getting Started

```bash
composer install
php artisan migrate
php artisan test
```

## Task 1 - API Endpoint for Listing Applications

### Approach

- Leveraged existing `auth:sanctum` middleware to maintain consistency with the `/api/user` pattern
- Implemented `ListApplicationsRequest` for strict input validation
- Created `ApplicationResource` to control response structure and field formatting
- Used eager loading with pagination to ensure scalability

### Implementation Details

- **Endpoint**: `GET /api/applications`
- **Authentication**: Required (Sanctum)
- **Query Parameters**: `plan_type` (optional: `nbn`, `opticomm`, `mobile`)
- **Ordering**: Oldest applications first
- **Pagination**: Default 15 per page

**Response Fields:**
- `id` - Application ID
- `customer_full_name` - Combined first and last name
- `address` - Formatted full address
- `plan_type` - Plan type (nbn/opticomm/mobile)
- `plan_name` - Name of the plan
- `state` - Australian state
- `plan_monthly_cost` - Formatted as dollars (e.g., `$49.99`)
- `order_id` - Only included when status is `complete`

### Files Changed

- `routes/api.php` - Added applications route
- `app/Http/Controllers/Api/ApplicationController.php` - Index action with filtering
- `app/Http/Requests/ListApplicationsRequest.php` - Validation rules
- `app/Http/Resources/ApplicationResource.php` - Response formatting
- `app/Models/Application.php` - Relationships and accessors
- `app/Models/Customer.php` - Full name accessor
- `app/Models/Plan.php` - Dollar formatting accessor

### Test Coverage

`tests/Feature/ApplicationApiTest.php` covers:
- Unauthenticated access returns 401/403
- Authenticated users can list applications
- Response includes correct fields and formatting
- `order_id` only appears for complete status
- Plan type filtering works correctly
- Results ordered by oldest first
- Response is paginated
- Invalid plan type returns validation error

## Task 2 - Automated NBN Order Processing

### Approach

- Used scheduler + command + job pattern for separation of concerns
- Command uses `chunkById()` for memory-efficient processing of large datasets
- Job handles HTTP communication and status updates
- Added `withoutOverlapping()` to prevent duplicate processing

### Implementation Details

- **Schedule**: Every 5 minutes
- **Criteria**: Applications with `status = order` and `plan.type = nbn`
- **Queue**: Each application processed as a separate queued job

**Job Behavior:**
- Sends POST request to `NBN_B2B_ENDPOINT` with application and plan details
- On success (`status: Successful`): Updates to `complete` status, stores `order_id`
- On failure: Updates to `order failed` status

**Payload Sent:**
- `address_1`, `address_2`, `city`, `state`, `postcode`, `plan_name`

### Files Changed

- `app/Console/Kernel.php` - Scheduler configuration
- `app/Console/Commands/ProcessNbnOrders.php` - Dispatcher command
- `app/Jobs/ProcessNbnOrder.php` - Order processing job
- `config/services.php` - NBN endpoint configuration
- `app/Models/Application.php` - Added fillable fields

### Test Coverage

`tests/Feature/ProcessNbnOrdersTest.php` covers:
- Command dispatches jobs only for eligible NBN applications
- Command outputs appropriate messages
- Job updates status to complete on successful response
- Job updates status to order failed on failure response
- Job updates status to order failed on HTTP error
- Job sends correct payload structure
- Job uses POST method
- Command is scheduled every 5 minutes

## Additional Changes

### Bug Fixes

- **CORS Middleware**: Replaced deprecated `Fruitcake\Cors\HandleCors` with Laravel's built-in `Illuminate\Http\Middleware\HandleCors`

### Dependencies Added

- **guzzlehttp/guzzle**: Added because Laravel's HTTP client facade (`Http::post()`, `Http::fake()`, `Http::response()`) depends on Guzzle under the hood. The original project did not include this dependency, causing `Class "GuzzleHttp\Psr7\Response" not found` errors when running tests that use `Http::fake()`. This package is required to:
  - Send HTTP POST requests to the NBN B2B endpoint in the job
  - Mock HTTP responses in tests using `Http::fake()`
  - Create fake response objects using `Http::response()`

### Database

- Added `expires_at` column to `personal_access_tokens` table for Sanctum compatibility and double checking it using Postman

## Running Tests

```bash
# Run all tests
php artisan test

# Run specific test class
php artisan test --filter=ApplicationApiTest
php artisan test --filter=ProcessNbnOrdersTest

# Run specific test method
php artisan test --filter=test_authenticated_user_can_list_applications
php artisan test --filter=test_job_updates_application_to_complete_on_success

# Run specific method in specific class
php artisan test --filter=ApplicationApiTest::test_authenticated_user_can_list_applications
