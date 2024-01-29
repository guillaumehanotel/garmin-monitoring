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
            $table->time('real_time'); // Temps réel
            $table->integer('bib_number'); // Numéro de dossard
            $table->string('category'); // SEM (1 / 2207)
            $table->float('speed_kmh', 8, 2);
            $table->time('official_time'); // Temps officiel


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
