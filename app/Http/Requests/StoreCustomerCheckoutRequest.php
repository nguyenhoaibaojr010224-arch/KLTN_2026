<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.ma_thuoc' => ['required', 'string', 'exists:thuocs,ma_thuoc'],
            'items.*.so_luong' => ['required', 'integer', 'min:1'],
            'items.*.don_vi' => ['nullable', 'string', 'max:50'],
            'phuong_thuc_thanh_toan' => ['required', 'in:cod,payos'],
            'ma_giam_gia' => ['nullable', 'string', 'max:30'],
            'su_dung_diem' => ['nullable', 'boolean'],
            'dia_chi_giao_hang' => ['nullable', 'string', 'max:255'],
            'ghi_chu' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Vui lòng chọn ít nhất một sản phẩm để đặt hàng.',
            'items.min' => 'Vui lòng chọn ít nhất một sản phẩm để đặt hàng.',
            'items.*.ma_thuoc.exists' => 'Có sản phẩm không còn tồn tại trong hệ thống.',
            'items.*.so_luong.min' => 'Số lượng đặt mua phải lớn hơn 0.',
            'phuong_thuc_thanh_toan.required' => 'Vui lòng chọn phương thức thanh toán.',
            'phuong_thuc_thanh_toan.in' => 'Phương thức thanh toán chỉ hỗ trợ Tiền mặt hoặc PayOS.',
        ];
    }
}
