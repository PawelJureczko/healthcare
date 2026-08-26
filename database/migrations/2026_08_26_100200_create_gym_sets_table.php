<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gym_sets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gym_exercise_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('set_number');
            $table->decimal('planned_weight_kg', 5, 2)->nullable();
            $table->unsignedSmallInteger('planned_reps')->nullable();
            $table->decimal('weight_kg', 5, 2)->nullable();
            $table->unsignedSmallInteger('reps')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gym_sets');
    }
};
