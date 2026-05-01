<?php

namespace App\Http\Middleware;

use App\Models\NhanVien;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class EnsureNhanVienRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user instanceof NhanVien) {
            return response()->json([
                'message' => 'Tai khoan nay khong duoc phep truy cap.',
            ], 403);
        }

        $user->loadMissing('vaiTro');
        $currentRole = $this->normalizeRole($user->vaiTro?->ten_vai_tro);
        $allowedRoles = array_map(fn(string $role): string => $this->normalizeRole($role), $roles);

        if (! in_array($currentRole, $allowedRoles, true)) {
            return response()->json([
                'message' => 'Ban khong co quyen truy cap tai nguyen nay.',
            ], 403);
        }

        return $next($request);
    }

    private function normalizeRole(?string $role): string
    {
        $normalized = Str::of((string) $role)
            ->lower()
            ->replace([' ', '-'], '_')
            ->toString();

        if ($normalized === 'admin') {
            return 'admin';
        }

        if (in_array($normalized, ['staff', 'nhan_vien', 'nhanvien'], true)) {
            return 'staff';
        }

        return $normalized;
    }
}
