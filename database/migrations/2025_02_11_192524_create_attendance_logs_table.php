<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('attendance_logs', function (Blueprint $table) {
            $table->id();
            $table->string('user_id');
            $table->string('name');
            $table->dateTime('timestamp');
            $table->enum('type', ['check_in', 'check_out']);
            $table->string('device_id')->nullable();
            $table->string('verify_mode')->nullable();
            $table->boolean('is_processed')->default(false);
            $table->timestamps();
            
            // Índices
            $table->index(['user_id', 'timestamp']);
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_logs');
    }
};
