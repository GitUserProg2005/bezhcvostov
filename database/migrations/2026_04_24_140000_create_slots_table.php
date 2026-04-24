<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('slots', function (Blueprint $table) {
            $table->id();
            $table->decimal('x', 5, 4);
            $table->decimal('y', 5, 4);
            $table->decimal('scale', 5, 4);
            $table->string('type');
            $table->timestamps();
        });

        DB::statement('ALTER TABLE slots ADD CONSTRAINT slots_x_range CHECK (x >= 0 AND x <= 1)');
        DB::statement('ALTER TABLE slots ADD CONSTRAINT slots_y_range CHECK (y >= 0 AND y <= 1)');
    }

    public function down(): void
    {
        Schema::dropIfExists('slots');
    }
};
