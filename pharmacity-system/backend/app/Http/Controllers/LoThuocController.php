<?php

namespace App\Http\Controllers;

use App\Models\LoThuoc;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class LoThuocController extends Controller
{
    public function index()
    {
        return response()->json(LoThuoc::with('thuoc')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_thuoc' => 'required|string|exists:thuocs,ma_thuoc',
            'so_lo' => 'required|string|unique:lo_thuocs,so_lo',
            'ngay_san_xuat' => 'required|date',
            'han_su_dung' => 'required|date|after:ngay_san_xuat',
            'so_luong_nhap' => 'required|integer|min:1',
            'gia_nhap' => 'required|numeric|min:1',
        ]);

        $validated['so_luong_con'] = $validated['so_luong_nhap'];

        $loThuoc = LoThuoc::create($validated);
        return response()->json($loThuoc, 201);
    }

    public function show($id)
    {
        $loThuoc = LoThuoc::with('thuoc')->find($id);

        if (!$loThuoc) {
            return response()->json(['message' => 'Không tìm thấy lô thuốc'], 404);
        }

        return response()->json($loThuoc);
    }

    public function update(Request $request, $id)
    {
        $loThuoc = LoThuoc::find($id);

        if (!$loThuoc) {
            return response()->json(['message' => 'Không tìm thấy lô thuốc'], 404);
        }

        $validated = $request->validate([
            'id_thuoc' => 'sometimes|required|string|exists:thuocs,ma_thuoc',
            'so_lo' => ['sometimes', 'required', 'string', Rule::unique('lo_thuocs')->ignore($loThuoc->id_lo, 'id_lo')],
            'ngay_san_xuat' => 'sometimes|required|date',
            'han_su_dung' => 'sometimes|required|date|after:ngay_san_xuat',
            'so_luong_nhap' => 'sometimes|required|integer|min:1',
            'so_luong_con' => 'sometimes|required|integer|min:0|lte:so_luong_nhap',
            'so_luong_nhap_them' => 'sometimes|required|integer|min:1',
            'gia_nhap' => 'sometimes|required|numeric|min:1',
        ]);

        if (isset($validated['so_luong_nhap_them'])) {
            $loThuoc->so_luong_nhap += $validated['so_luong_nhap_them'];
            $loThuoc->so_luong_con += $validated['so_luong_nhap_them'];
            unset($validated['so_luong_nhap_them'], $validated['so_luong_nhap'], $validated['so_luong_con']);
        }

        $loThuoc->update($validated);
        return response()->json($loThuoc);
    }

    public function destroy($id)
    {
        $loThuoc = LoThuoc::find($id);

        if (!$loThuoc) {
            return response()->json(['message' => 'Không tìm thấy lô thuốc'], 404);
        }

        $loThuoc->delete();
        return response()->json(['message' => 'Xoá thành công']);
    }

    public function search(Request $request)
    {
        $query = $request->get('q');
        
        $loThuocs = LoThuoc::with('thuoc')
            ->whereHas('thuoc', function ($q) use ($query) {
                $q->where('ten_thuoc', 'like', '%' . $query . '%')
                  ->orWhere('ma_thuoc', 'like', '%' . $query . '%');
            })
            ->orWhere('so_lo', 'like', '%' . $query . '%')
            ->get();

        return response()->json($loThuocs);
    }

    public function expiring(Request $request)
    {
        $days = $request->get('days', 30);
        $thresholdDate = Carbon::now()->addDays($days);

        $loThuocs = LoThuoc::with('thuoc')
            ->where('han_su_dung', '<=', $thresholdDate->format('Y-m-d'))
            ->where('han_su_dung', '>=', Carbon::now()->format('Y-m-d'))
            ->get();

        return response()->json($loThuocs);
    }
}
