<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreThongTinNhanVienRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_nhan_vien' => ['required', 'exists:nhan_viens,id_nhan_vien', 'unique:thong_tin_nhan_viens,id_nhan_vien'],
            'so_dien_thoai' => ['required', 'string', 'size:10', 'unique:thong_tin_nhan_viens,so_dien_thoai'],
            'email' => ['required', 'email', 'unique:thong_tin_nhan_viens,email'],
            'dia_chi' => ['required', 'string', 'min:5', 'max:100'],
            'ngay_sinh' => ['required', 'date', 'before_or_equal:' . now()->subYears(18)->format('Y-m-d')],
            'ngay_vao_lam' => ['required', 'date', 'before_or_equal:today'],
        ];
    }
}
