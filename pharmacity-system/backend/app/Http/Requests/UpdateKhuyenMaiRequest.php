<?php

namespace App\Http\Requests;

class UpdateKhuyenMaiRequest extends StoreKhuyenMaiRequest
{
    public function rules(): array
    {
        $rules = parent::rules();

        foreach ($rules as $field => $fieldRules) {
            $rules[$field] = array_merge(['sometimes'], $fieldRules);
        }

        return $rules;
    }
}
