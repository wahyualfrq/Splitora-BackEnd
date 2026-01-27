<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProcessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mode' => 'required|in:split,rename',
            'pdf' => 'required|file|mimes:pdf',
            'excel' => 'nullable|required_if:mode,rename|file|mimes:xlsx,xls',
        ];
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        response()->json([
            'errors' => $validator->errors()
        ], 422)->send();
        exit;
    }
}
