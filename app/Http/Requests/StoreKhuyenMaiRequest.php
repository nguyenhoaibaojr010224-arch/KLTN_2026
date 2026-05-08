<?php

namespace App\Http\Requests;

use App\Models\KhuyenMai;
use App\Models\Thuoc;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreKhuyenMaiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ma_thuoc' => ['required', 'exists:thuocs,ma_thuoc'],
            'ma_khuyen_mai' => ['nullable', 'string', 'min:4', 'max:30', 'regex:/^[A-Z0-9-]+$/', 'unique:khuyen_mais,ma_khuyen_mai'],
            'ten_khuyen_mai' => ['required', 'string', 'min:3', 'max:150'],
            'mo_ta' => ['nullable', 'string', 'max:1000'],
            'loai_ap_dung' => ['required', Rule::in(['phan_tram', 'so_tien', 'gia_co_dinh'])],
            'gia_tri' => ['required', 'numeric', 'min:1'],
            'nhan_hien_thi' => ['nullable', 'string', 'max:100'],
            'ngay_bat_dau' => ['required', 'date'],
            'ngay_ket_thuc' => ['nullable', 'date', 'after_or_equal:ngay_bat_dau'],
            'trang_thai' => ['required', Rule::in(['draft', 'active', 'inactive'])],
        ];
    }

    public function messages(): array
    {
        return [
            'ma_khuyen_mai.unique' => 'Ma khuyen mai da ton tai.',
            'ma_khuyen_mai.regex' => 'Ma khuyen mai chi duoc chua chu in hoa, so va dau gach ngang.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            [$type, $value, $maThuoc] = $this->resolveEffectiveDiscountData();

            if ($type === 'phan_tram' && $value !== null && $value > 100) {
                $validator->errors()->add(
                    'gia_tri',
                    'Giá trị giảm theo phần trăm không được vượt quá 100%.'
                );
            }

            if ($type === 'so_tien' && $value !== null && $maThuoc !== null) {
                $giaBan = Thuoc::query()->whereKey($maThuoc)->value('gia_ban');

                if ($giaBan !== null && $value > (float) $giaBan) {
                    $validator->errors()->add(
                        'gia_tri',
                        'Số tiền giảm không được vượt quá giá bán của thuốc.'
                    );
                }
            }
        });
    }

    private function resolveEffectiveDiscountData(): array
    {
        $current = null;
        $id = $this->route('id');

        if ($id) {
            $current = KhuyenMai::query()->find($id);
        }

        return [
            $this->input('loai_ap_dung', $current?->loai_ap_dung),
            $this->filled('gia_tri') ? (float) $this->input('gia_tri') : ($current?->gia_tri !== null ? (float) $current->gia_tri : null),
            $this->input('ma_thuoc', $current?->ma_thuoc),
        ];
    }
}
