<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateThongTinNhanVienRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $idNhanVien = $this->route('id');

        return [
            'so_dien_thoai' => ['required', 'string', 'size:10', Rule::unique('thong_tin_nhan_viens', 'so_dien_thoai')->ignore($idNhanVien, 'id_nhan_vien')],
            'email' => ['required', 'email', Rule::unique('thong_tin_nhan_viens', 'email')->ignore($idNhanVien, 'id_nhan_vien')],
            'dia_chi' => ['required', 'string', 'min:5', 'max:100'],
            'ngay_sinh' => ['required', 'date', 'before_or_equal:' . now()->subYears(18)->format('Y-m-d')],
            'ngay_vao_lam' => ['required', 'date', 'before_or_equal:today'],
        ];
    }
}
