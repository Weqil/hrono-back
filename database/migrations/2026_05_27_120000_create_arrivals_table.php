<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('arrivals', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('finished')->default(false);
            $table->unsignedInteger('round_min_time')->default(0);
            $table->string('time');
            $table->json('arrival_grades')->default(json_encode([]));
            $table->unsignedBigInteger('moto_race_id');
            $table->timestamps();
            $table->index(['moto_race_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('arrivals');
    }
};
