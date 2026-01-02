<?php

namespace App\Http\Resources;

use App\Enums\ApplicationStatus;
use Illuminate\Http\Resources\Json\JsonResource;

class ApplicationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'customer_full_name' => $this->customer->full_name,
            'address' => $this->full_address,
            'plan_type' => $this->plan->type,
            'plan_name' => $this->plan->name,
            'state' => $this->state,
            'plan_monthly_cost' => $this->plan->monthly_cost_in_dollars,
            'order_id' => $this->when(
                $this->status === ApplicationStatus::Complete,
                $this->order_id
            ),
        ];
    }
}
