<?php

namespace Tests\Feature\Console;

use App\Console\Commands\QueueNbnOrders;
use App\Enums\ApplicationStatus;
use App\Enums\PlanType;
use App\Jobs\OrderNbnApplication;
use App\Models\Application;
use App\Models\Plan;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class QueueNbnOrdersTest extends TestCase
{
    use RefreshDatabase;

    private const ENDPOINT = 'https://b2b.nbn.test/orders';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.nbn.endpoint' => self::ENDPOINT]);
    }

    public function test_it_runs_every_five_minutes(): void
    {
        $event = collect(app(Schedule::class)->events())
            ->first(fn (Event $event) => str_contains($event->command, 'applications:order-nbn'));

        $this->assertNotNull($event, 'The nbn ordering command is not scheduled.');
        $this->assertSame('*/5 * * * *', $event->expression);
    }

    public function test_only_nbn_applications_in_the_order_status_are_queued(): void
    {
        Queue::fake();

        $toOrder = Application::factory()
            ->count(2)
            ->for(Plan::factory()->state(['type' => PlanType::Nbn]))
            ->create(['status' => ApplicationStatus::Order]);

        foreach (ApplicationStatus::cases() as $status) {
            if ($status !== ApplicationStatus::Order) {
                $this->applicationFor(PlanType::Nbn, $status);
            }
        }

        $this->applicationFor(PlanType::Opticomm, ApplicationStatus::Order);
        $this->applicationFor(PlanType::Mobile, ApplicationStatus::Order);

        $this->artisan(QueueNbnOrders::class)
            ->expectsOutput('Found 2 nbn application(s) awaiting order; queued each one not already on the queue.')
            ->assertSuccessful();

        Queue::assertPushed(OrderNbnApplication::class, 2);

        foreach ($toOrder as $application) {
            Queue::assertPushed(OrderNbnApplication::class, fn (OrderNbnApplication $job) => $job->application->is($application));
        }
    }

    public function test_an_application_is_not_queued_again_while_its_order_is_still_pending(): void
    {
        Queue::fake();

        $this->applicationFor(PlanType::Nbn, ApplicationStatus::Order);

        // Two scheduler runs before a worker has picked up the first job.
        $this->artisan(QueueNbnOrders::class)->assertSuccessful();
        $this->artisan(QueueNbnOrders::class)->assertSuccessful();

        Queue::assertPushed(OrderNbnApplication::class, 1);
    }

    public function test_nothing_is_queued_when_the_b2b_endpoint_is_not_configured(): void
    {
        Queue::fake();
        config(['services.nbn.endpoint' => null]);

        $application = $this->applicationFor(PlanType::Nbn, ApplicationStatus::Order);

        $this->artisan(QueueNbnOrders::class)
            ->expectsOutput('NBN_B2B_ENDPOINT is not configured; no applications were queued.')
            ->assertFailed();

        Queue::assertNothingPushed();
        $this->assertSame(ApplicationStatus::Order, $application->refresh()->status);
    }

    public function test_queued_orders_are_processed_through_to_their_final_status(): void
    {
        $succeeds = $this->applicationFor(PlanType::Nbn, ApplicationStatus::Order, ['address_1' => '1 Success Street']);
        $fails = $this->applicationFor(PlanType::Nbn, ApplicationStatus::Order, ['address_1' => '2 Failure Street']);
        $mobile = $this->applicationFor(PlanType::Mobile, ApplicationStatus::Order);

        Http::fake(fn (Request $request) => Http::response($this->stub(
            $request['address_1'] === '1 Success Street' ? 'nbn-successful-response' : 'nbn-fail-response'
        )));

        // phpunit.xml runs the queue on the sync driver, so jobs are processed inline.
        $this->artisan(QueueNbnOrders::class)->assertSuccessful();

        Http::assertSentCount(2);
        $this->assertSame(ApplicationStatus::Complete, $succeeds->refresh()->status);
        $this->assertSame('ORD000000000000', $succeeds->order_id);
        $this->assertSame(ApplicationStatus::OrderFailed, $fails->refresh()->status);
        $this->assertNull($fails->order_id);
        $this->assertSame(ApplicationStatus::Order, $mobile->refresh()->status);
    }

    private function applicationFor(PlanType $type, ApplicationStatus $status, array $attributes = []): Application
    {
        return Application::factory()
            ->for(Plan::factory()->state(['type' => $type]))
            ->create(['status' => $status, ...$attributes]);
    }

    private function stub(string $name): array
    {
        return json_decode(file_get_contents(base_path("tests/stubs/{$name}.json")), true, flags: JSON_THROW_ON_ERROR);
    }
}
