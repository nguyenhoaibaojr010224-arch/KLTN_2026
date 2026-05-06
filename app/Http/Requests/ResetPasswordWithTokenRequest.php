<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordWithTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'exists:khach_hangs,email'],
            'token' => ['required', 'string'],
            'password' => [
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
            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Email chưa đúng định dạng.',
            'email.exists' => 'Email này chưa đăng ký tài khoản.',
            'token.required' => 'Vui lòng nhập mã xác minh.',
            'password.required' => 'Vui lòng nhập mật khẩu mới.',
            'password.min' => 'Mật khẩu mới phải có ít nhất 8 ký tự.',
            'password.regex' => 'Mật khẩu mới phải có ít nhất 1 chữ in hoa và 1 ký tự đặc biệt.',
            'password.confirmed' => 'Xác nhận mật khẩu mới không khớp.',
        ];
    }
}
