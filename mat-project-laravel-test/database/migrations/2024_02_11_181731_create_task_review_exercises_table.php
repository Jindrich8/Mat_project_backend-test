<?php

use App\Utils\DBUtils;
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
        Schema::create('task_review_exercises', function (Blueprint $table) {
            $table->id()->generatedAs()->always();
            $table->foreignId('task_review_id');
            $table->fixedFloat4('score');
            $table->fixedFloat4('max_points');
            $table->json('data');
            $table->autoTimestamps();
        });
        DBUtils::addPercentDecimalConstraint('task_review_exercises','score');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_review_exercises');
    }
};
