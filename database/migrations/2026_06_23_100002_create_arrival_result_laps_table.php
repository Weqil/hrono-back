<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('arrival_result_laps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('arrival_result_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('lap_number');
            $table->unsignedBigInteger('lap_time_ms');
            $table->unsignedBigInteger('timestamp_ms');
            $table->unsignedInteger('position_on_lap');
            $table->boolean('is_manual')->default(false);
            $table->timestamps();

            $table->unique(['arrival_result_id', 'lap_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('arrival_result_laps');
    }
};
