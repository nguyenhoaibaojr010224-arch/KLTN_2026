<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateKhuyenMaiRequest extends StoreKhuyenMaiRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $khuyenMaiId = $this->route('id');

        $rules['ma_khuyen_mai'] = [
            'nullable',
            'string',
            'min:4',
            'max:30',
            'regex:/^[A-Z0-9-]+$/',
            Rule::unique('khuyen_mais', 'ma_khuyen_mai')->ignore($khuyenMaiId),
        ];

        foreach ($rules as $field => $fieldRules) {
            $rules[$field] = array_merge(['sometimes'], $fieldRules);
        }

        return $rules;
    }
}
