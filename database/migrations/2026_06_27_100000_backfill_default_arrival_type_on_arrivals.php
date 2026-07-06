<?php

use App\Application\Arrival\Enums\ArrivalKind;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('arrival_types') || ! Schema::hasColumn('arrivals', 'arrival_type_id')) {
            return;
        }

        $regular = ArrivalKind::Regular;

        $typeId = DB::table('arrival_types')
            ->where('slug', $regular->value)
            ->value('id');

        if ($typeId === null) {
            $typeId = DB::table('arrival_types')->insertGetId([
                'name' => $regular->label(),
                'slug' => $regular->value,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('arrivals')
            ->whereNull('arrival_type_id')
            ->update(['arrival_type_id' => $typeId]);
    }

    public function down(): void
    {
        // Irreversible: previous null values cannot be restored reliably.
    }
};
