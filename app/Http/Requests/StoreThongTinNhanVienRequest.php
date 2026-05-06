<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreThongTinNhanVienRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $idNhanVien = $this->input('id_nhan_vien');

        return [
            'id_nhan_vien' => ['required', 'exists:nhan_viens,id_nhan_vien', 'unique:thong_tin_nhan_viens,id_nhan_vien'],
            'so_dien_thoai' => [
                'required',
                'string',
                'size:10',
                Rule::unique('thong_tin_nhan_viens', 'so_dien_thoai'),
                Rule::unique('nhan_viens', 'ten_dang_nhap')->ignore($idNhanVien, 'id_nhan_vien'),
                Rule::unique('khach_hangs', 'so_dien_thoai'),
            ],
            'email' => ['required', 'email', 'unique:thong_tin_nhan_viens,email'],
            'dia_chi' => ['required', 'string', 'min:5', 'max:100'],
            'ngay_sinh' => ['required', 'date', 'before_or_equal:' . now()->subYears(18)->format('Y-m-d')],
            'ngay_vao_lam' => ['required', 'date', 'before_or_equal:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'so_dien_thoai.required' => 'Vui lòng nhập số điện thoại.',
            'so_dien_thoai.size' => 'Số điện thoại phải gồm đúng 10 chữ số.',
            'so_dien_thoai.unique' => 'Số điện thoại này đã được sử dụng.',
        ];
    }
}
