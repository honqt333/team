<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PurchaseInvoice;
use App\Models\PurchaseReturnInvoice;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class PurchaseAttachmentService
{
    public function replace(PurchaseInvoice|PurchaseReturnInvoice $document, ?UploadedFile $file): void
    {
        $disk = Storage::disk('public');
        $directory = $document instanceof PurchaseInvoice ? 'purchases/invoices' : 'purchases/returns';
        $path = $file?->store($directory.'/'.$document->getKey(), 'public');

        if ($path === false) {
            throw new RuntimeException('Failed to store purchase attachment.');
        }

        $connection = $document->getConnection();

        try {
            $previous = $connection->transaction(function () use ($document, $path) {
                $locked = $document->newQuery()->lockForUpdate()->findOrFail($document->getKey());
                $previous = $locked->attachment_path;

                if ($previous === $path) {
                    return $previous;
                }
                $locked->attachment_path = $path;

                if (! $locked->saveOrFail()) {
                    throw new RuntimeException('Failed to update purchase attachment.');
                }
                auth()->user()?->logActivity($path === null ? 'purchase_attachment_deleted' : 'purchase_attachment_uploaded', [
                    'document_type' => $locked->getMorphClass(),
                    'document_id' => $locked->getKey(),
                    'center_id' => $locked->center_id,
                ]);

                return $previous;
            });
        } catch (Throwable $exception) {
            if ($path !== null) {
                $disk->delete($path);
            }

            throw $exception;
        }

        if ($previous && $previous !== $path) {
            $connection->afterCommit(fn () => $disk->delete($previous));
        }
    }
}
