<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateMaGiamGiaRequest extends StoreMaGiamGiaRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $maGiamGiaId = $this->route('id');

        $rules['ma_giam_gia'] = [
            'sometimes',
            'required',
            'string',
            'min:4',
            'max:30',
            'regex:/^[A-Z0-9-]+$/',
            Rule::unique('ma_giam_gias', 'ma_giam_gia')->ignore($maGiamGiaId),
        ];

        foreach ($rules as $field => $fieldRules) {
            if ($field === 'ma_giam_gia') {
                continue;
            }

            $rules[$field] = array_merge(['sometimes'], $fieldRules);
        }

        return $rules;
    }
}
