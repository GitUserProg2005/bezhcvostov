<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->foreignId('admin_id')->constrained('users')->cascadeOnDelete();
            $table->string('type')->default('public');
            $table->timestamps();

            $table->index('admin_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
