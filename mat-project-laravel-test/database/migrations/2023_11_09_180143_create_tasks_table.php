<?php

use App\ModelConstants\TaskInfoConstants;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private static string $table = 'tasks';
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(self::$table, function (Blueprint $table) {
            $table->id()->generatedAs()->always();
            $table->foreignId('task_info_id')->constrained();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version')->default(0);
            $table->text('source');
            $table->boolean('is_public')->default(false);
            $table->autoTimestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(self::$table);
    }
};
