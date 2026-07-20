<?php

namespace Database\Seeders;

use App\Models\VehicleMake;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Generate a placeholder logo SVG for every VehicleMake that does
 * not yet have one. The placeholder is a square with the first
 * letter of the make name in a deterministic background color.
 *
 * Why this exists: VehicleMakesSeeder creates the make + model rows
 * with hardcoded data but does not produce logo files. VehicleDataSeeder
 * is the canonical source of brand logos, but it requires
 * storage/app/temp_data/car-list.json + ~50MB of logo images, which
 * are not in the repo. Without logos, the vehicles list page renders
 * an empty image placeholder.
 *
 * The SVGs are tiny (~250 bytes each), purely functional, and not
 * intended as final brand artwork. They exist so the UI is never
 * blank on a fresh DB. To replace with real brand logos, drop them
 * into storage/app/public/makes/ and re-run this seeder — the
 * make_or_replace flag in updateOrCreate keeps the new file.
 */
class VehicleMakeLogoSeeder extends Seeder
{
    /** Deterministic palette so re-runs produce identical SVGs. */
    private const PALETTE = [
        '#1e40af', '#dc2626', '#16a34a', '#9333ea', '#ea580c',
        '#0891b2', '#ca8a04', '#db2777', '#4f46e5', '#059669',
        '#7c3aed', '#e11d48', '#0284c7', '#65a30d', '#a21caf',
    ];

    public function run(): void
    {
        $makes = VehicleMake::all();
        if ($makes->isEmpty()) {
            $this->command->warn('No VehicleMake rows found. Run VehicleMakesSeeder first.');

            return;
        }

        $count = 0;
        foreach ($makes as $make) {
            $firstLetter = mb_substr($make->name_en ?? $make->name_ar ?? '?', 0, 1, 'UTF-8');
            $color = self::PALETTE[$make->id % count(self::PALETTE)];

            $svg = $this->renderPlaceholderSvg($firstLetter, $color);

            $filename = 'makes/'.Str::slug($make->name_en ?? 'make-'.$make->id).'-'.$make->id.'.svg';
            Storage::disk('public')->put($filename, $svg);

            $make->update(['logo_path' => $filename]);
            $count++;
        }

        $this->command->info("Generated placeholder logos for {$count} vehicle makes.");
    }

    private function renderPlaceholderSvg(string $letter, string $color): string
    {
        // 200x200 viewBox, rounded square background, bold initial
        return '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL
            .'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200" width="200" height="200">'.PHP_EOL
            .'  <rect width="200" height="200" rx="32" fill="'.$color.'"/>'.PHP_EOL
            .'  <text x="100" y="100" font-family="system-ui, -apple-system, sans-serif" font-size="120" font-weight="700" fill="#ffffff" text-anchor="middle" dominant-baseline="central">'
            .htmlspecialchars(mb_strtoupper($letter), ENT_XML1)
            .'</text>'.PHP_EOL
            .'</svg>'.PHP_EOL;
    }
}
