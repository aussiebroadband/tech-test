<?php

namespace App\Http\Requests;

use App\Enums\PlanType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListApplicationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'plan_type' => ['nullable', Rule::enum(PlanType::class)],
        ];
    }

    /**
     * The requested plan type filter, or null to list every application.
     */
    public function planType(): ?PlanType
    {
        return $this->enum('plan_type', PlanType::class);
    }
}
