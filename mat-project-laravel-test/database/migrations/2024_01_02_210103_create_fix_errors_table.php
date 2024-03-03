<?php

use App\Types\DBCascadeType;
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
        Schema::create('fix_errors', function (Blueprint $table) {
            $table->pkFKColumn(
                'exerciseable_id',
                references: 'id',
                onTable: 'exercises',
                cascadeType:DBCascadeType::DELETE
            );

            $table->string('correct_text', 2040);
            $table->string('wrong_text', 2040);
            $table->unsignedInteger("distance");
            $table->autoTimestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fix_errors');
    }
};
