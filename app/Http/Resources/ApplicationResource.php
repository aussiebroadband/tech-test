<?php

namespace App\Http\Resources;

use App\Enums\ApplicationStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApplicationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'customer_full_name' => trim($this->customer->first_name.' '.$this->customer->last_name),
            'address' => collect([
                $this->address_1,
                $this->address_2,
                $this->city,
                $this->state,
                $this->postcode,
            ])->filter()->implode(', '),
            'plan_type' => $this->plan->type,
            'plan_name' => $this->plan->name,
            'state' => $this->state,
            'plan_monthly_cost' => number_format($this->plan->monthly_cost / 100, 2, '.', ''),
            'order_id' => $this->when($this->status === ApplicationStatus::Complete, $this->order_id),
        ];
    }
}
