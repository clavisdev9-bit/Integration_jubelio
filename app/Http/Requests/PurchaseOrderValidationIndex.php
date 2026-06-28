<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class PurchaseOrderValidationIndex extends FormRequest
{
    protected array $allowedSortFields = [
        'purchaseorder_no',
        'supplier_name',
        'status',
        'transaction_date',
        'grand_total',
        'created_at',
        'updated_at'
    ];

    public function authorize(): bool
    {
        return true;
    }

    public function setAllowedSortFields(array $fields): void
    {
        $this->allowedSortFields = $fields;
    }

    public function rules(): array
    {
        return [

            /*
            |--------------------------------------------------------------------------
            | Search & Filter
            |--------------------------------------------------------------------------
            */

            'search' => 'nullable|string',

            'supplier' => 'nullable|string',

            'status' => 'nullable|string',

            'location_id' => 'nullable|integer',

            'date_from' => 'nullable|date',

            'date_to' => 'nullable|date',

            /*
            |--------------------------------------------------------------------------
            | Sorting
            |--------------------------------------------------------------------------
            */

            'sort_by' => [
                'nullable',
                'in:' . implode(',', $this->allowedSortFields)
            ],

            'sort_dir' => 'nullable|in:asc,desc',

            /*
            |--------------------------------------------------------------------------
            | Pagination
            |--------------------------------------------------------------------------
            */

            'page' => 'nullable|integer|min:1',

            'per_page' => 'nullable|integer|min:1|max:100',

            /*
            |--------------------------------------------------------------------------
            | Soft Delete
            |--------------------------------------------------------------------------
            */

            'only_deleted' => 'nullable|boolean',

        ];
    }

    protected function prepareForValidation(): void
    {
        $allowed = array_keys($this->rules());

        $unknown = array_diff(
            array_keys($this->all()),
            $allowed
        );

        if (! empty($unknown)) {

            throw new HttpResponseException(
                response()->json([
                    'message' => 'Field tidak dikenali.',
                    'errors' => collect($unknown)->mapWithKeys(
                        fn ($field) => [
                            $field => ['Field ini tidak valid.']
                        ]
                    )
                ], 422)
            );

        }
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422)
        );
    }

    public function messages(): array
    {
        return [

            'supplier.string' =>
                'Supplier harus berupa text.',

            'status.string' =>
                'Status harus berupa text.',

            'location_id.integer' =>
                'Location ID harus berupa angka.',

            'date_from.date' =>
                'Format date_from harus YYYY-MM-DD.',

            'date_to.date' =>
                'Format date_to harus YYYY-MM-DD.',

            'sort_by.in' =>
                'Kolom sort_by tidak valid. Pilihan: '
                . implode(', ', $this->allowedSortFields),

            'sort_dir.in' =>
                'sort_dir hanya boleh asc atau desc.',

            'per_page.integer' =>
                'Per page harus berupa angka.',

            'per_page.min' =>
                'Per page minimal 1.',

            'per_page.max' =>
                'Per page maksimal 100.',

            'only_deleted.boolean' =>
                'only_deleted hanya boleh true/false atau 1/0.',

        ];
    }
}