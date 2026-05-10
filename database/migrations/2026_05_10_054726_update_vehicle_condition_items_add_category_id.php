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
        Schema::table('vehicle_condition_items', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('name_en')->constrained('vehicle_condition_categories')->nullOnDelete();
        });

        // Migrate data
        $items = \Illuminate\Support\Facades\DB::table('vehicle_condition_items')
            ->whereNotNull('category_ar')
            ->get();

        $categoriesMap = [];

        foreach ($items as $item) {
            $catAr = $item->category_ar;
            $catEn = $item->category_en;
            $key = $catAr . '|' . $catEn . '|' . $item->tenant_id . '|' . $item->center_id . '|' . $item->source;

            if (!isset($categoriesMap[$key])) {
                $categoryId = \Illuminate\Support\Facades\DB::table('vehicle_condition_categories')->insertGetId([
                    'tenant_id' => $item->tenant_id,
                    'center_id' => $item->center_id,
                    'source' => $item->source,
                    'name_ar' => $catAr,
                    'name_en' => $catEn,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $categoriesMap[$key] = $categoryId;
            }

            \Illuminate\Support\Facades\DB::table('vehicle_condition_items')
                ->where('id', $item->id)
                ->update(['category_id' => $categoriesMap[$key]]);
        }

        // Delete dummy items
        \Illuminate\Support\Facades\DB::table('vehicle_condition_items')
            ->whereRaw('name_ar = category_ar')
            ->delete();

        Schema::table('vehicle_condition_items', function (Blueprint $table) {
            $table->dropColumn(['category_ar', 'category_en']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicle_condition_items', function (Blueprint $table) {
            $table->string('category_ar')->nullable()->after('name_en');
            $table->string('category_en')->nullable()->after('category_ar');
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');
        });
    }
};
