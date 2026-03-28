<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterKhachHangRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ten_khach_hang' => ['required', 'string', 'min:5', 'max:100'],
            'so_dien_thoai' => ['required', 'regex:/^\d{10}$/', 'unique:khach_hangs,so_dien_thoai'],
            'email' => ['required', 'regex:/^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.com$/', 'unique:khach_hangs,email'],
            'dia_chi' => ['required', 'string', 'min:5', 'max:100'],
            'mat_khau' => [
                'required',
                'string',
                'min:8',
                'regex:/^(?=.*[A-Z])(?=.*[!@#$%^&*(),.?":{}|<>\[\]\/\\\\_\-+=~`;\']).+$/',
                'confirmed',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'ten_khach_hang.required' => 'Vui lòng nhập họ và tên.',
            'ten_khach_hang.min' => 'Họ và tên phải có ít nhất 5 ký tự.',
            'ten_khach_hang.max' => 'Họ và tên không được vượt quá 100 ký tự.',

            'so_dien_thoai.required' => 'Vui lòng nhập số điện thoại.',
            'so_dien_thoai.regex' => 'Số điện thoại phải gồm đúng 10 chữ số.',
            'so_dien_thoai.unique' => 'Số điện thoại này đã được sử dụng.',

            'email.required' => 'Vui lòng nhập email.',
            'email.regex' => 'Email phải đúng định dạng.',
            'email.unique' => 'Email này đã được sử dụng.',

            'dia_chi.required' => 'Vui lòng nhập địa chỉ.',
            'dia_chi.min' => 'Địa chỉ phải có ít nhất 5 ký tự.',
            'dia_chi.max' => 'Địa chỉ không được vượt quá 100 ký tự.',

            'mat_khau.required' => 'Vui lòng nhập mật khẩu.',
            'mat_khau.min' => 'Mật khẩu phải có ít nhất 8 ký tự.',
            'mat_khau.regex' => 'Mật khẩu phải có ít nhất 1 chữ in hoa và 1 ký tự đặc biệt.',
            'mat_khau.confirmed' => 'Xác nhận mật khẩu không khớp.',
        ];
    }
}
