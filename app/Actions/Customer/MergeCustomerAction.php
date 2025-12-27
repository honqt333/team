<?php

namespace App\Actions\Customer;

use App\Models\Customer;
use Illuminate\Support\Facades\DB;

class MergeCustomerAction
{
    /**
     * Merge source customer into target customer.
     * All related data from source will be transferred to target, then source is deleted.
     *
     * @param Customer $source The customer to merge FROM (will be deleted)
     * @param Customer $target The customer to merge TO (will remain)
     * @return Customer The target customer with merged data
     */
    public function execute(Customer $source, Customer $target): Customer
    {
        return DB::transaction(function () use ($source, $target) {
            // Transfer vehicles
            $source->vehicles()->update(['customer_id' => $target->id]);

            // Transfer work orders
            $source->workOrders()->update(['customer_id' => $target->id]);

            // Transfer quotes
            $source->quotes()->update(['customer_id' => $target->id]);

            // Transfer invoices (if exists)
            if (method_exists($source, 'invoices')) {
                $source->invoices()->update(['customer_id' => $target->id]);
            }

            // Transfer payments (if exists)
            if (method_exists($source, 'payments')) {
                $source->payments()->update(['customer_id' => $target->id]);
            }

            // Delete source customer (soft delete)
            $source->delete();

            // Refresh target to get updated counts
            $target->refresh();

            return $target;
        });
    }
}
