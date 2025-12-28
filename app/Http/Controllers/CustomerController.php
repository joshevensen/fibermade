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
            ? Customer::with('account')->get()
            : ($user->account_id ? Customer::where('account_id', $user->account_id)->with('account')->get() : collect());

        return Inertia::render('customers/CustomerIndexPage', [
            'customers' => $customers,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        $this->authorize('create', Customer::class);

        return Inertia::render('customers/CustomerCreatePage');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCustomerRequest $request): RedirectResponse
    {
        Customer::create([
            ...$request->validated(),
            'account_id' => $request->user()->account_id,
        ]);

        return redirect()->route('customers.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Customer $customer): Response
    {
        $this->authorize('view', $customer);

        return Inertia::render('customers/CustomerEditPage', [
            'customer' => $customer,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCustomerRequest $request, Customer $customer): RedirectResponse
    {
        $customer->update($request->validated());

        return redirect()->route('customers.index');
    }

    /**
     * Update the notes field on the customer.
     */
    public function updateNotes(UpdateCustomerRequest $request, Customer $customer): RedirectResponse
    {
        $this->authorize('update', $customer);

        $customer->update(['notes' => $request->input('notes')]);

        return redirect()->route('customers.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Customer $customer): RedirectResponse
    {
        $this->authorize('delete', $customer);

        $customer->delete();

        return redirect()->route('customers.index');
    }
}
