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
        Schema::create('task_reviews', function (Blueprint $table) {
            $table->id()->generatedAs()->always();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('task_review_template_id')->constrained();
            $table->fixedFloat4('score');
            $table->fixedFloat4('max_points');
            $table->json('exercises');
            $table->timestamp('evaluated_at');
            $table->autoTimestamps();
            $table->unique(['user_id','task_review_template_id']);
            $table->index(['score']);
        });

        DBUtils::addPercentDecimalConstraint('task_reviews','score');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_reviews');
    }
};
