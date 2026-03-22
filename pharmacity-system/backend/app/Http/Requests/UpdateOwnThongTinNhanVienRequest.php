<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOwnThongTinNhanVienRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $idNhanVien = $this->user()?->id_nhan_vien;

        return [
            'so_dien_thoai' => ['sometimes', 'required', 'string', 'size:10', Rule::unique('thong_tin_nhan_viens', 'so_dien_thoai')->ignore($idNhanVien, 'id_nhan_vien')],
            'email' => ['sometimes', 'required', 'email', Rule::unique('thong_tin_nhan_viens', 'email')->ignore($idNhanVien, 'id_nhan_vien')],
            'dia_chi' => ['sometimes', 'required', 'string', 'min:5', 'max:100'],
        ];
    }
}
