<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('password') && $this->filled('mat_khau')) {
            $this->merge([
                'password' => $this->input('mat_khau'),
            ]);
        }

        if (! $this->filled('tai_khoan') && $this->filled('so_dien_thoai')) {
            $this->merge([
                'tai_khoan' => $this->input('so_dien_thoai'),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'tai_khoan' => ['required', 'string', 'max:100'],
            'so_dien_thoai' => ['nullable', 'string', 'max:100'],
            'password' => ['required', 'string', 'min:6'],
            'kenh_dang_nhap' => ['nullable', 'string', 'in:he_thong,tai_quay'],
        ];
    }

    public function messages(): array
    {
        return [
            'tai_khoan.required' => 'Vui lòng nhập email hoặc số điện thoại.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
            'password.min' => 'Mật khẩu phải có ít nhất 6 ký tự.',
        ];
    }
}
