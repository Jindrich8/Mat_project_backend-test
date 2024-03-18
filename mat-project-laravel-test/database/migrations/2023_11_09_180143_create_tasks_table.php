<?php

use App\ModelConstants\TaskInfoConstants;
use App\Utils\DBUtils;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLE = 'tasks';
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(self::TABLE, function (Blueprint $table) {
            $table->id()->generatedAs()->always();
            $table->foreignId('task_info_id')->constrained();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name',250)->unique();
            $table->unsignedInteger('version')->default(0);
            $table->text('source');
            $table->boolean('is_public')->default(false);
            $table->autoTimestamps();
        });
        DBUtils::ensureAutoUpdateUpdatedAtTimestamp(self::TABLE);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(self::TABLE);
    }
};
