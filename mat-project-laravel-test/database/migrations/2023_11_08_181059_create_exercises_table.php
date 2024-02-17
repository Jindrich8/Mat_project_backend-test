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
        Schema::create('exercises', function (Blueprint $table) {
            $table->id()->generatedAs()->always();
            $table->unsignedTinyInteger('order');
            $table->unsignedInteger('weight');
            $table->string('exerciseable_type');
            $table->text('instructions');
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->autoTimestamps();
            $table->index(['exerciseable_type','id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exercises');
    }
};
