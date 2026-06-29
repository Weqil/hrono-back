<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('arrivals', function (Blueprint $table) {
            $table->unsignedBigInteger('local_arrival_id')->nullable()->after('moto_race_id');
            $table->timestamp('finished_at')->nullable()->after('finished');
        });
    }

    public function down(): void
    {
        Schema::table('arrivals', function (Blueprint $table) {
            $table->dropColumn(['local_arrival_id', 'finished_at']);
        });
    }
};
