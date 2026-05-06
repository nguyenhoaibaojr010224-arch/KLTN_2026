<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CounterSalePayosSession extends Model
{
    protected $fillable = [
        'session_key',
        'id_nhan_vien',
        'id_khach_hang',
        'id_hoa_don',
        'is_registered_customer',
        'status',
        'items_payload',
        'detail_rows',
        'summary_items',
        'tong_tien',
        'giam_gia_diem',
        'thue_vat',
        'tien_thanh_toan',
        'diem_da_su_dung',
        'diem_da_cong',
        'ghi_chu',
        'payos_order_code',
        'payos_payment_link_id',
        'payos_checkout_url',
        'payos_qr_code',
        'payos_payload',
        'payos_paid_at',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'is_registered_customer' => 'boolean',
            'items_payload' => 'array',
            'detail_rows' => 'array',
            'summary_items' => 'array',
            'tong_tien' => 'decimal:2',
            'giam_gia_diem' => 'decimal:2',
            'thue_vat' => 'decimal:2',
            'tien_thanh_toan' => 'decimal:2',
            'diem_da_su_dung' => 'integer',
            'diem_da_cong' => 'integer',
            'payos_payload' => 'array',
            'payos_paid_at' => 'datetime',
        ];
    }

    public function nhanVien(): BelongsTo
    {
        return $this->belongsTo(NhanVien::class, 'id_nhan_vien', 'id_nhan_vien');
    }

    public function khachHang(): BelongsTo
    {
        return $this->belongsTo(KhachHang::class, 'id_khach_hang', 'id_khach_hang');
    }

    public function hoaDon(): BelongsTo
    {
        return $this->belongsTo(HoaDon::class, 'id_hoa_don', 'id_hoa_don');
    }
}
