<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreKhuyenMaiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ma_thuoc' => ['required', 'exists:thuocs,ma_thuoc'],
            'ten_khuyen_mai' => ['required', 'string', 'min:3', 'max:150'],
            'mo_ta' => ['nullable', 'string', 'max:1000'],
            'loai_ap_dung' => ['required', Rule::in(['phan_tram', 'so_tien', 'gia_co_dinh'])],
            'gia_tri' => ['required', 'numeric', 'min:1'],
            'nhan_hien_thi' => ['nullable', 'string', 'max:100'],
            'ngay_bat_dau' => ['required', 'date'],
            'ngay_ket_thuc' => ['nullable', 'date', 'after_or_equal:ngay_bat_dau'],
            'trang_thai' => ['required', Rule::in(['draft', 'active', 'inactive'])],
        ];
    }
}
