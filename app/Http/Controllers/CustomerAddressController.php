<?php

namespace App\Http\Controllers;

use App\Models\DiaChiKhachHang;
use App\Models\KhachHang;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerAddressController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $khachHang = $this->resolveCustomer($request);

        return response()->json([
            'data' => DiaChiKhachHang::query()
                ->where('id_khach_hang', $khachHang->id_khach_hang)
                ->orderByDesc('mac_dinh')
                ->orderByDesc('updated_at')
                ->get()
                ->map(fn (DiaChiKhachHang $address) => $this->transformAddress($address)),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $khachHang = $this->resolveCustomer($request);
        $validated = $this->validatePayload($request, $khachHang);

        $address = DB::transaction(function () use ($validated, $khachHang) {
            $shouldSetDefault = (bool) ($validated['mac_dinh'] ?? false)
                || !DiaChiKhachHang::query()->where('id_khach_hang', $khachHang->id_khach_hang)->exists();

            if ($shouldSetDefault) {
                $this->clearDefaultAddresses($khachHang->id_khach_hang);
            }

            return DiaChiKhachHang::query()->create([
                ...$validated,
                'id_khach_hang' => $khachHang->id_khach_hang,
                'mac_dinh' => $shouldSetDefault,
            ]);
        });

        return response()->json([
            'message' => 'Đã thêm địa chỉ nhận hàng.',
            'data' => $this->transformAddress($address->fresh()),
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $khachHang = $this->resolveCustomer($request);
        $address = $this->findCustomerAddress($khachHang, $id);
        $validated = $this->validatePayload($request, $khachHang, $address->id_dia_chi);

        $address = DB::transaction(function () use ($address, $validated, $khachHang) {
            $shouldSetDefault = (bool) ($validated['mac_dinh'] ?? false);

            if ($shouldSetDefault) {
                $this->clearDefaultAddresses($khachHang->id_khach_hang, $address->id_dia_chi);
            }

            $address->update([
                ...$validated,
                'mac_dinh' => $shouldSetDefault ? true : $address->mac_dinh,
            ]);

            return $address;
        });

        return response()->json([
            'message' => 'Đã cập nhật địa chỉ nhận hàng.',
            'data' => $this->transformAddress($address->fresh()),
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $khachHang = $this->resolveCustomer($request);
        $address = $this->findCustomerAddress($khachHang, $id);

        DB::transaction(function () use ($address, $khachHang) {
            $wasDefault = $address->mac_dinh;
            $address->delete();

            if (! $wasDefault) {
                return;
            }

            $nextDefault = DiaChiKhachHang::query()
                ->where('id_khach_hang', $khachHang->id_khach_hang)
                ->orderByDesc('updated_at')
                ->first();

            if ($nextDefault) {
                $nextDefault->update(['mac_dinh' => true]);
            }
        });

        return response()->json([
            'message' => 'Đã xóa địa chỉ nhận hàng.',
        ]);
    }

    private function resolveCustomer(Request $request): KhachHang
    {
        $user = $request->user();

        abort_unless($user instanceof KhachHang, 403, 'Chỉ khách hàng mới có thể quản lý địa chỉ nhận hàng.');

        return $user;
    }

    private function findCustomerAddress(KhachHang $khachHang, int $id): DiaChiKhachHang
    {
        return DiaChiKhachHang::query()
            ->where('id_khach_hang', $khachHang->id_khach_hang)
            ->findOrFail($id);
    }

    private function validatePayload(Request $request, KhachHang $khachHang, ?int $addressId = null): array
    {
        return $request->validate([
            'ho_ten' => ['required', 'string', 'min:2', 'max:100'],
            'so_dien_thoai' => ['required', 'string', 'regex:/^\d{10}$/'],
            'tinh_thanh' => ['nullable', 'string', 'max:100'],
            'quan_huyen' => ['nullable', 'string', 'max:100'],
            'phuong_xa' => ['nullable', 'string', 'max:100'],
            'so_nha' => ['required', 'string', 'min:2', 'max:255'],
            'loai_dia_chi' => ['required', 'string', 'max:50'],
            'mac_dinh' => ['nullable', 'boolean'],
        ], [
            'ho_ten.required' => 'Vui lòng nhập họ tên người nhận.',
            'so_dien_thoai.required' => 'Vui lòng nhập số điện thoại người nhận.',
            'so_dien_thoai.regex' => 'Số điện thoại người nhận phải gồm đúng 10 chữ số.',
            'so_nha.required' => 'Vui lòng nhập số nhà hoặc địa chỉ chi tiết.',
            'loai_dia_chi.required' => 'Vui lòng chọn loại địa chỉ.',
        ]);
    }

    private function clearDefaultAddresses(int $customerId, ?int $exceptId = null): void
    {
        DiaChiKhachHang::query()
            ->where('id_khach_hang', $customerId)
            ->when($exceptId, fn ($query) => $query->where('id_dia_chi', '!=', $exceptId))
            ->update(['mac_dinh' => false]);
    }

    private function transformAddress(DiaChiKhachHang $address): array
    {
        return [
            'id' => $address->id_dia_chi,
            'ho_ten' => $address->ho_ten,
            'so_dien_thoai' => $address->so_dien_thoai,
            'tinh_thanh' => $address->tinh_thanh,
            'quan_huyen' => $address->quan_huyen,
            'phuong_xa' => $address->phuong_xa,
            'so_nha' => $address->so_nha,
            'loai_dia_chi' => $address->loai_dia_chi,
            'mac_dinh' => $address->mac_dinh,
            'created_at' => optional($address->created_at)?->toIso8601String(),
            'updated_at' => optional($address->updated_at)?->toIso8601String(),
        ];
    }
}
