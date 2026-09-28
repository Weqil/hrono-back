<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('arrivals', function (Blueprint $table) {
            $table->timestamp('stream_auto_close_at')->nullable()->after('moto_stream_closed_at');
            $table->text('moto_stream_bearer')->nullable()->after('stream_auto_close_at');
            $table->timestamp('last_live_results_at')->nullable()->after('moto_stream_bearer');

            $table->index(['stream_auto_close_at']);
        });
    }

    public function down(): void
    {
        Schema::table('arrivals', function (Blueprint $table) {
            $table->dropIndex(['stream_auto_close_at']);
            $table->dropColumn([
                'stream_auto_close_at',
                'moto_stream_bearer',
                'last_live_results_at',
            ]);
        });
    }
};
