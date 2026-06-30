<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('arrivals', function (Blueprint $table) {
            $table->foreignId('arrival_type_id')
                ->nullable()
                ->after('moto_race_id')
                ->constrained('arrival_types')
                ->nullOnDelete();

            $table->index(['arrival_type_id']);
        });
    }

    public function down(): void
    {
        Schema::table('arrivals', function (Blueprint $table) {
            $table->dropConstrainedForeignId('arrival_type_id');
        });
    }
};
