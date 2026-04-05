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
            'phuong_thuc_thanh_toan' => ['required', 'in:cod,momo,zalopay,atm,international'],
            'ma_giam_gia' => ['nullable', 'string', 'max:30'],
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
        ];
    }
}
