<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateChiTietPhieuNhapRequest extends FormRequest
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
            'gia_nhap' => ['sometimes', 'required', 'numeric', 'gt:0'],
        ];
    }
}
