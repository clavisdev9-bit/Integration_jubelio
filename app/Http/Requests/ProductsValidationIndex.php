<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ProductsValidationIndex extends FormRequest
{
    protected array $allowedSortFields = ['item_name', 'created_at'];

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

            'date_from'    => ['nullable', 'date'],
            'date_to'      => ['nullable', 'date', 'after_or_equal:date_from'],
        ];
    }

   
    protected function prepareForValidation(): void
    {
        // optional normalization (kalau mau)
        $this->merge([
            'search' => trim((string) $this->search),
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
            'per_page.integer' => 'Per page harus berupa angka.',
            'per_page.min' => 'Per page minimal 1.',
            'per_page.max' => 'Per page maksimal 100.',
            'only_deleted.boolean' => 'only_deleted harus true/false atau 1/0.',
        ];
    }
}