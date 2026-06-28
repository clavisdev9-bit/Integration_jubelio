<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class SalesOrderValidationIndex extends FormRequest
{
    /**
     * Kolom yang boleh digunakan untuk sorting.
     */
    protected array $allowedSortFields = [
        'salesorder_no',
        'customer_name',
        'transaction_date',
        'grand_total',
        'created_at',
    ];

    /**
     * Authorization
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Setter apabila suatu saat ingin mengganti
     * allowed sort field dari controller.
     */
    public function setAllowedSortFields(array $fields): void
    {
        $this->allowedSortFields = $fields;
    }

    /**
     * Validation Rules
     */
    public function rules(): array
    {
        return [

            'search' => 'nullable|string',

            'status' => 'nullable|string',

            'customer' => 'nullable|string',

            'location_id' => 'nullable|integer',

            'date_from' => 'nullable|date',

            'date_to' => 'nullable|date|after_or_equal:date_from',

            'sort_by' => [
                'nullable',
                'in:' . implode(',', $this->allowedSortFields),
            ],

            'sort_dir' => 'nullable|in:asc,desc',

            'only_deleted' => 'nullable|boolean',

            'page' => 'nullable|integer|min:1',

            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }

    /**
     * Tolak field yang tidak dikenal
     */
    protected function prepareForValidation(): void
    {
        $allowed = array_keys($this->rules());

        $unknown = array_diff(
            array_keys($this->all()),
            $allowed
        );

        if (count($unknown) > 0) {

            throw new HttpResponseException(

                response()->json([

                    'message' => 'Field tidak dikenali',

                    'errors' => collect($unknown)
                        ->mapWithKeys(fn($field) => [

                            $field => [
                                'Field ini tidak valid'
                            ]

                        ])

                ], 422)

            );
        }
    }

    /**
     * Custom Validation Response
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(

            response()->json([

                'message' => 'Validasi gagal',

                'errors' => $validator->errors()

            ], 422)

        );
    }

    /**
     * Custom Error Message
     */
    public function messages(): array
    {
        return [

            'sort_by.in' =>
                'Kolom sort_by tidak valid. Hanya diperbolehkan: '
                . implode(', ', $this->allowedSortFields),

            'sort_dir.in' =>
                'sort_dir hanya boleh asc atau desc.',

            'date_to.after_or_equal' =>
                'date_to harus lebih besar atau sama dengan date_from.',

            'location_id.integer' =>
                'location_id harus berupa angka.',

            'page.integer' =>
                'page harus berupa angka.',

            'page.min' =>
                'page minimal 1.',

            'per_page.integer' =>
                'per_page harus berupa angka.',

            'per_page.min' =>
                'per_page minimal 1.',

            'per_page.max' =>
                'per_page maksimal 100.',

            'only_deleted.boolean' =>
                'only_deleted harus bernilai true/false atau 1/0.',
        ];
    }
}