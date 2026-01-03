<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add new columns
        Schema::table('parts', function (Blueprint $table) {
            if (!Schema::hasColumn('parts', 'unit_id')) {
                $table->foreignId('unit_id')->nullable()->after('name_en')->constrained('inventory_units')->nullOnDelete();
            }
            if (!Schema::hasColumn('parts', 'category_id')) {
                $table->foreignId('category_id')->nullable()->after('unit')->constrained('inventory_categories')->nullOnDelete();
            }
        });

        // 2. Migrate data
        // Map unit (string) to unit_id
        $parts = DB::table('parts')->select('id', 'unit', 'category', 'tenant_id')->get();
        foreach ($parts as $part) {
            $updates = [];

            // Try to match unit by ar or en name
            if ($part->unit) {
                $unit = DB::table('inventory_units')
                    ->where('tenant_id', $part->tenant_id)
                    ->where(function($q) use ($part) {
                        $q->where('name_ar', $part->unit)
                          ->orWhere('name_en', $part->unit);
                    })->first();
                if ($unit) {
                    $updates['unit_id'] = $unit->id;
                }
            }

            // Try to match category by ar or en name
            if ($part->category) {
                $category = DB::table('inventory_categories')
                    ->where('tenant_id', $part->tenant_id)
                    ->where(function($q) use ($part) {
                        $q->where('name_ar', $part->category)
                          ->orWhere('name_en', $part->category);
                    })->first();
                if ($category) {
                    $updates['category_id'] = $category->id;
                }
            }

            if (!empty($updates)) {
                DB::table('parts')->where('id', $part->id)->update($updates);
            }
        }

        // 3. Drop old columns
        Schema::table('parts', function (Blueprint $table) {
            $table->dropColumn(['unit', 'category']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parts', function (Blueprint $table) {
            $table->string('unit', 20)->default('piece');
            $table->string('category')->nullable();
        });

        // Restore data (best effort)
        $parts = DB::table('parts')->select('id', 'unit_id', 'category_id')->get();
        foreach ($parts as $part) {
            $updates = [];
            if ($part->unit_id) {
                $unit = DB::table('inventory_units')->find($part->unit_id);
                if ($unit) $updates['unit'] = $unit->name_ar;
            }
            if ($part->category_id) {
                $cat = DB::table('inventory_categories')->find($part->category_id);
                if ($cat) $updates['category'] = $cat->name_ar;
            }
            if (!empty($updates)) {
                DB::table('parts')->where('id', $part->id)->update($updates);
            }
        }

        Schema::table('parts', function (Blueprint $table) {
            $table->dropForeign(['unit_id']);
            $table->dropForeign(['category_id']);
            $table->dropColumn(['unit_id', 'category_id']);
        });
    }
};
