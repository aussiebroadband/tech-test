<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Application;
use App\Models\Plan;
use App\Enums\ApplicationStatus;
use App\Jobs\ProcessNbnOrderJob;
use App\Services\Nbn\NbnClientInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class ProcessNbnOrderJobTest extends TestCase
{
    use RefreshDatabase;

    private function bindMockClient(string $mode): void
    {
        $this->app->bind(NbnClientInterface::class, function () use ($mode) {
            return new class($mode) implements NbnClientInterface {
                public function __construct(private string $mode) {}

                public function submitOrder(array $payload): array
                {
                    $file = $this->mode === 'fail'
                        ? base_path('tests/stubs/nbn-fail-response.json')
                        : base_path('tests/stubs/nbn-successful-response.json');

                    return json_decode(file_get_contents($file), true);
                }
            };
        });
    }

    public function test_success_marks_complete_and_sets_order_id(): void
    {
        $this->bindMockClient('success');

        $plan = Plan::factory()->create(['type' => 'nbn']);
        $app = Application::factory()->create([
            'status' => ApplicationStatus::Order,
            'plan_id' => $plan->id,
            'order_id' => null,
        ]);

        (new ProcessNbnOrderJob($app))->handle(app(NbnClientInterface::class));

        $app->refresh();

        $this->assertSame(ApplicationStatus::Complete, $app->status);
        $this->assertSame('ORD000000000000', $app->order_id);

    }

    public function test_failed_response_marks_order_failed(): void
    {
        $this->bindMockClient('fail');

        $plan = Plan::factory()->create(['type' => 'nbn']);
        $app = Application::factory()->create([
            'status' => ApplicationStatus::Order,
            'plan_id' => $plan->id,
            'order_id' => null,
        ]);

        (new ProcessNbnOrderJob($app))->handle(app(NbnClientInterface::class));

        $app->refresh();

        $this->assertSame(ApplicationStatus::OrderFailed, $app->status);
        $this->assertNull($app->order_id);
    }
}
