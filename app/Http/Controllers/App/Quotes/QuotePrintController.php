<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Quotes;

use App\Http\Controllers\Controller;
use App\Models\Quote;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Inertia\Inertia;
use Inertia\Response;

class QuotePrintController extends Controller
{
    use AuthorizesRequests;

    /**
     * Print the specified quote.
     */
    public function print(Quote $quote): Response
    {
        $this->authorize('view', $quote);

        $quote->load([
            'center.address',
            'customer',
            'vehicle.make',
            'vehicle.customer',
            'vehicle.model',
            'lines.service.department',
            'parts.part' => fn ($q) => $q->with('inventoryBalances')->withSum('inventoryBalances', 'qty_on_hand'),
            'parts.quoteLine',
            'departments',
            'createdByUser',
        ]);

        return Inertia::render('Quotes/Print', [
            'quote' => $quote,
        ]);
    }
}
