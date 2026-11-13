<?php

declare(strict_types=1);

namespace App\Http\Controllers\System;

use App\Models\Credits\SmsPackage;
use App\Models\Credits\SmsPurchase;
use App\Models\Credits\SmsUsageLog;
use App\Models\Credits\TenantSmsBalance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SmsCreditsController extends BaseCreditsController
{
    protected function channelKey(): string
    {
        return 'sms';
    }

    protected function packageModel(): string
    {
        return SmsPackage::class;
    }

    protected function balanceModel(): string
    {
        return TenantSmsBalance::class;
    }

    protected function purchaseModel(): string
    {
        return SmsPurchase::class;
    }

    protected function usageLogModel(): string
    {
        return SmsUsageLog::class;
    }

    protected function inertiaPage(): string
    {
        return 'System/Credits/SmsHub';
    }

    public function updatePackage(Request $request, SmsPackage $package): RedirectResponse
    {
        return $this->performUpdatePackage($request, $package);
    }

    public function destroyPackage(SmsPackage $package): RedirectResponse
    {
        return $this->performDestroyPackage($package);
    }
}
