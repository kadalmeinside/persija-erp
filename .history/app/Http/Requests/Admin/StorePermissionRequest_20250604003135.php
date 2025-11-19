<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePermissionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('manage permissions');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $guardName = $this->input('guard_name', 'web'); // Default ke 'web' jika tidak diisi
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('permissions')->where(function ($query) use ($guardName) {
                    return $query->where('guard_name', $guardName);
                }),
            ],
            'guard_name' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'Permission name already exists for this guard.',
        ];
    }
}
