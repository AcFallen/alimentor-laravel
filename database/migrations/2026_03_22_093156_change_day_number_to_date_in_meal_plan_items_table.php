<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meal_plan_items', function (Blueprint $table) {
            $table->dropIndex(['meal_plan_id', 'day_number', 'meal_type']);
        });

        Schema::table('meal_plan_items', function (Blueprint $table) {
            $table->dropColumn('day_number');
            $table->date('date')->after('meal_plan_id');
        });

        Schema::table('meal_plan_items', function (Blueprint $table) {
            $table->index(['meal_plan_id', 'date', 'meal_type']);
        });
    }

    public function down(): void
    {
        Schema::table('meal_plan_items', function (Blueprint $table) {
            $table->dropIndex(['meal_plan_id', 'date', 'meal_type']);
            $table->dropColumn('date');
            $table->unsignedSmallInteger('day_number')->after('meal_plan_id');
        });

        Schema::table('meal_plan_items', function (Blueprint $table) {
            $table->index(['meal_plan_id', 'day_number', 'meal_type']);
        });
    }
};
