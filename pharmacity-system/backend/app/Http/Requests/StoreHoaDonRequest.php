<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreHoaDonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ma_hoa_don' => ['nullable', 'string', 'max:30', 'unique:hoa_dons,ma_hoa_don'],
            'id_khach_hang' => ['required', 'exists:khach_hangs,id_khach_hang'],
            'tong_tien' => ['required', 'numeric', 'min:0'],
            'giam_gia' => ['nullable', 'numeric', 'min:0', 'lte:tong_tien'],
            'ngay_ban' => ['nullable', 'date'],
        ];
    }
}
