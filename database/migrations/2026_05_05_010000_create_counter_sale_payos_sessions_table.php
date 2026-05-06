<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('counter_sale_payos_sessions', function (Blueprint $table) {
            $table->id();
            $table->uuid('session_key')->unique();
            $table->unsignedBigInteger('id_nhan_vien');
            $table->unsignedBigInteger('id_khach_hang')->nullable();
            $table->unsignedBigInteger('id_hoa_don')->nullable();
            $table->boolean('is_registered_customer')->default(false);
            $table->string('status', 30)->default('pending');
            $table->json('items_payload');
            $table->json('detail_rows');
            $table->json('summary_items');
            $table->decimal('tong_tien', 14, 2)->default(0);
            $table->decimal('giam_gia_diem', 14, 2)->default(0);
            $table->decimal('thue_vat', 14, 2)->default(0);
            $table->decimal('tien_thanh_toan', 14, 2)->default(0);
            $table->unsignedInteger('diem_da_su_dung')->default(0);
            $table->unsignedInteger('diem_da_cong')->default(0);
            $table->text('ghi_chu')->nullable();
            $table->unsignedBigInteger('payos_order_code')->nullable()->unique();
            $table->string('payos_payment_link_id', 100)->nullable()->unique();
            $table->text('payos_checkout_url')->nullable();
            $table->text('payos_qr_code')->nullable();
            $table->json('payos_payload')->nullable();
            $table->dateTime('payos_paid_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->foreign('id_nhan_vien')
                ->references('id_nhan_vien')
                ->on('nhan_viens')
                ->restrictOnDelete();

            $table->foreign('id_khach_hang')
                ->references('id_khach_hang')
                ->on('khach_hangs')
                ->restrictOnDelete();

            $table->foreign('id_hoa_don')
                ->references('id_hoa_don')
                ->on('hoa_dons')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('counter_sale_payos_sessions');
    }
};
