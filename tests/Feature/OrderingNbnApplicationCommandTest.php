<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OrderingNbnApplicationCommandTest extends TestCase
{
    private const COMMAND = 'app:ordering-nbn-application';

    public function test_should_only_process_with_status_order_and_nbn_application(): void {
        $application = Application::factory()->prelim()->create();

        $this->artisan(self::COMMAND)->assertExitCode(0);

        // the datasets should be stayed the same since it didn't satisfy the condition
        $this->assertDatabaseHas('applications', [
            'id' => $application->id,
            'status' => ApplicationStatus::Prelim,
            'order_id' => null
        ]);
    }

    public function test_store_order_id_and_complete_status_if_successful(): void {
        $application = Application::factory()->orderingNbnApplication()->create();
        $stub = file_get_contents(base_path('tests/stubs/nbn-successful-response.json'));
        $response = json_decode($stub, true);
        Http::fake([
            config('app.nbn_b2b_endpoint') => Http::response($response)
        ]);

        $this->artisan(self::COMMAND)->assertExitCode(0);

        $this->assertDatabaseHas('applications', [
            'id' => $application->id,
            'order_id' => $response['id'],
            'status' => ApplicationStatus::Complete
        ]);
    }

    public function test_order_failed_if_unsuccessful(): void {
        $application = Application::factory()->orderingNbnApplication()->create();
        $stub = file_get_contents(base_path('tests/stubs/nbn-fail-response.json'));
        $response = json_decode($stub, true);
        Http::fake([
            config('app.nbn_b2b_endpoint') => Http::response($response)
        ]);

        $this->artisan(self::COMMAND)->assertExitCode(0);

        $this->assertDatabaseHas('applications', [
            'id' => $application->id,
            'status' => ApplicationStatus::OrderFailed
        ]);
    }
}
