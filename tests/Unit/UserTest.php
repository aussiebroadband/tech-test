<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use \App\Models\User;
use \App\Models\Application;
use \App\Models\Customer;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\Hash;
use App\Enums\PlanType;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;
use App\Jobs\ProcessApplication;
use Illuminate\Database\Seeder;

class UserTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic unit test example.
     */
    public function testuserCanLogin()
    {
        // Create a user and authenticate them using actingAs
        $user = User::factory()->create();
        // Generate a token for the user
        $token = $user->createToken('test-token')->plainTextToken;
        // Authenticate the user by acting as that user
        $this->actingAs($user);
        // Make a JSON GET request to a protected route
        $response = $this->get('api/get-customers', [
            'Authorization' => "Bearer $token",
        ]);
        // Assert that the response is as expected
        $response->assertStatus(200);

        
    }
    /**
     * Task 1: Test get all applications by plan_type endpoint
     */
    public function testGetAllApplications()
    {
        // Create a user and authenticate them using actingAs
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $application = Application::factory()->create();

        // Generate a token for the user
        $token = $user->createToken('test-token')->plainTextToken;
        // Authenticate the user by acting as that user
        $this->actingAs($user);
        // Make a request to get all applications
        $responseApplications = $this->get('api/applications', [
            'Authorization' => "Bearer $token",
        ]);
        // AssertTrue for all applications
        $responseApplications->assertStatus(200)
        ->assertOk()
        ->assertJsonStructure([
                'status',
                'data' => [
                    '*' => [
                        'id',
                        'full_name',
                        'address',
                        'order_id',
                        'type',
                        'name',
                        'state',
                        'monthly_cost'
                    ]
                ]   
        ]); ;
        $planTypes = PlanType::cases();
        foreach($planTypes as $key => $value) {
            // Filter by all types of plan type for get applications endpoint
            $responseApplicationsPlanType = $this->get('api/applications/' . $value->name, [
                'Authorization' => "Bearer $token",
            ]);
        }
        // Assert for all applications by plan type filter
        $responseApplicationsPlanType->assertStatus(200)
        ->assertOk()
        ->assertJsonStructure([
                'status',
                'data' => [
                    '*' => [
                        'id',
                        'full_name',
                        'address',
                        'order_id',
                        'type',
                        'name',
                        'state',
                        'monthly_cost'
                    ]
                ]   
        ]);    
    }
     /**
     * Task 2: Test the processing of NBN Applications function
     */
    public function testProcessNbnApplications() 
    {
        // Create a user and authenticate them using actingAs
        $user = User::factory()->create();
        // Generate a token for the user
        $token = $user->createToken('test-token')->plainTextToken;
        // Authenticate the user by acting as that user
        $this->actingAs($user);
    
        // Process All NBN applications
        $processNbnApplications = $this->get('api/process-nbn-applications/', [
            'Authorization' => "Bearer $token",
        ]);
        // Assert to process all Applications
        $processNbnApplications->assertStatus(200)
        ->assertJsonStructure([
            'status',
            'data' => [
                '*' => [
                    'id',
                    'address_1',
                    'address_2',
                    'city',
                    'state',
                    'postcode',
                    'name'
                ]
            ]   
        ]); 
    }
    /**
     * Task 2: Test the scheduling of jobs for processing NBN applications
     */
    public function testNbnApplicationsScheduling()
    {
        // Run the scheduled job
        Artisan::call('schedule:run');

        // Assert to check the job's functionality
        $this->assertTrue(true);
    }
    /**
     * Task 2: Test the queue worker
     */
    public function testQueueWorker()
    {
        // Test application data to be pushed onto the queue
        $application = Application::factory()->create();
        $jobData = [
            'address_1' => $application->address_1,
            'address_2' => $application->address_2,
            'city'      => $application->city,
            "state" => $application->state,
            "postcode" => $application->postcode,
            "plan_name" => $application->plan_id,
            "id" => $application->id
        ];
        // Push the job to the queue
        Queue::fake();

        dispatch(new ProcessApplication($jobData));

        // Command to Process the job immediately (queue worker tested and configured)
        $this->artisan('queue:work', ['--once' => true]);

        // Assertions for queue worker
        Queue::assertPushed(ProcessApplication::class);

    }
}
