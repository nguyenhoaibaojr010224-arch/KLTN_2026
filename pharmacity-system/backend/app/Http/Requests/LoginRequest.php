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

        if ($this->filled('tai_khoan') && ! $this->filled('so_dien_thoai')) {
            $this->merge([
                'so_dien_thoai' => $this->input('tai_khoan'),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'tai_khoan' => ['nullable', 'string', 'max:20'],
            'so_dien_thoai' => ['required_without:tai_khoan', 'string', 'size:10'],
            'password' => ['required', 'string', 'min:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'so_dien_thoai.required_without' => 'Vui lòng nhập số điện thoại.',
            'so_dien_thoai.size' => 'Số điện thoại phải gồm đúng 10 chữ số.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
            'password.min' => 'Mật khẩu phải có ít nhất 6 ký tự.',
        ];
    }
}
