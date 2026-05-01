<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLichSuDonHangRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'trang_thai' => ['required', 'in:moi_tao,da_thanh_toan,dang_xu_ly,hoan_tat,huy'],
            'ghi_chu' => ['nullable', 'string'],
            'thoi_gian' => ['nullable', 'date'],
        ];
    }
}
