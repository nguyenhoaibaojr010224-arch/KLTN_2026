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
            'so_hoa_don_giay' => ['nullable', 'string', 'max:100'],
            'ngay_hoa_don' => ['nullable', 'date'],
            'chung_tu_url' => ['nullable', 'string', 'max:255'],
            'ghi_chu' => ['nullable', 'string'],
            'chi_tiets' => ['nullable', 'array', 'min:1'],
            'chi_tiets.*.id_thuoc' => ['required_with:chi_tiets', 'string', 'exists:thuocs,ma_thuoc'],
            'chi_tiets.*.so_lo' => ['required_with:chi_tiets', 'string', 'max:255', 'distinct', 'unique:lo_thuocs,so_lo'],
            'chi_tiets.*.ngay_san_xuat' => ['required_with:chi_tiets', 'date'],
            'chi_tiets.*.han_su_dung' => ['required_with:chi_tiets', 'date'],
            'chi_tiets.*.don_vi_nhap' => ['required_with:chi_tiets', 'string', 'max:50'],
            'chi_tiets.*.so_luong_nhap_goc' => ['required_with:chi_tiets', 'integer', 'min:1'],
            'chi_tiets.*.gia_nhap' => ['required_with:chi_tiets', 'numeric', 'gt:0'],
        ];
    }
}
