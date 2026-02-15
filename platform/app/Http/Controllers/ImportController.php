<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportCollectionsRequest;
use App\Http\Requests\ImportCustomersRequest;
use App\Http\Requests\ImportOrdersRequest;
use App\Http\Requests\ImportProductsRequest;
use App\Services\ImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;

class ImportController extends Controller
{
    public function __construct(
        private ImportService $importService
    ) {}

    /**
     * Import products, collections, and inventory from CSV files.
     */
    public function importProducts(ImportProductsRequest $request): RedirectResponse
    {
        $user = $request->user();
        $accountId = $user->account_id;

        if (! $accountId) {
            return Redirect::route('user.edit', ['tab' => 'import'])
                ->withErrors(['error' => 'You must be associated with an account to import data.']);
        }

        // Import products first
        $productsResult = $this->importService->importProducts(
            $request->file('products_file'),
            $accountId
        );

        // Import collections
        $collectionsResult = $this->importService->importCollections(
            $request->file('collections_file'),
            $accountId
        );

        // Combine results
        $allErrors = array_merge(
            $productsResult['errors'] ?? [],
            $collectionsResult['errors'] ?? []
        );

        if ($productsResult['success'] && $collectionsResult['success']) {
            $message = sprintf(
                'Import completed successfully! Created %d colorways, updated %d colorways, created %d bases, created %d collections, updated %d collections, linked %d colorways to collections.',
                $productsResult['colorways_created'] ?? 0,
                $productsResult['colorways_updated'] ?? 0,
                $productsResult['bases_created'] ?? 0,
                $collectionsResult['collections_created'] ?? 0,
                $collectionsResult['collections_updated'] ?? 0,
                $collectionsResult['colorways_linked'] ?? 0
            );

            if (! empty($allErrors)) {
                $message .= ' Some errors occurred: '.implode(', ', array_slice($allErrors, 0, 5));
            }

            $warnings = $productsResult['warnings'] ?? [];
            if (! empty($warnings)) {
                $message .= ' Warnings: '.implode('; ', array_slice($warnings, 0, 5));
            }

            return Redirect::route('user.edit', ['tab' => 'import'])
                ->with('success', $message);
        }

        return Redirect::route('user.edit', ['tab' => 'import'])
            ->withErrors(['error' => 'Import failed: '.implode(', ', $allErrors)]);
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

    /**
     * Import collections from CSV file.
     */
    public function importCollections(ImportCollectionsRequest $request): RedirectResponse
    {
        $user = $request->user();
        $accountId = $user->account_id;

        if (! $accountId) {
            return back()->withErrors(['error' => 'You must be associated with an account to import data.']);
        }

        $result = $this->importService->importCollections(
            $request->file('collections_file'),
            $accountId
        );

        if ($result['success']) {
            $message = sprintf(
                'Import completed successfully! Created %d collections, updated %d collections, linked %d colorways.',
                $result['collections_created'],
                $result['collections_updated'],
                $result['colorways_linked']
            );

            if (! empty($result['errors'])) {
                $message .= ' Some errors occurred: '.implode(', ', array_slice($result['errors'], 0, 5));
            }

            return back()->with('success', $message);
        }

        return back()->withErrors(['error' => 'Import failed: '.implode(', ', $result['errors'])]);
    }
}
