<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Scan codebase for translation key usages and compare against lang/en/*.php + lang/ar/*.php.
 *
 * Usage:
 *   php artisan translations:check                  # report all missing keys
 *   php artisan translations:check --locale=en      # only check EN side
 *   php artisan translations:check --locale=ar      # only check AR side
 *   php artisan translations:check --json           # output JSON for CI
 *   php artisan translations:check --fix            # print a stub for missing keys (do not write)
 *   php artisan translations:check --strict         # exit 1 on any missing key
 *   php artisan translations:check --vue             # also scan Vue i18n JSON files
 */
class TranslationsCheck extends Command
{
    protected $signature = 'translations:check
                            {--locale= : Restrict check to a single locale (en|ar)}
                            {--json : Output JSON instead of human report}
                            {--fix : Print a stub array of missing keys for the missing locale}
                            {--strict : Exit with code 1 when missing keys are found}
                            {--vue : Also scan Vue i18n JSON files (resources/js/i18n/lang/*.json)}
                            {--vue-only : Only check Vue i18n JSON files}
                            {--no-placeholders : Skip the AR-placeholder value check}';

    protected $description = 'Scan codebase for translation key usages and report keys that are not yet defined in lang/{en,ar}/*.php. Also flags AR placeholder values like "[AR] foo.bar".';

    /** @var array<int, string> */
    private array $patterns = [
        '/__\(\s*[\'"]([^\'"]+)[\'"]\s*[\),]/',
        '/trans\(\s*[\'"]([^\'"]+)[\'"]\s*[\),]/',
        '/@lang\(\s*[\'"]([^\'"]+)[\'"]\s*\)/',
        '/Lang::get\(\s*[\'"]([^\'"]+)[\'"]\s*[\),]/',
        '/Lang::choice\(\s*[\'"]([^\'"]+)[\'"]\s*[\),]/',
        '/Lang::has\(\s*[\'"]([^\'"]+)[\'"]\s*\)/',
        '/\$t\(\s*[\'"]([^\'"]+)[\'"]\s*[\),]/',
        '/\bt\(\s*[\'"]([^\'"]+)[\'"]\s*[\),]/',
        '/i18n\.t\(\s*[\'"]([^\'"]+)[\'"]\s*[\),]/',
        '/i18n\.global\.t\(\s*[\'"]([^\'"]+)[\'"]\s*[\),]/',
    ];

    /** @var array<int, string> */
    private array $skipDirs = [
        'vendor', 'node_modules', '.git', 'storage/framework',
        'storage/logs', 'public/build', 'bootstrap/cache',
    ];

    public function handle(): int
    {
        $base = base_path();
        $filterLocale = $this->option('locale');
        $asJson = (bool) $this->option('json');
        $fix = (bool) $this->option('fix');
        $strict = (bool) $this->option('strict');
        $withVue = (bool) $this->option('vue');
        $vueOnly = (bool) $this->option('vue-only');
        $skipPlaceholders = (bool) $this->option('no-placeholders');

        $locales = $filterLocale ? [$filterLocale] : ['en', 'ar'];

        // 1) Collect defined keys per locale (PHP files)
        $definedKeys = []; // locale => ['file.key.sub' => true]
        $placeholderValues = []; // locale => ['file.key.sub' => placeholder_string]
        $placeholderPattern = '/^\[(?:AR|EN|TODO)[^\]]*\]\s/';

        foreach ($locales as $locale) {
            $definedKeys[$locale] = [];
            $placeholderValues[$locale] = [];

            foreach (glob($base."/lang/{$locale}/*.php") as $file) {
                $name = basename($file, '.php');
                $arr = include $file;

                foreach ($this->flatten($arr) as $k => $v) {
                    $definedKeys[$locale]["{$name}.{$k}"] = true;

                    // Detect placeholder values like "[AR] foo.bar" or "[TODO:ar] foo"
                    if (is_string($v) && preg_match($placeholderPattern, $v)) {
                        $placeholderValues[$locale]["{$name}.{$k}"] = $v;
                    }
                }
            }
        }

        // 1b) Collect defined keys per locale (Vue i18n JSON files)
        $vueDefined = []; // locale => ['key.sub' => true]

        foreach ($locales as $locale) {
            $vueDefined[$locale] = [];
            $jsonPath = $base."/resources/js/i18n/lang/{$locale}.json";

            if (file_exists($jsonPath)) {
                $arr = json_decode(file_get_contents($jsonPath), true) ?: [];

                foreach ($this->flatten($arr) as $k => $_) {
                    $vueDefined[$locale][$k] = true;
                }
            }
        }

        // 2) Scan codebase for usage
        $usages = []; // 'file.key.sub' => [files...]
        $scanDirs = ['app', 'resources/views', 'resources/js', 'database', 'routes'];

        foreach ($scanDirs as $d) {
            $full = $base.'/'.$d;

            if (! is_dir($full)) {
                continue;
            }
            $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($full));

            foreach ($rii as $f) {
                if ($f->isDir()) {
                    continue;
                }
                $path = $f->getPathname();

                foreach ($this->skipDirs as $s) {
                    if (strpos($path, '/'.$s.'/') !== false) {
                        continue 2;
                    }
                }
                $ext = $f->getExtension();

                if (! in_array($ext, ['php', 'vue', 'js', 'ts'], true)) {
                    continue;
                }
                $content = @file_get_contents($path);

                if ($content === false) {
                    continue;
                }

                foreach ($this->patterns as $pat) {
                    if (preg_match_all($pat, $content, $m)) {
                        foreach ($m[1] as $key) {
                            // skip dynamic markers / non-keys
                            if (strpos($key, '$') !== false) {
                                continue;
                            }

                            if (preg_match('/\{[^}]+\}/', $key)) {
                                continue;
                            }

                            if (strpos($key, ' ') !== false) {
                                continue;
                            }

                            // Skip bilingual model attributes (e.g., title_ar, name_en) —
                            // these are database fields, not translation keys.
                            if (preg_match('/_(ar|en)$/', $key)) {
                                continue;
                            }
                            $usages[$key][] = str_replace($base.'/', '', $path);
                        }
                    }
                }
            }
        }

        foreach ($usages as $k => &$files) {
            $files = array_values(array_unique($files));
        }
        unset($files);

        // 3) Find missing per locale
        $missing = []; // locale => [key => files]

        foreach ($locales as $locale) {
            $missing[$locale] = [];

            foreach ($usages as $key => $files) {
                if (! isset($definedKeys[$locale][$key])) {
                    $missing[$locale][$key] = $files;
                }
            }
            ksort($missing[$locale]);
        }

        // 3b) Vue i18n cross-check: keys in EN missing in AR, and vice versa
        $vueMissing = []; // locale => [key => value]

        if ($withVue || $vueOnly) {
            foreach ($locales as $locale) {
                $otherLocale = $locale === 'en' ? 'ar' : 'en';
                $vueMissing[$locale] = [];

                foreach ($vueDefined[$locale] as $key => $_) {
                    if (! isset($vueDefined[$otherLocale][$key])) {
                        $vueMissing[$locale][$key] = "Only in {$locale} JSON";
                    }
                }
                ksort($vueMissing[$locale]);
            }
        }

        if ($vueOnly) {
            // Override: skip PHP file checks
            $missing = [];
        }

        // 4) Output
        if ($fix) {
            $this->line('<?php');
            $this->line('');
            $this->line('// Paste this into the appropriate lang/{locale}/*.php file.');
            $this->line('// Each missing key has a placeholder value.');

            foreach ($locales as $locale) {
                if (empty($missing[$locale])) {
                    continue;
                }
                $this->line('');
                $this->line("// --- {$locale} ---");

                foreach ($missing[$locale] as $key => $files) {
                    $placeholder = '[TODO:'.$locale.'] '.$key;
                    $this->line('// used in: '.implode(', ', array_slice($files, 0, 3)).(count($files) > 3 ? ', ...' : ''));
                    $this->line("'{$key}' => '{$placeholder}',");
                }
            }

            return self::SUCCESS;
        }

        $totalMissing = 0;

        foreach ($missing as $loc => $keys) {
            $totalMissing += count($keys);
        }

        // 3c) Placeholder values (e.g. "[AR] foo.bar") per locale — independent
        // of usage; these are untranslated values that still render to the user.
        $placeholders = []; // locale => [key => value]

        if (! $skipPlaceholders) {
            foreach ($locales as $locale) {
                $placeholders[$locale] = $placeholderValues[$locale] ?? [];
                ksort($placeholders[$locale]);
            }
        }

        $totalPlaceholders = 0;

        foreach ($placeholders as $loc => $items) {
            $totalPlaceholders += count($items);
        }

        if ($asJson) {
            $payload = [
                'total_missing' => $totalMissing,
                'locales' => array_map(
                    fn ($keys) => array_map(
                        fn ($files) => ['files' => $files, 'count' => count($files)],
                        $keys
                    ),
                    $missing
                ),
            ];

            if ($withVue || $vueOnly) {
                $payload['vue_missing'] = $vueMissing;
            }

            if (! $skipPlaceholders) {
                $payload['total_placeholders'] = $totalPlaceholders;
                $payload['placeholders'] = $placeholders;
            }

            $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        } else {
            $this->info('Translation check complete.');
            $this->line('  Total usages found: '.count($usages));
            $this->line('  Total missing keys: '.$totalMissing);

            if (! $skipPlaceholders) {
                $this->line('  Total placeholder values (untranslated): '.$totalPlaceholders);
            }

            $this->line('');

            foreach ($missing as $loc => $keys) {
                $this->line("--- Missing in '{$loc}' (".count($keys).' keys) ---');

                if (empty($keys)) {
                    $this->line('  (none)');
                    $this->line('');
                    continue;
                }
                $byFile = [];

                foreach ($keys as $key => $files) {
                    $prefix = explode('.', $key)[0];
                    $byFile[$prefix][$key] = $files;
                }
                ksort($byFile);

                foreach ($byFile as $prefix => $items) {
                    $this->line("  [{$prefix}] ".count($items).' keys');

                    foreach ($items as $key => $files) {
                        $first = $files[0] ?? '';
                        $this->line("    {$key}  ({$first}".(count($files) > 1 ? ' +'.(count($files) - 1) : '').')');
                    }
                }
                $this->line('');
            }

            // Placeholder values report (untranslated AR/EN values)
            if (! $skipPlaceholders && $totalPlaceholders > 0) {
                foreach ($placeholders as $loc => $items) {
                    if (empty($items)) {
                        continue;
                    }
                    $this->line("--- Placeholder values in '{$loc}' (".count($items).' untranslated) ---');

                    $byFile = [];

                    foreach ($items as $key => $val) {
                        $prefix = explode('.', $key)[0];
                        $byFile[$prefix][] = $key;
                    }
                    ksort($byFile);

                    foreach ($byFile as $prefix => $keys) {
                        $this->line("  [{$prefix}] ".count($keys).' keys');

                        foreach ($keys as $key) {
                            $sample = mb_substr($items[$key], 0, 60);
                            $this->line("    {$key}  (value: \"{$sample}\")");
                        }
                    }
                    $this->line('');
                }
            }

            // Vue JSON cross-check
            if ($withVue || $vueOnly) {
                foreach ($vueMissing as $loc => $items) {
                    $this->line("--- Vue i18n keys missing in '{$loc}' JSON (defined in other locale) (".count($items).' keys) ---');

                    foreach ($items as $key => $reason) {
                        $this->line("  {$key}");
                    }
                    $this->line('');
                }
            }
        }

        // Strict fails on missing keys OR placeholder values.
        $hasIssues = ($totalMissing > 0) || (! $skipPlaceholders && $totalPlaceholders > 0);

        return ($hasIssues && $strict) ? self::FAILURE : self::SUCCESS;
    }

    private function flatten($arr, string $prefix = ''): array
    {
        $r = [];

        foreach ($arr as $k => $v) {
            // Force string key. Some lang files have numeric-keyed
            // entries (e.g. status enums); array_merge would renumber
            // them, which then breaks the `key.replace('.', '.')`
            // substring search the rest of the command does.
            $newKey = $prefix === '' ? (string) $k : $prefix.'.'.(string) $k;

            if (is_array($v)) {
                $r = array_merge($r, $this->flatten($v, $newKey));
            } else {
                $r[$newKey] = $v;
            }
        }

        return $r;
    }
}
