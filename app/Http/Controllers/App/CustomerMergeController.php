<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Actions\Customer\MergeCustomerAction;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class CustomerMergeController extends Controller
{
    use AuthorizesRequests;

    /**
     * Get merge data for customer.
     */
    public function mergeData(Customer $customer): JsonResponse
    {
        $this->authorize('update', $customer);

        $query = Customer::where('id', '!=', $customer->id)
            ->where('type', $customer->type)
            ->withCount(['vehicles', 'quotes', 'workOrders'])
            ->orderBy('name');

        $otherCustomers = $query->get();

        return response()->json([
            'source' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'whatsapp' => $customer->whatsapp,
                'vehicles_count' => $customer->vehicles()->count(),
                'quotes_count' => $customer->quotes()->count(),
                'work_orders_count' => $customer->workOrders()->count(),
            ],
            'targets' => $otherCustomers->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'phone' => $c->phone,
                'whatsapp' => $c->whatsapp,
                'vehicles_count' => $c->vehicles_count,
                'quotes_count' => $c->quotes_count,
                'work_orders_count' => $c->work_orders_count,
            ]),
        ]);
    }

    /**
     * Execute merge of two customers.
     */
    public function executeMerge(Customer $source, Customer $target, MergeCustomerAction $action): RedirectResponse
    {
        $this->authorize('update', $source);
        $this->authorize('update', $target);

        $action->execute($source, $target);

        return redirect()->route('customers.show', $target)->with('success', __('messages.customer_merged'));
    }
}
