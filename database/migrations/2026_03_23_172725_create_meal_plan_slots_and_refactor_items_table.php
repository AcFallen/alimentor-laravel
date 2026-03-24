<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meal_plan_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meal_plan_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('meal_type');
            $table->unsignedInteger('diners')->default(1);
            $table->timestamps();

            $table->unique(['meal_plan_id', 'date', 'meal_type']);
            $table->index(['meal_plan_id', 'date']);
        });

        // Migrate existing data: create slots from existing items
        DB::table('meal_plan_items')
            ->select('meal_plan_id', 'date', 'meal_type')
            ->distinct()
            ->orderBy('meal_plan_id')
            ->orderBy('date')
            ->orderBy('meal_type')
            ->each(function ($row) {
                DB::table('meal_plan_slots')->insert([
                    'meal_plan_id' => $row->meal_plan_id,
                    'date' => $row->date,
                    'meal_type' => $row->meal_type,
                    'diners' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });

        // Add new columns to meal_plan_items
        Schema::table('meal_plan_items', function (Blueprint $table) {
            $table->foreignId('meal_plan_slot_id')
                ->nullable()
                ->after('id')
                ->constrained('meal_plan_slots')
                ->cascadeOnDelete();
            $table->unsignedInteger('diners')->default(1)->after('quantity');
        });

        // Populate meal_plan_slot_id from matching slots
        DB::table('meal_plan_items')->update([
            'meal_plan_slot_id' => DB::raw('(
                SELECT s.id FROM meal_plan_slots s
                WHERE s.meal_plan_id = meal_plan_items.meal_plan_id
                AND s.date = meal_plan_items.date
                AND s.meal_type = meal_plan_items.meal_type
            )'),
        ]);

        // Make meal_plan_slot_id non-nullable now that it's populated
        Schema::table('meal_plan_items', function (Blueprint $table) {
            $table->foreignId('meal_plan_slot_id')->nullable(false)->change();
        });

        // Drop old columns and index
        Schema::table('meal_plan_items', function (Blueprint $table) {
            $table->dropIndex(['meal_plan_id', 'date', 'meal_type']);
        });

        Schema::table('meal_plan_items', function (Blueprint $table) {
            $table->dropForeign(['meal_plan_id']);
        });

        Schema::table('meal_plan_items', function (Blueprint $table) {
            $table->dropColumn(['meal_plan_id', 'date', 'meal_type']);
        });

        // Add new index
        Schema::table('meal_plan_items', function (Blueprint $table) {
            $table->index(['meal_plan_slot_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        // Re-add old columns to meal_plan_items
        Schema::table('meal_plan_items', function (Blueprint $table) {
            $table->dropIndex(['meal_plan_slot_id', 'sort_order']);
        });

        Schema::table('meal_plan_items', function (Blueprint $table) {
            $table->foreignId('meal_plan_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->date('date')->nullable()->after('meal_plan_id');
            $table->string('meal_type')->nullable()->after('date');
        });

        // Restore data from slots
        DB::table('meal_plan_items')->update([
            'meal_plan_id' => DB::raw('(SELECT s.meal_plan_id FROM meal_plan_slots s WHERE s.id = meal_plan_items.meal_plan_slot_id)'),
            'date' => DB::raw('(SELECT s.date FROM meal_plan_slots s WHERE s.id = meal_plan_items.meal_plan_slot_id)'),
            'meal_type' => DB::raw('(SELECT s.meal_type FROM meal_plan_slots s WHERE s.id = meal_plan_items.meal_plan_slot_id)'),
        ]);

        Schema::table('meal_plan_items', function (Blueprint $table) {
            $table->foreignId('meal_plan_id')->nullable(false)->change();
            $table->date('date')->nullable(false)->change();
            $table->string('meal_type')->nullable(false)->change();
        });

        Schema::table('meal_plan_items', function (Blueprint $table) {
            $table->dropForeign(['meal_plan_slot_id']);
            $table->dropColumn(['meal_plan_slot_id', 'diners']);
        });

        Schema::table('meal_plan_items', function (Blueprint $table) {
            $table->index(['meal_plan_id', 'date', 'meal_type']);
        });

        Schema::dropIfExists('meal_plan_slots');
    }
};
