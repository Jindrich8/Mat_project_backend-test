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
        Schema::create('task_review_templates', function (Blueprint $table) {
            $table->id()->generatedAs()->always();
            $table->foreignId('task_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('task_info_id')->constrained();
            $table->foreignId('author_id')->references('id')->on('users')->nullable()->constrained()->nullOnDelete();
            $table->string('author_name');
            $table->autoTimestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_review_templates');
    }
};
