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
        $customerFullName = trim(implode(' ', array_filter([
            $this->customer?->first_name,
            $this->customer?->last_name,
        ])));

        $addressParts = array_values(array_filter([
            $this->address_1,
            $this->address_2,
            trim($this->city . ' ' . $this->postcode),
        ], fn ($part) => $part !== null && $part !== ''));

        $monthlyCostCents = (int) ($this->plan?->monthly_cost ?? 0);

        return [
            'application_id' => $this->id,
            'customer_full_name' => $customerFullName,
            'address' => implode(', ', $addressParts),
            'plan_type' => $this->plan?->type,
            'plan_name' => $this->plan?->name,
            'state' => $this->state,
            'plan_monthly_cost' => $this->formatMonthlyCost($monthlyCostCents),
            $this->mergeWhen($this->status === ApplicationStatus::Complete, [
                'order_id' => $this->order_id,
            ]),
        ];
    }

    private function formatMonthlyCost(int $cents): string
    {
        $sign = $cents < 0 ? '-' : '';
        $cents = abs($cents);

        return sprintf('%s%d.%02d', $sign, intdiv($cents, 100), $cents % 100);
    }
}
