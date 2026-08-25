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
        Schema::create('lab_markers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('unit');
            $table->decimal('norm_min', 8, 2)->nullable();
            $table->decimal('norm_max', 8, 2)->nullable();
            $table->boolean('is_predefined')->default(false);
            $table->timestamps();

            $table->unique('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lab_markers');
    }
};
