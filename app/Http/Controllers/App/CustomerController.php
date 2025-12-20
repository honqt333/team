<?php

namespace App\Http\Controllers\App;

use App\Http\Requests\CustomerStoreRequest;
use App\Http\Requests\CustomerUpdateRequest;
use App\Models\Customer;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;

class CustomerController
{
    use AuthorizesRequests;

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Customer::class);

        $customers = Customer::paginate(15);

        return response()->json($customers);
    }

    public function store(CustomerStoreRequest $request): JsonResponse
    {
        $this->authorize('create', Customer::class);

        $customer = Customer::create($request->validated());

        return response()->json($customer, 201);
    }

    public function show(Customer $customer): JsonResponse
    {
        $this->authorize('view', $customer);

        return response()->json($customer);
    }

    public function update(CustomerUpdateRequest $request, Customer $customer): JsonResponse
    {
        $this->authorize('update', $customer);

        $customer->update($request->validated());

        return response()->json($customer);
    }

    public function destroy(Customer $customer): JsonResponse
    {
        $this->authorize('delete', $customer);

        $customer->delete();

        return response()->json(null, 204);
    }
}
