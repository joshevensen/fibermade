<?php

namespace App\Http\Controllers;

use App\Enums\AccountType;
use App\Enums\InviteType;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Http\Requests\StoreStoreRequest;
use App\Http\Requests\UpdateStoreRequest;
use App\Models\Creator;
use App\Models\Invite;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Store;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class StoreController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        $this->authorize('viewAny', Store::class);

        $user = auth()->user();
        $routeName = request()->route()->getName();
        $isCreatorRoute = ! str_starts_with($routeName, 'store.');
        $page = $isCreatorRoute
            ? 'creator/stores/StoreIndexPage'
            : 'store/vendors/VendorsIndexPage';

        if ($user->is_admin) {
            if ($isCreatorRoute) {
                $stores = Store::with('account')->get();
                $totalStores = $stores->count();
                $filteredCount = $totalStores;
                $stores = $this->transformStoresForIndex($stores);

                return Inertia::render($page, [
                    'stores' => $stores,
                    'totalStores' => $totalStores,
                    'filteredCount' => $filteredCount,
                ]);
            }

            return Inertia::render($page, [
                'vendors' => [],
                'totalVendors' => 0,
                'filteredCount' => 0,
            ]);
        }

        if ($user->account?->type === AccountType::Creator && $user->account->creator) {
            [$stores, $totalStores, $filteredCount] = $this->indexForCreator($user->account->creator, $isCreatorRoute);

            return Inertia::render($page, [
                'stores' => $stores,
                'totalStores' => $totalStores,
                'filteredCount' => $filteredCount,
            ]);
        }

        if ($user->account?->type === AccountType::Store && $user->account->store) {
            [$vendors, $totalVendors, $filteredCount] = $this->indexForStore($user->account->store);

            return Inertia::render($page, [
                'vendors' => $vendors,
                'totalVendors' => $totalVendors,
                'filteredCount' => $filteredCount,
            ]);
        }

        if ($isCreatorRoute) {
            return Inertia::render($page, [
                'stores' => [],
                'totalStores' => 0,
                'filteredCount' => 0,
            ]);
        }

        return Inertia::render($page, [
            'vendors' => [],
            'totalVendors' => 0,
            'filteredCount' => 0,
        ]);
    }

    /**
     * Display the store home page with creator cards.
     */
    public function home(): Response
    {
        $this->authorize('viewAny', Store::class);

        $user = auth()->user();

        if ($user->account?->type !== AccountType::Store || ! $user->account->store) {
            return Inertia::render('store/HomePage', [
                'creators' => [],
                'totalCreators' => 0,
                'filteredCount' => 0,
            ]);
        }

        $store = $user->account->store;
        $status = request()->query('status', 'active');
        if (! in_array($status, ['all', 'active', 'paused', 'ended'], true)) {
            $status = 'active';
        }

        $creatorQuery = $store->creators()->with('account');

        if ($status === 'all') {
            $creators = $creatorQuery->get();
        } else {
            $creators = $creatorQuery->wherePivot('status', $status)->get();
        }

        $totalCreators = $store->creators()->count();
        $filteredCount = $creators->count();

        $countsByAccount = Order::query()
            ->where('type', OrderType::Wholesale)
            ->where('orderable_type', Store::class)
            ->where('orderable_id', $store->id)
            ->selectRaw('account_id, status, count(*) as count')
            ->groupBy('account_id', 'status')
            ->get()
            ->groupBy('account_id')
            ->map(fn ($rows) => $rows->mapWithKeys(fn ($row) => [$row->status->value => (int) $row->count])->all())
            ->all();

        $creatorsData = $this->transformCreatorsForHome($creators, $store, $countsByAccount);

        return Inertia::render('store/HomePage', [
            'creators' => $creatorsData,
            'totalCreators' => $totalCreators,
            'filteredCount' => $filteredCount,
        ]);
    }

    /**
     * @param  Collection<int, \App\Models\Creator>  $creators
     * @param  array<int, array<string, int>>  $countsByAccount  account_id => [status => count]
     * @return array<int, array<string, mixed>>
     */
    private function transformCreatorsForHome(Collection $creators, Store $store, array $countsByAccount): array
    {
        $items = [];

        foreach ($creators as $creator) {
            $pivot = $creator->pivot;
            $counts = $countsByAccount[$creator->account_id] ?? [];

            $items[] = [
                'id' => $creator->id,
                'list_key' => 'creator-'.$creator->id,
                'name' => $creator->name,
                'email' => $creator->email,
                'city' => $creator->city,
                'state_region' => $creator->state_region,
                'status' => $pivot?->status ?? 'active',
                'draft_count' => $counts['draft'] ?? 0,
                'open_count' => $counts['open'] ?? 0,
                'delivered_count' => $counts['delivered'] ?? 0,
            ];
        }

        return $items;
    }

    /**
     * Display the order list for a creator (store's view).
     */
    public function orders(Creator $creator): Response|RedirectResponse
    {
        $this->authorize('viewAny', Store::class);

        $user = auth()->user();
        if ($user->account?->type !== AccountType::Store || ! $user->account->store) {
            return redirect()->route('store.home');
        }

        $store = $user->account->store;
        $this->authorize('viewCreatorOrders', [$store, $creator]);

        $status = request()->query('status', 'all');
        $validStatuses = ['all', ...array_map(fn (OrderStatus $case) => $case->value, OrderStatus::cases())];
        if (! in_array($status, $validStatuses, true)) {
            $status = 'all';
        }

        $query = Order::query()
            ->where('type', OrderType::Wholesale)
            ->where('orderable_type', Store::class)
            ->where('orderable_id', $store->id)
            ->where('account_id', $creator->account_id)
            ->withSum('orderItems as skein_count', 'quantity')
            ->addSelect([
                'colorway_count' => OrderItem::query()
                    ->selectRaw('count(distinct colorway_id)')
                    ->whereColumn('order_items.order_id', 'orders.id'),
            ])
            ->orderByDesc('order_date');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $orders = $query->get()->map(fn (Order $order) => [
            'id' => $order->id,
            'order_date' => $order->order_date->toDateString(),
            'status' => $order->status->value,
            'total_amount' => $order->total_amount !== null ? (float) $order->total_amount : null,
            'skein_count' => (int) ($order->skein_count ?? 0),
            'colorway_count' => (int) ($order->colorway_count ?? 0),
        ])->all();

        $orderStatusOptions = collect(OrderStatus::cases())->map(fn (OrderStatus $case) => [
            'label' => ucfirst($case->value),
            'value' => $case->value,
        ])->all();

        return Inertia::render('store/orders/OrderListPage', [
            'creator' => ['id' => $creator->id, 'name' => $creator->name],
            'orders' => $orders,
            'orderStatusOptions' => $orderStatusOptions,
        ]);
    }

    /**
     * @return array{0: array<int, array<string, mixed>>, 1: int, 2: int}
     */
    private function indexForCreator(Creator $creator, bool $mergeInvites): array
    {
        $status = request()->query('status', 'active');
        if (! in_array($status, ['all', 'invited', 'active', 'paused', 'ended'], true)) {
            $status = 'active';
        }

        $storeQuery = $creator->stores()->with('account');

        if ($status === 'invited') {
            $stores = collect();
        } elseif ($status === 'all') {
            $stores = $storeQuery->get();
        } else {
            $stores = $storeQuery->wherePivot('status', $status)->get();
        }

        $totalStores = $creator->stores()->count();

        if (! $mergeInvites) {
            $filteredCount = $stores->count();

            return [$this->transformStoresForIndex($stores), $totalStores, $filteredCount];
        }

        $invites = collect();
        if ($status === 'invited' || $status === 'all') {
            $invites = Invite::pending()
                ->where('invite_type', InviteType::Store)
                ->where('inviter_type', Creator::class)
                ->where('inviter_id', $creator->id)
                ->orderByDesc('created_at')
                ->get();
        }

        $items = $this->mergeStoresAndInvites($stores->sortBy('name')->values(), $invites);
        $filteredCount = $items->count();

        return [$items->all(), $totalStores, $filteredCount];
    }

    /**
     * @return array{0: array<int, array<string, mixed>>, 1: int, 2: int}
     */
    private function indexForStore(Store $store): array
    {
        $status = request()->query('status', 'active');
        if (! in_array($status, ['all', 'active', 'paused', 'ended'], true)) {
            $status = 'active';
        }

        $creatorQuery = $store->creators()->with('account');

        if ($status === 'all') {
            $creators = $creatorQuery->get();
        } else {
            $creators = $creatorQuery->wherePivot('status', $status)->get();
        }

        $totalVendors = $store->creators()->count();
        $filteredCount = $creators->count();

        return [$this->transformVendorsForIndex($creators), $totalVendors, $filteredCount];
    }

    /**
     * @param  Collection<int, \App\Models\Creator>  $creators
     * @return array<int, array<string, mixed>>
     */
    private function transformVendorsForIndex(Collection $creators): array
    {
        $items = [];

        foreach ($creators as $creator) {
            $pivot = $creator->pivot;

            $items[] = [
                'id' => $creator->id,
                'list_key' => 'vendor-'.$creator->id,
                'name' => $creator->name,
                'email' => $creator->email,
                'city' => $creator->city,
                'state_region' => $creator->state_region,
                'status' => $pivot?->status ?? 'active',
            ];
        }

        return $items;
    }

    /**
     * @param  Collection<int, \App\Models\Store>  $stores
     * @param  Collection<int, \App\Models\Invite>  $invites
     * @return Collection<int, array<string, mixed>>
     */
    private function mergeStoresAndInvites(Collection $stores, Collection $invites): Collection
    {
        $items = collect();

        foreach ($invites as $invite) {
            $meta = $invite->metadata ?? [];
            $items->push([
                'id' => $invite->id,
                'list_key' => 'invite-'.$invite->id,
                'item_type' => 'invite',
                'name' => $meta['store_name'] ?? $invite->email,
                'email' => $invite->email,
                'owner_name' => $meta['owner_name'] ?? null,
                'address_line1' => '',
                'address_line2' => null,
                'city' => '',
                'state_region' => '',
                'postal_code' => '',
                'country_code' => '',
                'status' => 'invited',
                'is_invited' => true,
                'invite_id' => $invite->id,
            ]);
        }

        foreach ($stores as $store) {
            $pivot = $store->pivot;
            $items->push([
                'id' => $store->id,
                'list_key' => 'store-'.$store->id,
                'item_type' => 'store',
                'name' => $store->name,
                'email' => $store->email,
                'owner_name' => $store->owner_name,
                'address_line1' => $store->address_line1,
                'address_line2' => $store->address_line2,
                'city' => $store->city,
                'state_region' => $store->state_region,
                'postal_code' => $store->postal_code,
                'country_code' => $store->country_code,
                'status' => $pivot->status ?? 'active',
                'is_invited' => false,
                'invite_id' => null,
            ]);
        }

        return $items;
    }

    /**
     * @param  Collection<int, \App\Models\Store>  $stores
     * @return array<int, array<string, mixed>>
     */
    private function transformStoresForIndex(Collection $stores): array
    {
        $items = [];

        foreach ($stores as $store) {
            $pivot = $store->pivot ?? null;
            $items[] = [
                'id' => $store->id,
                'list_key' => 'store-'.$store->id,
                'item_type' => 'store',
                'name' => $store->name,
                'email' => $store->email,
                'owner_name' => $store->owner_name,
                'address_line1' => $store->address_line1,
                'address_line2' => $store->address_line2,
                'city' => $store->city,
                'state_region' => $store->state_region,
                'postal_code' => $store->postal_code,
                'country_code' => $store->country_code,
                'status' => $pivot?->status ?? 'active',
                'is_invited' => false,
                'invite_id' => null,
            ];
        }

        return $items;
    }

    /**
     * Store a newly created resource in storage.
     *
     * Note: Store creation should typically happen during registration.
     * This method may need to create both Account and Store records,
     * or handle store creation separately from account creation.
     */
    public function store(StoreStoreRequest $request): RedirectResponse
    {
        // For now, this assumes the account already exists (e.g., from registration)
        // If account doesn't exist, it should be created during registration flow
        Store::create([
            ...$request->validated(),
            'account_id' => $request->user()->account_id,
        ]);

        return redirect()->route('stores.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Store $store): Response
    {
        $this->authorize('view', $store);

        $store->load(['orders.orderable']);

        return Inertia::render('creator/stores/StoreEditPage', [
            'store' => $store,
            'orders' => $store->orders->map(fn ($order) => [
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
     * Update the specified resource in storage.
     */
    public function update(UpdateStoreRequest $request, Store $store): RedirectResponse
    {
        $store->update($request->validated());

        return redirect()->route('stores.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Store $store): RedirectResponse
    {
        $this->authorize('delete', $store);

        $store->delete();

        return redirect()->route('stores.index');
    }
}
