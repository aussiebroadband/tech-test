<?php

namespace App\Http\Resources;

use App\Enums\ApplicationStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApplicationListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $addressParts = [
            $this->address_1,
            $this->address_2,
            $this->city,
            $this->postcode,
        ];

        //remove empty values
        $addressParts = array_filter($addressParts, function ($value) {
            if ($value === null) {
                return false;
            }

            $value = trim((string) $value);

            return $value !== '';
        });

        $address = implode(', ', $addressParts);//merge address

        return [
            'application_id'      => $this->id,
            'customer_full_name'  => $this->customer->full_name,
            'address'             => $address,
            'plan_type'           => $this->plan?->type,
            'plan_name'           => $this->plan?->name,
            'state'               => $this->state,
            'plan_monthly_cost'   => $this->plan?->monthly_cost_dollars,
            'order_id'            => $this->when(
                $this->status === ApplicationStatus::Complete,
                $this->order_id
            ),
        ];
    }
}
