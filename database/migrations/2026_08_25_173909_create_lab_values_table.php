<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lab_result_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lab_marker_id')->constrained()->cascadeOnDelete();
            $table->decimal('value', 8, 2);
            $table->timestamps();

            $table->unique(['lab_result_id', 'lab_marker_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_values');
    }
};
