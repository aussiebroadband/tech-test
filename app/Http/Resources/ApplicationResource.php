<?php

namespace App\Http\Resources;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Application
 */
class ApplicationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'customer_full_name' => $this->customer->full_name,
            'address' => [
                'address_1' => $this->address_1,
                'address_2' => $this->address_2,
                'city' => $this->city,
                'postcode' => $this->postcode,
            ],
            'state' => $this->state,
            'plan_type' => $this->plan->type,
            'plan_name' => $this->plan->name,
            'plan_monthly_cost' => $this->plan->formatted_monthly_cost,
            'order_id' => $this->when($this->status === ApplicationStatus::Complete, $this->order_id),
        ];
    }
}
