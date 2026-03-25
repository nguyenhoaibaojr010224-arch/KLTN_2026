<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreChiTietPhieuNhapRequest extends FormRequest
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
            'gia_nhap' => ['required', 'numeric', 'gt:0'],
        ];
    }
}
