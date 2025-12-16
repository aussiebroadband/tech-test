<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApplicationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'customer_name' => trim(
                implode(' ', [
                    $this->customer->first_name, 
                    $this->customer->last_name
                ])
            ),
            'address' => trim(
                implode(', ', array_filter([
                    $this->address_1, $this->address_2
                ]))
            ),
            'state' => $this->state,
            'plan_type' => $this->plan->type,
            'plan_name' => $this->plan->name,
            'plan_monthly_cost' => number_format($this->plan->monthly_cost / 100, 2),
        ];

        // Only include order_id if status is 'complete'
        if ($this->status?->value === 'complete') {
            $data['order_id'] = $this->order_id;
        }

        return $data;
    }
}
