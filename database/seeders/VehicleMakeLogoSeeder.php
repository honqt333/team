<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\VehicleMake;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/** Generate missing placeholders without replacing existing brand artwork. */
class VehicleMakeLogoSeeder extends Seeder
{
    public function run(): void
    {
        foreach (VehicleMake::query()->where('source', 'system')->get() as $make) {
            if ($make->logo_path) {
                continue;
            }
            $path = 'makes/placeholders/make-'.$make->id.'.svg';

            if (! Storage::disk('public')->exists($path)) {
                $letter = htmlspecialchars(mb_strtoupper(mb_substr($make->name_en ?: $make->name_ar ?: '?', 0, 1)), ENT_XML1, 'UTF-8');
                $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200"><rect width="200" height="200" rx="32" fill="#1e40af"/><text x="100" y="140" text-anchor="middle" font-family="IBM Plex Sans Arabic" font-size="120" fill="#fff">'.$letter.'</text></svg>';

                if (! Storage::disk('public')->put($path, $svg)) {
                    throw new RuntimeException(__('messages.setup.logo_write_failed'));
                }
            }
            $make->update(['logo_path' => $path]);
        }
    }
}
