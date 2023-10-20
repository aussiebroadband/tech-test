<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use \App\Models\User;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\Hash;
use App\Enums\PlanType;

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

    public function testGetAllApplications()
    {
        // Create a user and authenticate them using actingAs
        $user = User::factory()->create();
        // Generate a token for the user
        $token = $user->createToken('test-token')->plainTextToken;
        // Authenticate the user by acting as that user
        $this->actingAs($user);
        // Make a request to get all applications
        $responseApplications = $this->get('api/applications', [
            'Authorization' => "Bearer $token",
        ]);
        // AssertTrue for all applications
        $responseApplications->assertStatus(200);
        $planTypes = PlanType::cases();
        foreach($planTypes as $key => $value) {
            // Filter by all types of plan type for get applications endpoint
            $responseApplicationsPlanType = $this->get('api/applications/' . $value->name, [
                'Authorization' => "Bearer $token",
            ]);
        }
        // Assert for all applications by plan type filter
        $responseApplicationsPlanType->assertStatus(200);

        
    }
}
