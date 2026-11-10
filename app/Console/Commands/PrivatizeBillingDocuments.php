<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PrivatizeBillingDocuments extends Command
{
    protected $signature = 'billing:privatize-documents {--apply : Copy, verify and remove public originals}';

    protected $description = 'Preview or privatize legacy billing documents with verified copies';

    public function handle(): int
    {
        $public = Storage::disk('public');
        $private = Storage::disk('local');

        foreach (['invoices/subscription', 'bank-transfer-receipts'] as $directory) {
            foreach ($public->allFiles($directory) as $path) {
                $this->line($path);

                if (! $this->option('apply')) {
                    continue;
                }
                $bytes = $public->get($path);

                if ($private->exists($path)) {
                    if (! hash_equals(hash('sha256', $bytes), hash('sha256', $private->get($path)))) {
                        $this->error('Conflicting private copy: '.$path);

                        return self::FAILURE;
                    }
                } elseif (! $private->put($path, $bytes)) {
                    $this->error('Copy failed: '.$path);

                    return self::FAILURE;
                }

                if (! $private->exists($path) || ! hash_equals(hash('sha256', $bytes), hash('sha256', $private->get($path)))) {
                    $this->error('Verification failed: '.$path);

                    return self::FAILURE;
                }

                if (! $public->delete($path)) {
                    $this->error('Public removal failed: '.$path);

                    return self::FAILURE;
                }
            }
        }

        return self::SUCCESS;
    }
}
