<?php

use App\TableSpecificData\TaskDisplay;
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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id()->generatedAs()->always();
            $table->string('name',250);
            $table->enum('orientation',array_map(fn(TaskDisplay $case)=>$case->value,TaskDisplay::cases()));
            $table->string('description',2040);
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->autoTimestamps();
        });
    }
    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
