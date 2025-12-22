<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApplicationListRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Route is already protected by auth:sanctum
        return true;
    }

    public function rules(): array
    {
        return [
            'plan_type' => ['nullable', Rule::in(['nbn', 'opticomm', 'mobile'])],
            'per_page'  => ['nullable', 'integer'],
            'page'      => ['nullable', 'integer'],
        ];
    }
}
