<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class LocationValidationIndex extends FormRequest
{
    protected array $allowedSortFields = [
        'location_code',
        'location_name',
        'created_at',
        'updated_at',
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

            'search' => 'nullable|string',

            'sort_by' => [
                'nullable',
                'in:' . implode(',', $this->allowedSortFields),
            ],

            'sort_dir' => 'nullable|in:asc,desc',

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

        if (count($unknown) > 0) {

            throw new HttpResponseException(

                response()->json([

                    'message' => 'Field tidak dikenali.',

                    'errors' => collect($unknown)
                        ->mapWithKeys(fn($field) => [

                            $field => [
                                'Field ini tidak valid.'
                            ]

                        ])

                ], 422)

            );

        }
    }

    protected function failedValidation(
        Validator $validator
    ) {

        throw new HttpResponseException(

            response()->json([

                'message' => 'Validasi gagal.',

                'errors' => $validator->errors()

            ], 422)

        );

    }

    public function messages(): array
    {
        return [

            'sort_by.in' =>
                'Kolom sort_by tidak valid. Pilihan: '
                . implode(', ', $this->allowedSortFields),

            'sort_dir.in' =>
                'sort_dir hanya boleh asc atau desc.',

            'only_deleted.boolean' =>
                'only_deleted harus bernilai 1 atau 0.',

        ];
    }
}