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
        Schema::create('fill_in_blanks', function (Blueprint $table) {
            $table->pkFKColumn(
                'exerciseable_id',
                references: 'id',
                onTable: 'exercises'
            );

            $table->json("content");
            $table->autoTimestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fill_in_blanks');
    }
};
