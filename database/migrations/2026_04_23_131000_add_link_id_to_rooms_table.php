<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->uuid('link_id')->nullable()->after('creator_id');
        });

        DB::table('rooms')
            ->select('id')
            ->whereNull('link_id')
            ->orderBy('id')
            ->chunkById(100, function ($rooms) {
                foreach ($rooms as $room) {
                    DB::table('rooms')
                        ->where('id', $room->id)
                        ->update(['link_id' => (string) Str::uuid()]);
                }
            });

        Schema::table('rooms', function (Blueprint $table) {
            $table->unique('link_id');
        });
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropUnique(['link_id']);
            $table->dropColumn('link_id');
        });
    }
};
