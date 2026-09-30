<?php

namespace Tests\Feature\Jobs;

use App\Enums\ApplicationStatus;
use App\Enums\PlanType;
use App\Jobs\OrderNbnApplication;
use App\Models\Application;
use App\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class OrderNbnApplicationTest extends TestCase
{
    use RefreshDatabase;

    private const ENDPOINT = 'https://b2b.nbn.test/orders';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.nbn.endpoint' => self::ENDPOINT]);
    }

    public function test_a_successful_order_stores_the_order_id_and_completes_the_application(): void
    {
        Http::fake([self::ENDPOINT => Http::response($this->stub('nbn-successful-response'))]);

        $application = $this->nbnApplicationAwaitingOrder();

        OrderNbnApplication::dispatch($application);

        $application->refresh();
        $this->assertSame(ApplicationStatus::Complete, $application->status);
        $this->assertSame('ORD000000000000', $application->order_id);
    }

    public function test_the_application_and_plan_details_are_posted_to_the_b2b_endpoint(): void
    {
        Http::fake([self::ENDPOINT => Http::response($this->stub('nbn-successful-response'))]);

        OrderNbnApplication::dispatch($this->nbnApplicationAwaitingOrder());

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request) => $request->method() === 'POST'
            && $request->url() === self::ENDPOINT
            && $request->data() === [
                'address_1' => '1 Example Street',
                'address_2' => 'Unit 2',
                'city' => 'Morwell',
                'state' => 'VIC',
                'postcode' => '3840',
                'plan_name' => 'nbn 100/20 Fibre',
            ]);
    }

    public function test_a_failed_order_moves_the_application_to_order_failed(): void
    {
        Http::fake([self::ENDPOINT => Http::response($this->stub('nbn-fail-response'))]);

        $application = $this->nbnApplicationAwaitingOrder();

        OrderNbnApplication::dispatch($application);

        $application->refresh();
        $this->assertSame(ApplicationStatus::OrderFailed, $application->status);
        $this->assertNull($application->order_id);
    }

    #[DataProvider('errorResponses')]
    public function test_an_error_response_moves_the_application_to_order_failed(int $status): void
    {
        Http::fake([self::ENDPOINT => Http::response(['message' => 'Something went wrong'], $status)]);

        $application = $this->nbnApplicationAwaitingOrder();

        OrderNbnApplication::dispatch($application);

        $application->refresh();
        $this->assertSame(ApplicationStatus::OrderFailed, $application->status);
        $this->assertNull($application->order_id);
    }

    public static function errorResponses(): array
    {
        return [
            'bad request' => [400],
            'unprocessable' => [422],
            'server error' => [500],
            'unavailable' => [503],
        ];
    }

    public function test_a_successful_status_without_an_order_id_is_treated_as_a_failure(): void
    {
        Http::fake([self::ENDPOINT => Http::response(['id' => '', 'status' => 'Successful'])]);

        $application = $this->nbnApplicationAwaitingOrder();

        OrderNbnApplication::dispatch($application);

        $this->assertSame(ApplicationStatus::OrderFailed, $application->refresh()->status);
    }

    public function test_a_connection_error_moves_the_application_to_order_failed(): void
    {
        Http::fake(fn () => throw new ConnectionException('Connection timed out'));

        $application = $this->nbnApplicationAwaitingOrder();

        // The sync queue re-throws after running the job's failed() handler.
        $this->assertThrows(function () use ($application) {
            OrderNbnApplication::dispatch($application);
        }, ConnectionException::class);

        $application->refresh();
        $this->assertSame(ApplicationStatus::OrderFailed, $application->status);
        $this->assertNull($application->order_id);
    }

    public function test_an_application_that_has_left_the_order_status_is_not_ordered(): void
    {
        Http::fake();

        $application = $this->nbnApplicationAwaitingOrder();
        $job = new OrderNbnApplication($application);

        // Completed elsewhere between being queued and the job running.
        $application->markAsOrdered('ORD-EARLIER');

        dispatch($job);

        Http::assertNothingSent();
        $application->refresh();
        $this->assertSame(ApplicationStatus::Complete, $application->status);
        $this->assertSame('ORD-EARLIER', $application->order_id);
    }

    private function nbnApplicationAwaitingOrder(): Application
    {
        return Application::factory()
            ->for(Plan::factory()->state(['type' => PlanType::Nbn, 'name' => 'nbn 100/20 Fibre']))
            ->create([
                'status' => ApplicationStatus::Order,
                'address_1' => '1 Example Street',
                'address_2' => 'Unit 2',
                'city' => 'Morwell',
                'state' => 'VIC',
                'postcode' => '3840',
            ]);
    }

    private function stub(string $name): array
    {
        return json_decode(file_get_contents(base_path("tests/stubs/{$name}.json")), true, flags: JSON_THROW_ON_ERROR);
    }
}
