<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class SupplierValidationIndex extends FormRequest
{
    protected array $allowedSortFields = ['contact_name', 'created_at'];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search'       => ['nullable', 'string'],
            'per_page'     => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort_by'      => ['nullable', 'in:' . implode(',', $this->allowedSortFields)],
            'sort_dir'     => ['nullable', 'in:asc,desc'],
            'page'         => ['nullable', 'integer', 'min:1'],
            'only_deleted' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        // SAFE normalization only (bukan strict validation)
        $this->merge([
            'search' => $this->search ? trim($this->search) : null,
            'sort_dir' => strtolower($this->sort_dir ?? 'asc'),
        ]);
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'message' => 'Validasi gagal',
            'errors' => $validator->errors(),
        ], 422));
    }

    public function messages(): array
    {
        return [
            'sort_by.in' => 'Kolom sort_by tidak valid. Hanya: ' . implode(', ', $this->allowedSortFields),
            'sort_dir.in' => 'sort_dir harus asc atau desc.',
            'per_page.integer' => 'per_page harus berupa angka.',
            'per_page.min' => 'per_page minimal 1.',
            'per_page.max' => 'per_page maksimal 100.',
            'only_deleted.boolean' => 'only_deleted harus true/false atau 1/0.',
        ];
    }
}