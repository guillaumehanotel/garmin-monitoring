<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('race_runners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('race_id')->constrained()->onDelete('cascade');
            $table->foreignId('runner_id')->constrained()->onDelete('cascade');
            $table->integer('position');
            $table->integer('bib_number');
            $table->string('category');
            $table->float('speed_kmh', 8, 2);
            $table->time('real_time');
            $table->time('official_time');
            $table->time('time_km5')->nullable();
            $table->time('time_km10')->nullable();
            $table->time('time_km15')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('race_runners');
    }
};
