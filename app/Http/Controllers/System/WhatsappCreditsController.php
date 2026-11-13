<?php

declare(strict_types=1);

namespace App\Http\Controllers\System;

use App\Models\Credits\TenantWhatsappBalance;
use App\Models\Credits\WhatsappPackage;
use App\Models\Credits\WhatsappPurchase;
use App\Models\Credits\WhatsappUsageLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class WhatsappCreditsController extends BaseCreditsController
{
    protected function channelKey(): string
    {
        return 'whatsapp';
    }

    protected function packageModel(): string
    {
        return WhatsappPackage::class;
    }

    protected function balanceModel(): string
    {
        return TenantWhatsappBalance::class;
    }

    protected function purchaseModel(): string
    {
        return WhatsappPurchase::class;
    }

    protected function usageLogModel(): string
    {
        return WhatsappUsageLog::class;
    }

    protected function inertiaPage(): string
    {
        return 'System/Credits/WhatsappHub';
    }

    protected function extraStats(Collection $packages): array
    {
        return [
            'read' => WhatsappUsageLog::where('status', 'read')->count(),
        ];
    }

    public function updatePackage(Request $request, WhatsappPackage $package): RedirectResponse
    {
        return $this->performUpdatePackage($request, $package);
    }

    public function destroyPackage(WhatsappPackage $package): RedirectResponse
    {
        return $this->performDestroyPackage($package);
    }
}
