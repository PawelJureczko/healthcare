<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workout_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('distance_m');
            $table->unsignedInteger('duration_s');
            $table->unsignedInteger('avg_pace_s_per_km')->nullable();
            $table->unsignedSmallInteger('avg_heart_rate')->nullable();
            $table->unsignedSmallInteger('max_heart_rate')->nullable();
            $table->unsignedInteger('kcal')->nullable();
            $table->unsignedBigInteger('strava_activity_id')->nullable()->unique();
            $table->json('strava_raw')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('runs');
    }
};
