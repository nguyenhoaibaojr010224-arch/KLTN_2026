<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreThanhToanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_hoa_don' => ['required', 'exists:hoa_dons,id_hoa_don', 'unique:thanh_toan,id_hoa_don'],
            'phuong_thuc' => ['required', 'in:tien_mat,payos'],
            'so_tien' => ['nullable', 'numeric', 'gt:0'],
            'thoi_gian' => ['nullable', 'date'],
            'ma_giao_dich' => ['nullable', 'string', 'max:100', 'unique:thanh_toan,ma_giao_dich'],
        ];
    }
}
