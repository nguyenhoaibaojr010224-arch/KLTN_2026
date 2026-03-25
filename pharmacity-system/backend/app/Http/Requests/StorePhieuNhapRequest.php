<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePhieuNhapRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_nha_san_xuat' => ['required', 'exists:nha_san_xuats,id'],
            'ngay_nhap' => ['nullable', 'date'],
        ];
    }
}
