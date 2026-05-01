<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('password_resets', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->string('token')->unique();
            $table->dateTime('expired_at');
            $table->boolean('used')->default(false);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('ngay_cap_nhat')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_resets');
    }
};
