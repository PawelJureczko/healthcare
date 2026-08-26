<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('sport_subtype')->nullable();
            $table->date('date');
            $table->string('status')->default('completed');
            $table->text('comment')->nullable();
            $table->unsignedTinyInteger('wellbeing_rating')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'type', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workouts');
    }
};
