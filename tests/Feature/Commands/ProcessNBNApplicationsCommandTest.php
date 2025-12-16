<?php

namespace Tests\Feature\Commands;

use App\Enums\ApplicationStatus;
use App\Enums\PlanType;
use App\Models\Application;
use App\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcessNBNApplicationsCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test command finds and dispatches NBN applications with order status.
     */
    public function test_command_dispatches_nbn_applications_with_order_status(): void
    {
        // Create NBN plan
        $nbnPlan = Plan::factory()->create(['type' => PlanType::NBN->value]);

        // Create applications with different statuses
        Application::factory()->create([
            'plan_id' => $nbnPlan->id,
            'status' => ApplicationStatus::Order,
        ]);
        
        // Create non-NBN application with order status
        $opticommPlan = Plan::factory()->create(['type' => PlanType::Opticomm->value]);
        Application::factory()->create([
            'plan_id' => $opticommPlan->id,
            'status' => ApplicationStatus::Order,
        ]);

        // Run the command
        $this->artisan('nbn:process-applications')
            ->assertExitCode(0);
    }

    /**
     * Test command with no applications returns appropriate message.
     */
    public function test_command_with_no_applications(): void
    {
        $this->artisan('nbn:process-applications')
            ->expectsOutput('No NBN applications with order status found.')
            ->assertExitCode(0);
    }

    /**
     * Test command only processes NBN plan type.
     */
    public function test_command_ignores_non_nbn_plans(): void
    {
        // Create non-NBN plans
        $mobilePlan = Plan::factory()->create(['type' => PlanType::Mobile->value]);
        $opticommPlan = Plan::factory()->create(['type' => PlanType::Opticomm->value]);

        // Create applications with order status but non-NBN plans
        Application::factory()->create([
            'plan_id' => $mobilePlan->id,
            'status' => ApplicationStatus::Order,
        ]);
        
        Application::factory()->create([
            'plan_id' => $opticommPlan->id,
            'status' => ApplicationStatus::Order,
        ]);

        // Run the command
        $this->artisan('nbn:process-applications')
            ->expectsOutput('No NBN applications with order status found.')
            ->assertExitCode(0);
    }

    /**
     * Test command only processes applications with order status.
     */
    public function test_command_ignores_non_order_status(): void
    {
        $nbnPlan = Plan::factory()->create(['type' => PlanType::NBN->value]);

        // Create applications with non-order statuses
        Application::factory()->create([
            'plan_id' => $nbnPlan->id,
            'status' => ApplicationStatus::Complete,
        ]);
        
        Application::factory()->create([
            'plan_id' => $nbnPlan->id,
            'status' => ApplicationStatus::OrderFailed,
        ]);

        // Run the command
        $this->artisan('nbn:process-applications')
            ->expectsOutput('No NBN applications with order status found.')
            ->assertExitCode(0);
    }
}
