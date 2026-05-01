<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateChiTietHoaDonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_lo' => ['sometimes', 'required', 'exists:lo_thuocs,id_lo'],
            'so_luong' => ['sometimes', 'required', 'integer', 'min:1'],
            'gia_ban' => ['sometimes', 'required', 'numeric', 'gt:0'],
        ];
    }
}
