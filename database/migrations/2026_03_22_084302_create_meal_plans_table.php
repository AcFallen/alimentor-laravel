<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meal_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('food_table_id')->constrained();
            $table->string('sex');
            $table->integer('age');
            $table->decimal('weight', 6, 2);
            $table->decimal('height_cm', 6, 2);
            $table->string('formula')->nullable();
            $table->decimal('geb', 10, 2)->nullable();
            $table->decimal('get', 10, 2)->nullable();
            $table->string('activity_factor')->nullable();
            $table->decimal('breakfast_percentage', 5, 2)->nullable();
            $table->decimal('morning_snack_percentage', 5, 2)->nullable();
            $table->decimal('lunch_percentage', 5, 2)->nullable();
            $table->decimal('afternoon_snack_percentage', 5, 2)->nullable();
            $table->decimal('dinner_percentage', 5, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meal_plans');
    }
};
