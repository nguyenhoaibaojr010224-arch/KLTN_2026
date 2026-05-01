<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('thong_bao_khach_hang_da_docs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('id_thong_bao');
            $table->unsignedBigInteger('id_khach_hang');
            $table->dateTime('da_doc_luc');
            $table->timestamps();

            $table->foreign('id_thong_bao')
                ->references('id_thong_bao')
                ->on('thong_bao_khach_hangs')
                ->cascadeOnDelete();

            $table->foreign('id_khach_hang')
                ->references('id_khach_hang')
                ->on('khach_hangs')
                ->cascadeOnDelete();

            $table->unique(['id_thong_bao', 'id_khach_hang'], 'uniq_thong_bao_khach_hang_da_doc');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('thong_bao_khach_hang_da_docs');
    }
};
