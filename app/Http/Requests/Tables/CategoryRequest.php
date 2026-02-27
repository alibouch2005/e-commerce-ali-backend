<?php

namespace App\Http\Requests\Tables;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        if ($this->user() && $this->user()->role === 'admin') {
            return true; // L'utilisateur est autorisé à faire cette requête s'il est authentifié et a le rôle d'administrateur
        }
        return false; // L'utilisateur n'est pas autorisé à faire cette requête
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                Rule::unique('categories', 'name')->ignore($this->category)
            ],
            'description' => 'nullable|string'
        ];
    }
}
