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
        Schema::create('running_activities', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('activity_id')->unique();
            $table->string('name');
            $table->dateTime('start_time_local');
            $table->dateTime('start_time_gmt');
            $table->decimal('distance_km', 8, 2);
            $table->decimal('duration_minutes', 8, 2);
            $table->decimal('average_pace_min_per_km', 5, 2);
            $table->integer('average_heart_rate_bpm');
            $table->integer('max_heart_rate_bpm');
            $table->integer('average_running_cadence_steps_per_min');
            $table->integer('max_running_cadence_steps_per_min');
            $table->string('location');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('running_activities');
    }
};
