<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreChiTietHoaDonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_lo' => ['required', 'exists:lo_thuocs,id_lo'],
            'so_luong' => ['required', 'integer', 'min:1'],
            'gia_ban' => ['nullable', 'numeric', 'gt:0'],
        ];
    }
}
