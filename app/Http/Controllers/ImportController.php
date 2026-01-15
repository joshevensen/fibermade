<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportCustomersRequest;
use App\Http\Requests\ImportOrdersRequest;
use App\Http\Requests\ImportProductsRequest;
use App\Services\ImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ImportController extends Controller
{
    public function __construct(
        private ImportService $importService
    ) {
    }

    /**
     * Import products and inventory from CSV files.
     */
    public function importProducts(ImportProductsRequest $request): RedirectResponse
    {
        $user = $request->user();
        $accountId = $user->account_id;

        if (! $accountId) {
            return back()->withErrors(['error' => 'You must be associated with an account to import data.']);
        }

        $result = $this->importService->importProducts(
            $request->file('products_file'),
            $request->file('inventory_file'),
            $accountId
        );

        if ($result['success']) {
            $message = sprintf(
                'Import completed successfully! Created %d colorways, updated %d colorways, created %d bases, updated %d bases, updated %d inventory entries.',
                $result['colorways_created'],
                $result['colorways_updated'],
                $result['bases_created'],
                $result['bases_updated'],
                $result['inventory_updated']
            );

            if (! empty($result['errors'])) {
                $message .= ' Some errors occurred: '.implode(', ', array_slice($result['errors'], 0, 5));
            }

            return back()->with('success', $message);
        }

        return back()->withErrors(['error' => 'Import failed: '.implode(', ', $result['errors'])]);
    }

    /**
     * Import orders from CSV file.
     */
    public function importOrders(ImportOrdersRequest $request): RedirectResponse
    {
        $user = $request->user();
        $accountId = $user->account_id;

        if (! $accountId) {
            return back()->withErrors(['error' => 'You must be associated with an account to import data.']);
        }

        $result = $this->importService->importOrders(
            $request->file('orders_file'),
            $accountId
        );

        if ($result['success']) {
            $message = sprintf(
                'Import completed successfully! Created %d orders, updated %d orders, created %d order items.',
                $result['orders_created'],
                $result['orders_updated'],
                $result['order_items_created']
            );

            if (! empty($result['errors'])) {
                $message .= ' Some errors occurred: '.implode(', ', array_slice($result['errors'], 0, 5));
            }

            return back()->with('success', $message);
        }

        return back()->withErrors(['error' => 'Import failed: '.implode(', ', $result['errors'])]);
    }

    /**
     * Import customers from CSV file.
     */
    public function importCustomers(ImportCustomersRequest $request): RedirectResponse
    {
        $user = $request->user();
        $accountId = $user->account_id;

        if (! $accountId) {
            return back()->withErrors(['error' => 'You must be associated with an account to import data.']);
        }

        $result = $this->importService->importCustomers(
            $request->file('customers_file'),
            $accountId
        );

        if ($result['success']) {
            $message = sprintf(
                'Import completed successfully! Created %d customers, updated %d customers.',
                $result['customers_created'],
                $result['customers_updated']
            );

            if (! empty($result['errors'])) {
                $message .= ' Some errors occurred: '.implode(', ', array_slice($result['errors'], 0, 5));
            }

            return back()->with('success', $message);
        }

        return back()->withErrors(['error' => 'Import failed: '.implode(', ', $result['errors'])]);
    }
}
