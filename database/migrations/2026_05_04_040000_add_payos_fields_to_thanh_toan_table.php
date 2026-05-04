<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('thanh_toan', function (Blueprint $table) {
            $table->string('trang_thai', 30)->default('paid')->after('ma_giao_dich');
            $table->unsignedBigInteger('payos_order_code')->nullable()->unique()->after('trang_thai');
            $table->string('payos_payment_link_id', 100)->nullable()->unique()->after('payos_order_code');
            $table->text('payos_checkout_url')->nullable()->after('payos_payment_link_id');
            $table->text('payos_qr_code')->nullable()->after('payos_checkout_url');
            $table->json('payos_payload')->nullable()->after('payos_qr_code');
            $table->dateTime('payos_paid_at')->nullable()->after('payos_payload');
        });
    }

    public function down(): void
    {
        Schema::table('thanh_toan', function (Blueprint $table) {
            $table->dropUnique(['payos_order_code']);
            $table->dropUnique(['payos_payment_link_id']);
            $table->dropColumn([
                'trang_thai',
                'payos_order_code',
                'payos_payment_link_id',
                'payos_checkout_url',
                'payos_qr_code',
                'payos_payload',
                'payos_paid_at',
            ]);
        });
    }
};
