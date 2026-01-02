<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListApplicationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plan_type' => ['nullable', 'string', 'in:nbn,opticomm,mobile'],
        ];
    }
}
