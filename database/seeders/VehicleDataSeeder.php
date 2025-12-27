<?php

namespace Database\Seeders;

use App\Models\VehicleMake;
use App\Models\VehicleModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VehicleDataSeeder extends Seeder
{
    protected $arabicNames = [
        'Toyota' => 'تويوتا',
        'Honda' => 'هوندا',
        'Nissan' => 'نيسان',
        'Ford' => 'فورد',
        'Chevrolet' => 'شيفروليه',
        'Hyundai' => 'هيونداي',
        'Kia' => 'كيا',
        'Mercedes-Benz' => 'مرسيدس بنز',
        'BMW' => 'بي إم دبليو',
        'Audi' => 'أودي',
        'Volkswagen' => 'فولكس فاجن',
        'Lexus' => 'لكزس',
        'Mazda' => 'مازدا',
        'Subaru' => 'سوبارو',
        'Jeep' => 'جيب',
        'Dodge' => 'دودج',
        'GMC' => 'جي إم سي',
        'Cadillac' => 'كاديلاك',
        'Porsche' => 'بورش',
        'Land Rover' => 'لاند روفر',
        'Jaguar' => 'جاكوار',
        'Volvo' => 'فولفو',
        'Ferrari' => 'فيراري',
        'Lamborghini' => 'لامبورغيني',
        'Maserati' => 'مازيراتي',
        'Bentley' => 'بنتلي',
        'Rolls-Royce' => 'رولز رويس',
        'Aston Martin' => 'أستون مارتن',
        'McLaren' => 'مكلارين',
        'Bugatti' => 'بوغاتي',
        'Tesla' => 'تسلا',
        'Mitsubishi' => 'ميتسوبيشي',
        'Suzuki' => 'سوزوكي',
        'Isuzu' => 'ايسوزو',
        'Infiniti' => 'انفينيتي',
        'Acura' => 'اكيورا',
        'Lincoln' => 'لينكون',
        'Chrysler' => 'كرايسلر',
        'Buick' => 'بويك',
        'Mini' => 'ميني',
        'Fiat' => 'فيات',
        'Alfa Romeo' => 'ألفا روميو',
        'Peugeot' => 'بيجو',
        'Renault' => 'رينو',
        'Citroën' => 'سيتروين',
        'Škoda' => 'سكودا',
        'Skoda' => 'سكودا',
        'Seat' => 'سيات',
        'Opel' => 'أوبل',
        'Dacia' => 'داسيا',
        'Chery' => 'شيري',
        'Geely' => 'جيلي',
        'BYD' => 'بي واي دي',
        'Haval' => 'هافال',
        'Changan' => 'شانجان',
        'MG' => 'إم جي',
        'Daihatsu' => 'دايهاتسو',
        'Genesis' => 'جينيسيس',
        'Hummer' => 'همر',
        'Lotus' => 'لوتس',
        'Pagani' => 'باغاني',
        'SsangYong' => 'سانج يونج',
        'Tata' => 'تاتا',
    ];

    public function run(): void
    {
        // Path to logos
        $logosPath = storage_path('app/temp_data/logos_repo/logos/optimized');
        
        // Load JSON
        $jsonPath = storage_path('app/temp_data/car-list.json');
        if (!File::exists($jsonPath)) {
             $this->command->error("JSON file not found at $jsonPath");
             return;
        }
        
        $data = json_decode(File::get($jsonPath), true);
        
        $this->command->info('Seed started...');

        foreach ($data as $item) {
            $brandName = $item['brand'];
            $models = $item['models'];
            
            // Find logo
            $logoFile = $this->findLogo($logosPath, $brandName);
            $logoPath = null;
            
            if ($logoFile) {
                // Determine destination
                $ext = pathinfo($logoFile, PATHINFO_EXTENSION);
                $hash = Str::random(40);
                $filename = "makes/{$hash}.{$ext}";
                
                // Copy file to public storage
                if (File::exists($logoFile)) {
                    // Ensure directory exists
                    if (!Storage::disk('public')->exists('makes')) {
                        Storage::disk('public')->makeDirectory('makes');
                    }
                    
                    Storage::disk('public')->put($filename, File::get($logoFile));
                    $logoPath = $filename;
                }
            }
            
            // Create Make
            $make = VehicleMake::firstOrCreate(
                ['name_en' => $brandName],
                [
                    'name_ar' => $this->arabicNames[$brandName] ?? $brandName,
                    'logo_path' => $logoPath,
                    'source' => 'system',
                    'is_active' => true,
                ]
            );
            
            // Update logo if allowed (e.g. was missing)
            if ($make->wasRecentlyCreated === false && !$make->logo_path && $logoPath) {
                $make->update(['logo_path' => $logoPath]);
            }
            
            // Create Models
            // We'll chunk to avoid crazy number of queries if possible, but firstOrCreate is safer individually
            $count = 0;
            foreach ($models as $modelName) {
                // Ensure unique combination
                // We use name_en as the identifying logic for duplicates if name_ar is just a copy
                
                // Check if exists
                $exists = VehicleModel::where('make_id', $make->id)
                    ->where(function($q) use ($modelName) {
                        $q->where('name_en', $modelName)
                          ->orWhere('name_ar', $modelName);
                    })->exists();

                if (!$exists) {
                    VehicleModel::create([
                        'make_id' => $make->id,
                        'name_ar' => $modelName, // Default fallback
                        'name_en' => $modelName,
                        'source' => 'system',
                        'is_active' => true,
                    ]);
                    $count++;
                }
            }
            $this->command->info("Processed $brandName: Added/Updated with $count new models.");
        }
        
        $this->command->info('Seeding completed.');
    }
    
    protected function findLogo($basePath, $brandName)
    {
        // Try exact match slug
        $slug = Str::slug($brandName); 
        
        $extensions = ['png', 'jpg', 'jpeg', 'svg'];
        
        foreach ($extensions as $ext) {
            $path = "{$basePath}/{$slug}.{$ext}";
            if (File::exists($path)) {
                return $path;
            }
        }
        
        // Try iterating directory for loose match on filename
        // This is slow if directory is huge, but it's ~400 files, acceptable for local seed
        $files = File::files($basePath);
        foreach ($files as $file) {
             $filename = pathinfo($file->getFilename(), PATHINFO_FILENAME);
             if (Str::slug($filename) === $slug) {
                 return $file->getPathname();
             }
        }
        
        return null; // Not found
    }
}
