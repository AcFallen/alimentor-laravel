<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meal_plans', function (Blueprint $table) {
            $table->decimal('protein_percentage', 5, 2)->nullable()->after('dinner_percentage');
            $table->decimal('fat_percentage', 5, 2)->nullable()->after('protein_percentage');
            $table->decimal('carbohydrate_percentage', 5, 2)->nullable()->after('fat_percentage');
        });
    }

    public function down(): void
    {
        Schema::table('meal_plans', function (Blueprint $table) {
            $table->dropColumn(['protein_percentage', 'fat_percentage', 'carbohydrate_percentage']);
        });
    }
};
