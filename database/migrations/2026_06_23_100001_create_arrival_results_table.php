<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('arrival_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('arrival_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('server_race_id');
            $table->unsignedInteger('place');
            $table->unsignedInteger('total_laps');
            $table->unsignedBigInteger('total_time_ms');
            $table->unsignedBigInteger('best_lap_time_ms');
            $table->unsignedBigInteger('user_id');
            $table->string('name');
            $table->string('surname')->default('');
            $table->string('patronymic')->default('');
            $table->unsignedInteger('start_number');
            $table->string('tag_id')->default('');
            $table->string('grade')->default('');
            $table->string('command')->default('');
            $table->timestamps();

            $table->index(['arrival_id', 'place']);
            $table->index('server_race_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('arrival_results');
    }
};
