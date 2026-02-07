<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        $this->authorize('viewAny', Customer::class);

        $user = auth()->user();
        $customers = $user->is_admin
            ? Customer::with(['account', 'externalIdentifiers.integration'])->get()
            : ($user->account_id ? Customer::where('account_id', $user->account_id)->with(['account', 'externalIdentifiers.integration'])->get() : collect());

        $customers = $customers->map(function ($customer) {
            $customerArray = $customer->toArray();
            $customerArray['external_identifiers'] = $customer->externalIdentifiers->map(fn ($identifier) => [
                'integration_type' => $identifier->integration->type->value,
                'external_type' => $identifier->external_type,
                'external_id' => $identifier->external_id,
                'data' => $identifier->data,
            ])->toArray();

            return $customerArray;
        });

        return Inertia::render('creator/customers/CustomerIndexPage', [
            'customers' => $customers,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * TODO: Re-enable in Stage 2 when Fibermade becomes the selling surface.
     */
    public function create(): Response
    {
        $this->authorize('create', Customer::class);

        return Inertia::render('creator/customers/CustomerCreatePage');
    }

    /**
     * Store a newly created resource in storage.
     *
     * TODO: Re-enable in Stage 2 when Fibermade becomes the selling surface.
     * Currently disabled via CustomerPolicy in Stage 1.
     */
    public function store(StoreCustomerRequest $request): RedirectResponse
    {
        $this->authorize('create', Customer::class);

        Customer::create([
            ...$request->validated(),
            'account_id' => $request->user()->account_id,
        ]);

        return redirect()->route('customers.index');
    }

    /**
     * Display the specified resource (read-only view).
     */
    public function show(Customer $customer): Response
    {
        $this->authorize('view', $customer);

        $customer->load(['orders.orderable', 'externalIdentifiers.integration']);
        $customerArray = $customer->toArray();
        $customerArray['external_identifiers'] = $customer->externalIdentifiers->map(fn ($identifier) => [
            'integration_type' => $identifier->integration->type->value,
            'external_type' => $identifier->external_type,
            'external_id' => $identifier->external_id,
            'data' => $identifier->data,
        ])->toArray();

        return Inertia::render('creator/customers/CustomerEditPage', [
            'customer' => $customerArray,
            'orders' => $customer->orders->map(fn ($order) => [
                'id' => $order->id,
                'order_date' => $order->order_date->toDateString(),
                'status' => $order->status->value,
                'total_amount' => $order->total_amount,
                'orderable' => $order->orderable ? [
                    'name' => $order->orderable->name,
                ] : null,
            ]),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * TODO: In Stage 2, convert back to edit or keep both show and edit routes.
     * Currently redirects to show for read-only viewing in Stage 1.
     */
    public function edit(Customer $customer): Response
    {
        return $this->show($customer);
    }

    /**
     * Update the specified resource in storage.
     *
     * TODO: Re-enable in Stage 2 when Fibermade becomes the selling surface.
     * Currently disabled via CustomerPolicy in Stage 1.
     */
    public function update(UpdateCustomerRequest $request, Customer $customer): RedirectResponse
    {
        $this->authorize('update', $customer);

        $customer->update($request->validated());

        return redirect()->route('customers.index');
    }

    /**
     * Update the notes field on the customer.
     *
     * TODO: Re-enable in Stage 2 when Fibermade becomes the selling surface.
     * Currently disabled via CustomerPolicy in Stage 1.
     */
    public function updateNotes(UpdateCustomerRequest $request, Customer $customer): RedirectResponse
    {
        $this->authorize('update', $customer);

        $customer->update(['notes' => $request->input('notes')]);

        return redirect()->route('customers.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * TODO: Re-enable in Stage 2 when Fibermade becomes the selling surface.
     * Currently disabled via CustomerPolicy in Stage 1.
     */
    public function destroy(Customer $customer): RedirectResponse
    {
        $this->authorize('delete', $customer);

        $customer->delete();

        return redirect()->route('customers.index');
    }
}
