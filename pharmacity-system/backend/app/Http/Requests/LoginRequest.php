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

        if ($this->filled('tai_khoan') && ! $this->filled('ten_dang_nhap') && ! $this->filled('email') && ! $this->filled('so_dien_thoai')) {
            $taiKhoan = (string) $this->input('tai_khoan');

            if (filter_var($taiKhoan, FILTER_VALIDATE_EMAIL)) {
                $this->merge(['email' => $taiKhoan]);
            } elseif (preg_match('/^\d{10}$/', $taiKhoan) === 1) {
                $this->merge(['so_dien_thoai' => $taiKhoan]);
            } else {
                $this->merge(['ten_dang_nhap' => $taiKhoan]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'tai_khoan' => ['nullable', 'string', 'max:100'],
            'ten_dang_nhap' => ['nullable', 'required_without_all:email,so_dien_thoai', 'string', 'min:3', 'max:50'],
            'email' => ['nullable', 'required_without_all:ten_dang_nhap,so_dien_thoai', 'email', 'max:100'],
            'so_dien_thoai' => ['nullable', 'required_without_all:ten_dang_nhap,email', 'string', 'size:10'],
            'password' => ['required', 'string', 'min:6'],
        ];
    }
}
