<?php

namespace App\Http\Requests;

use App\Models\MaGiamGia;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreMaGiamGiaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ma_giam_gia' => ['required', 'string', 'min:4', 'max:30', 'regex:/^[A-Z0-9-]+$/', 'unique:ma_giam_gias,ma_giam_gia'],
            'ten_ma' => ['required', 'string', 'min:3', 'max:150'],
            'mo_ta' => ['nullable', 'string', 'max:1000'],
            'loai_ap_dung' => ['required', Rule::in(['phan_tram', 'so_tien', 'gia_co_dinh'])],
            'gia_tri' => ['required', 'numeric', 'min:1'],
            'gia_tri_don_toi_thieu' => ['nullable', 'numeric', 'min:0'],
            'gioi_han_moi_khach' => ['nullable', 'integer', 'min:1'],
            'ngay_bat_dau' => ['required', 'date'],
            'ngay_ket_thuc' => ['nullable', 'date', 'after_or_equal:ngay_bat_dau'],
            'trang_thai' => ['required', Rule::in(['draft', 'active', 'inactive'])],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            [$type, $value] = $this->resolveEffectiveDiscountData();

            if ($type === 'phan_tram' && $value !== null && $value > 100) {
                $validator->errors()->add(
                    'gia_tri',
                    'Giá trị giảm theo phần trăm không được vượt quá 100%.'
                );
            }
        });
    }

    private function resolveEffectiveDiscountData(): array
    {
        $current = null;
        $id = $this->route('id');

        if ($id) {
            $current = MaGiamGia::query()->find($id);
        }

        return [
            $this->input('loai_ap_dung', $current?->loai_ap_dung),
            $this->filled('gia_tri') ? (float) $this->input('gia_tri') : ($current?->gia_tri !== null ? (float) $current->gia_tri : null),
        ];
    }
}
