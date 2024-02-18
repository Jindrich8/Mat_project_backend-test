<?php

use App\TableSpecificData\TaskDifficulty;
use App\TableSpecificData\TaskDisplay;
use App\TableSpecificData\TaskClass;
use App\Utils\DBUtils;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private static string $table = 'task_infos';
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(self::$table, function (Blueprint $table) {
            $table->id()->generatedAs()->always();
            $table->string('name',250);
            $table->unsignedTinyInteger('orientation');
            $table->string('description',2040);
            $table->unsignedTinyInteger('difficulty');
            $table->unsignedTinyInteger('min_class');
            $table->unsignedTinyInteger('max_class');
            $table->autoTimestamps();
        });
        DBUtils::addIntEnumConstraint(self::$table,'orientation',TaskDisplay::class);
        DBUtils::addIntEnumConstraint(self::$table,'difficulty',TaskDifficulty::class);
        
        DBUtils::addCheckConstraint(
            table:self::$table,
            condition:"min_class >= 0 AND max_class < ".count(TaskClass::cases())." AND max_class >= min_class"
        );
    }
    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(self::$table);
    }
};
