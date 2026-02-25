<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EvaluationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [

            'status' => 'required|string',

            'education_remark' => 'nullable|string',
            'experience_remark' => 'nullable|string',
            'training_remark' => 'nullable|string',
            'eligibility_remark' => 'nullable|string',

            'education_qualification' => 'nullable|array',
            'education_qualification.*' => 'integer',

            'experience_qualification' => 'nullable|array',
            'experience_qualification.*' => 'integer',

            'training_qualification' => 'nullable|array',
            'training_qualification.*' => 'integer',

            'eligibility_qualification' => 'nullable|array',
            'eligibility_qualification.*' => 'integer',
    
        ];
    }
}
