<?php

namespace App\Http\Resources;

use App\Enums\ApplicationStatus;
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
        return [
            'id' => $this->id,
            'customer' => sprintf('%s %s', $this->customer->first_name, $this->customer->last_name),
            'address' => sprintf('%s %s', $this->address_1, $this->address_2),
            'status' => $this->status,
            'plan' => $this->whenLoaded('plan', function () {
                return [
                    'name' => $this->plan->name,
                    'type' => $this->plan->type,
                    'monthly_cost' => '$' . number_format($this->plan->monthly_cost, 2),
                ];
            }),
            'order_id' => $this->when($this->status === ApplicationStatus::Complete, $this->order_id)
        ];
    }
}
