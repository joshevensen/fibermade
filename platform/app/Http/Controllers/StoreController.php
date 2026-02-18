<?php

namespace App\Http\Controllers;

use App\Enums\AccountType;
use App\Enums\BaseStatus;
use App\Enums\ColorwayStatus;
use App\Enums\InviteType;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Http\Requests\StoreOrderBuilderRequest;
use App\Http\Requests\StoreStoreRequest;
use App\Http\Requests\UpdateStoreRequest;
use App\Http\Requests\UpdateStoreStatusRequest;
use App\Mail\WholesaleNewOrderNotificationMail;
use App\Mail\WholesaleOrderConfirmationMail;
use App\Models\Collection;
use App\Models\Colorway;
use App\Models\Creator;
use App\Models\Inventory;
use App\Models\Invite;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Store;
use App\Services\WholesaleOrderMailHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
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
     * @param  SupportCollection<int, \App\Models\Creator>  $creators
     * @param  array<int, array<string, int>>  $countsByAccount  account_id => [status => count]
     * @return array<int, array<string, mixed>>
     */
    private function transformCreatorsForHome(SupportCollection $creators, Store $store, array $countsByAccount): array
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
                'accepted_count' => $counts['accepted'] ?? 0,
                'fulfilled_count' => $counts['fulfilled'] ?? 0,
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
     * Display the order detail page for the authenticated store.
     */
    public function showOrder(Order $order): Response|RedirectResponse
    {
        $this->authorize('viewAny', Store::class);

        $user = auth()->user();
        if ($user->account?->type !== AccountType::Store || ! $user->account->store) {
            return redirect()->route('store.home');
        }

        $store = $user->account->store;

        if ($order->orderable_type !== Store::class || $order->orderable_id !== $store->id) {
            abort(403);
        }

        $order->load([
            'orderItems.colorway.media',
            'orderItems.base',
            'orderable',
            'account.creator',
        ]);

        $creator = $order->account->creator;
        if (! $creator) {
            abort(404);
        }

        $itemsByColorway = $order->orderItems
            ->groupBy('colorway_id')
            ->map(function (SupportCollection $items, $colorwayId) {
                $first = $items->first();
                $colorway = $first->colorway;

                return [
                    'colorway' => [
                        'id' => $colorway?->id ?? (int) $colorwayId,
                        'name' => $colorway?->name ?? '',
                        'primary_image_url' => $colorway?->primary_image_url,
                    ],
                    'bases' => $items->map(fn (OrderItem $item) => [
                        'id' => $item->base->id ?? $item->base_id,
                        'descriptor' => $item->base->descriptor ?? '',
                        'weight' => $item->base->weight?->value,
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unit_price !== null ? (float) $item->unit_price : null,
                        'line_total' => $item->line_total !== null ? (float) $item->line_total : null,
                    ])->values()->all(),
                ];
            })
            ->values()
            ->all();

        $skeinCount = (int) $order->orderItems->sum('quantity');
        $colorwayCount = $order->orderItems->pluck('colorway_id')->unique()->count();

        return Inertia::render('store/orders/OrderDetailPage', [
            'id' => $order->id,
            'order_date' => $order->order_date->toDateString(),
            'status' => $order->status->value,
            'notes' => $order->notes,
            'subtotal_amount' => $order->subtotal_amount !== null ? (float) $order->subtotal_amount : null,
            'shipping_amount' => $order->shipping_amount !== null ? (float) $order->shipping_amount : null,
            'discount_amount' => $order->discount_amount !== null ? (float) $order->discount_amount : null,
            'tax_amount' => $order->tax_amount !== null ? (float) $order->tax_amount : null,
            'total_amount' => $order->total_amount !== null ? (float) $order->total_amount : null,
            'creator' => ['id' => $creator->id, 'name' => $creator->name],
            'skein_count' => $skeinCount,
            'colorway_count' => $colorwayCount,
            'items_by_colorway' => $itemsByColorway,
        ]);
    }

    /**
     * Display the order builder step 1: colorway selection.
     */
    public function order(Creator $creator): Response|RedirectResponse
    {
        $this->authorize('viewAny', Store::class);

        $user = auth()->user();
        if ($user->account?->type !== AccountType::Store || ! $user->account->store) {
            return redirect()->route('store.home');
        }

        $store = $user->account->store;
        $this->authorize('viewCreatorOrders', [$store, $creator]);

        $pivot = $store->creators()->where('creator_id', $creator->id)->first()?->pivot;
        $discountRate = $pivot?->discount_rate !== null ? (float) $pivot->discount_rate : null;

        $colorways = Colorway::query()
            ->where('account_id', $creator->account_id)
            ->where('status', ColorwayStatus::Active)
            ->with(['collections', 'inventories.base', 'media'])
            ->get();

        $collections = Collection::query()
            ->where('account_id', $creator->account_id)
            ->where('status', BaseStatus::Active)
            ->get();

        $colorwaysData = $this->transformColorwaysForOrderStep1($colorways);
        $collectionsData = $collections->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])->all();

        return Inertia::render('store/orders/ColorwaySelectionPage', [
            'creator' => ['id' => $creator->id, 'name' => $creator->name],
            'colorways' => $colorwaysData,
            'collections' => $collectionsData,
            'discount_rate' => $discountRate,
        ]);
    }

    /**
     * Display the order builder step 2: base & quantity selection (review).
     */
    public function review(Creator $creator): Response|RedirectResponse
    {
        $this->authorize('viewAny', Store::class);

        $user = auth()->user();
        if ($user->account?->type !== AccountType::Store || ! $user->account->store) {
            return redirect()->route('store.home');
        }

        $store = $user->account->store;
        $this->authorize('viewCreatorOrders', [$store, $creator]);

        $pivot = $store->creators()->where('creator_id', $creator->id)->first()?->pivot;
        $discountRate = $pivot?->discount_rate !== null ? (float) $pivot->discount_rate : 0;

        $colorwayIds = $this->parseColorwayIdsFromQuery();
        $draftOrderId = request()->query('draft');

        if ($draftOrderId) {
            $order = Order::with(['orderItems.colorway', 'orderItems.base'])
                ->where('id', $draftOrderId)
                ->where('orderable_type', Store::class)
                ->where('orderable_id', $store->id)
                ->where('account_id', $creator->account_id)
                ->first();

            $orderExistsElsewhere = $order === null
                && Order::where('id', $draftOrderId)->exists();
            if ($orderExistsElsewhere) {
                abort(403);
            }

            if ($order && $order->status === OrderStatus::Draft) {
                $colorwayIds = $order->orderItems
                    ->filter(fn (OrderItem $item) => $item->colorway?->status === ColorwayStatus::Active
                        && $item->base?->status === BaseStatus::Active)
                    ->pluck('colorway_id')
                    ->unique()
                    ->values()
                    ->all();

                $draftItems = $order->orderItems
                    ->filter(fn (OrderItem $item) => $item->colorway?->status === ColorwayStatus::Active
                        && $item->base?->status === BaseStatus::Active)
                    ->map(fn (OrderItem $item) => [
                        'colorway_id' => $item->colorway_id,
                        'base_id' => $item->base_id,
                        'quantity' => $item->quantity,
                    ])
                    ->values()
                    ->all();

                $draft = [
                    'order_id' => $order->id,
                    'notes' => $order->notes ?? '',
                    'items' => $draftItems,
                ];
            } elseif ($order && $order->status !== OrderStatus::Draft) {
                abort(403);
            } else {
                $draft = null;
            }
        } else {
            $draft = null;
        }

        if (empty($colorwayIds)) {
            return redirect()->route('store.creator.order.step1', ['creator' => $creator])
                ->with('error', 'Select colorways or resume a draft');
        }

        $colorways = Colorway::query()
            ->whereIn('id', $colorwayIds)
            ->where('account_id', $creator->account_id)
            ->where('status', ColorwayStatus::Active)
            ->with(['inventories.base', 'media'])
            ->get();

        $colorwaysData = $this->transformColorwaysForOrderStep2($colorways, $discountRate);

        $wholesaleTerms = [
            'discount_rate' => $pivot?->discount_rate !== null ? (float) $pivot->discount_rate : null,
            'minimum_order_quantity' => $pivot?->minimum_order_quantity !== null ? (int) $pivot->minimum_order_quantity : null,
            'minimum_order_value' => $pivot?->minimum_order_value !== null ? (float) $pivot->minimum_order_value : null,
            'allows_preorders' => (bool) ($pivot?->allows_preorders ?? false),
        ];

        return Inertia::render('store/orders/BaseQuantitySelectionPage', [
            'creator' => ['id' => $creator->id, 'name' => $creator->name],
            'colorways' => $colorwaysData,
            'wholesale_terms' => $wholesaleTerms,
            'draft' => $draft,
        ]);
    }

    /**
     * Save order as draft.
     */
    public function saveOrder(StoreOrderBuilderRequest $request, Creator $creator): RedirectResponse
    {
        $store = $request->user()->account->store;
        $this->authorize('viewCreatorOrders', [$store, $creator]);

        $pivot = $store->creators()->where('creator_id', $creator->id)->first()?->pivot;
        $discountRate = $pivot?->discount_rate !== null ? (float) $pivot->discount_rate : 0;

        $items = collect($request->input('items', []))->filter(fn ($item) => (int) ($item['quantity'] ?? 0) > 0)->all();

        $orderId = $request->input('order_id');
        if ($orderId) {
            $order = Order::query()
                ->where('id', $orderId)
                ->where('orderable_type', Store::class)
                ->where('orderable_id', $store->id)
                ->where('account_id', $creator->account_id)
                ->where('status', OrderStatus::Draft)
                ->first();

            if (! $order) {
                abort(403);
            }

            $order->update([
                'notes' => $request->input('notes'),
                'updated_by' => $request->user()->id,
            ]);

            $order->orderItems()->delete();
        } else {
            $order = Order::create([
                'type' => OrderType::Wholesale,
                'status' => OrderStatus::Draft,
                'account_id' => $creator->account_id,
                'order_date' => now(),
                'orderable_type' => Store::class,
                'orderable_id' => $store->id,
                'notes' => $request->input('notes'),
                'created_by' => $request->user()->id,
            ]);
        }

        $colorwayIds = array_unique(array_column($items, 'colorway_id'));
        $wholesalePriceMap = $this->buildWholesalePriceMap($creator->account_id, $colorwayIds, $discountRate);

        $lineTotals = [];
        foreach ($items as $item) {
            $colorwayId = (int) $item['colorway_id'];
            $baseId = (int) $item['base_id'];
            $quantity = (int) $item['quantity'];
            $unitPrice = $wholesalePriceMap[$colorwayId][$baseId] ?? 0;
            $lineTotal = round($unitPrice * $quantity, 2);

            $order->orderItems()->create([
                'colorway_id' => $colorwayId,
                'base_id' => $baseId,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
            ]);

            $lineTotals[] = $lineTotal;
        }

        $subtotal = round(array_sum($lineTotals), 2);
        $order->update([
            'subtotal_amount' => $subtotal,
            'total_amount' => $subtotal,
        ]);

        return redirect()->route('store.creator.orders', ['creator' => $creator])
            ->with('success', 'Order saved as draft')
            ->with('order_id', $order->id);
    }

    /**
     * Submit draft order.
     */
    public function submitOrder(StoreOrderBuilderRequest $request, Creator $creator): RedirectResponse
    {
        $store = $request->user()->account->store;
        $this->authorize('viewCreatorOrders', [$store, $creator]);

        $orderId = $request->input('order_id');
        $order = Order::query()
            ->where('id', $orderId)
            ->where('orderable_type', Store::class)
            ->where('orderable_id', $store->id)
            ->where('account_id', $creator->account_id)
            ->first();

        if (! $order) {
            abort(403);
        }

        if ($order->status !== OrderStatus::Draft) {
            throw ValidationException::withMessages([
                'order' => ['Order has already been submitted.'],
            ]);
        }

        return DB::transaction(function () use ($order, $store, $creator) {
            $order = Order::query()
                ->where('id', $order->id)
                ->lockForUpdate()
                ->first();

            if ($order->status !== OrderStatus::Draft) {
                throw ValidationException::withMessages([
                    'order' => ['Order has already been submitted.'],
                ]);
            }

            $pivot = $store->creators()->where('creator_id', $creator->id)->first()?->pivot;
            $minQuantity = $pivot?->minimum_order_quantity !== null ? (int) $pivot->minimum_order_quantity : null;
            $minValue = $pivot?->minimum_order_value !== null ? (float) $pivot->minimum_order_value : null;

            $totalSkeins = $order->orderItems()->sum('quantity');
            $totalAmount = (float) $order->orderItems()->sum('line_total');

            $errors = [];
            if ($minQuantity !== null && $totalSkeins < $minQuantity) {
                $errors['minimum_order_quantity'] = ["Minimum order quantity is {$minQuantity} skeins."];
            }
            if ($minValue !== null && $totalAmount < $minValue) {
                $errors['minimum_order_value'] = ["Minimum order value is \${$minValue}."];
            }
            if (! empty($errors)) {
                throw ValidationException::withMessages($errors);
            }

            $order->update([
                'status' => OrderStatus::Open,
                'order_date' => now(),
            ]);

            $order->load(['orderable', 'account.users', 'account.creator', 'orderItems.colorway', 'orderItems.base']);

            $storeEmail = WholesaleOrderMailHelper::getStoreEmail($order);
            if ($storeEmail !== null) {
                Mail::to($storeEmail)->queue(new WholesaleOrderConfirmationMail($order));
            }

            $creatorEmail = WholesaleOrderMailHelper::getCreatorEmail($order);
            if ($creatorEmail !== null) {
                Mail::to($creatorEmail)->queue(new WholesaleNewOrderNotificationMail($order));
            }

            return redirect()->route('store.orders.show', ['order' => $order->id])
                ->with('success', 'Order submitted successfully');
        });
    }

    /**
     * Parse comma-separated colorway IDs from query param.
     *
     * @return array<int, int>
     */
    private function parseColorwayIdsFromQuery(): array
    {
        $colorways = request()->query('colorways');

        if (! is_string($colorways) || trim($colorways) === '') {
            return [];
        }

        return array_values(array_filter(array_map('intval', explode(',', $colorways)), fn ($id) => $id > 0));
    }

    /**
     * @param  SupportCollection<int, Colorway>  $colorways
     * @return array<int, array<string, mixed>>
     */
    private function transformColorwaysForOrderStep2(SupportCollection $colorways, float $discountRate): array
    {
        $items = [];

        foreach ($colorways as $colorway) {
            $basesByBaseId = $colorway->inventories->groupBy('base_id');
            $bases = [];
            foreach ($basesByBaseId as $inventoriesForBase) {
                $inventory = $inventoriesForBase->first();
                $base = $inventory->base;
                if (! $base || $base->status !== BaseStatus::Active) {
                    continue;
                }
                $retailPrice = $base->retail_price !== null ? (float) $base->retail_price : 0;
                $wholesalePrice = round($retailPrice * (1 - $discountRate), 2);
                $bases[] = [
                    'id' => $base->id,
                    'descriptor' => $base->descriptor,
                    'weight' => $base->weight?->value,
                    'retail_price' => $retailPrice,
                    'wholesale_price' => $wholesalePrice,
                    'inventory_quantity' => $inventory->quantity,
                ];
            }

            $items[] = [
                'id' => $colorway->id,
                'name' => $colorway->name,
                'primary_image_url' => $colorway->primary_image_url,
                'bases' => $bases,
            ];
        }

        return $items;
    }

    /**
     * Build wholesale price map by colorway_id and base_id.
     *
     * @param  array<int, int>  $colorwayIds
     * @return array<int, array<int, float>>
     */
    private function buildWholesalePriceMap(int $accountId, array $colorwayIds, float $discountRate): array
    {
        $colorwayIds = array_values(array_filter($colorwayIds, fn ($id) => $id > 0));

        $inventories = Inventory::query()
            ->where('account_id', $accountId)
            ->whereIn('colorway_id', $colorwayIds)
            ->with('base')
            ->get();

        $map = [];
        foreach ($inventories as $inventory) {
            $base = $inventory->base;
            if (! $base) {
                continue;
            }
            $retailPrice = $base->retail_price !== null ? (float) $base->retail_price : 0;
            $map[$inventory->colorway_id][$inventory->base_id] = round($retailPrice * (1 - $discountRate), 2);
        }

        return $map;
    }

    /**
     * @param  SupportCollection<int, Colorway>  $colorways
     * @return array<int, array<string, mixed>>
     */
    private function transformColorwaysForOrderStep1(SupportCollection $colorways): array
    {
        $items = [];

        foreach ($colorways as $colorway) {
            $basesByBaseId = $colorway->inventories->groupBy('base_id');
            $bases = [];
            foreach ($basesByBaseId as $inventoriesForBase) {
                $inventory = $inventoriesForBase->first();
                $base = $inventory->base;
                if (! $base) {
                    continue;
                }
                $bases[] = [
                    'id' => $base->id,
                    'descriptor' => $base->descriptor,
                    'weight' => $base->weight?->value,
                    'retail_price' => $base->retail_price !== null ? (float) $base->retail_price : null,
                    'inventory_quantity' => $inventory->quantity,
                ];
            }

            $items[] = [
                'id' => $colorway->id,
                'name' => $colorway->name,
                'description' => $colorway->description,
                'status' => $colorway->status->value,
                'colors' => $colorway->colors?->map(fn ($c) => $c->value)->values()->all() ?? [],
                'primary_image_url' => $colorway->primary_image_url,
                'collections' => $colorway->collections->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])->all(),
                'bases' => $bases,
            ];
        }

        return $items;
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
     * @param  SupportCollection<int, \App\Models\Creator>  $creators
     * @return array<int, array<string, mixed>>
     */
    private function transformVendorsForIndex(SupportCollection $creators): array
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
     * @param  SupportCollection<int, \App\Models\Store>  $stores
     * @param  SupportCollection<int, \App\Models\Invite>  $invites
     * @return SupportCollection<int, array<string, mixed>>
     */
    private function mergeStoresAndInvites(SupportCollection $stores, SupportCollection $invites): SupportCollection
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
                'discount_rate' => $pivot?->discount_rate !== null ? (float) $pivot->discount_rate : null,
                'payment_terms' => $pivot?->payment_terms,
            ]);
        }

        return $items;
    }

    /**
     * @param  SupportCollection<int, \App\Models\Store>  $stores
     * @return array<int, array<string, mixed>>
     */
    private function transformStoresForIndex(SupportCollection $stores): array
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
                'discount_rate' => $pivot?->discount_rate !== null ? (float) $pivot->discount_rate : null,
                'payment_terms' => $pivot?->payment_terms,
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
        $user = auth()->user();
        if ($user->account?->type === AccountType::Creator && $user->account->creator) {
            $storeWithPivot = $user->account->creator->stores()->where('stores.id', $store->id)->first();
            if ($storeWithPivot === null) {
                abort(404);
            }
        }

        $this->authorize('view', $store);

        $orders = [];
        $ordersTruncated = false;

        if ($user->account?->type === AccountType::Creator && $user->account->creator) {
            $creator = $user->account->creator;
            $storeWithPivot = $creator->stores()->where('stores.id', $store->id)->first();
            $pivot = $storeWithPivot->pivot;
            $storeData = [
                'id' => $store->id,
                'name' => $store->name,
                'email' => $store->email,
                'owner_name' => $store->owner_name,
                'address_line1' => $store->address_line1,
                'address_line2' => $store->address_line2,
                'city' => $store->city,
                'state_region' => $store->state_region,
                'postal_code' => $store->postal_code,
                'country_code' => $store->country_code,
                'discount_rate' => $pivot->discount_rate !== null ? $this->discountRateForForm((float) $pivot->discount_rate) : null,
                'minimum_order_quantity' => $pivot->minimum_order_quantity !== null ? (int) $pivot->minimum_order_quantity : null,
                'minimum_order_value' => $pivot->minimum_order_value !== null ? (float) $pivot->minimum_order_value : null,
                'payment_terms' => $pivot->payment_terms,
                'lead_time_days' => $pivot->lead_time_days !== null ? (int) $pivot->lead_time_days : null,
                'allows_preorders' => (bool) $pivot->allows_preorders,
                'status' => $pivot->status ?? 'active',
                'notes' => $pivot->notes,
            ];

            $ordersQuery = Order::query()
                ->where('type', OrderType::Wholesale)
                ->where('account_id', $creator->account_id)
                ->where('orderable_type', Store::class)
                ->where('orderable_id', $store->id)
                ->with('orderItems')
                ->orderByDesc('order_date')
                ->limit(100);
            $ordersCollection = $ordersQuery->get();
            $ordersTruncated = $ordersCollection->count() === 100;
            $orders = $ordersCollection->map(fn (Order $order) => [
                'id' => $order->id,
                'order_date' => $order->order_date->toDateString(),
                'status' => $order->status->value,
                'total_amount' => $order->total_amount,
                'skein_count' => $order->orderItems->sum('quantity'),
            ])->all();
        } else {
            $storeData = array_merge($store->only([
                'id', 'name', 'email', 'owner_name', 'address_line1', 'address_line2',
                'city', 'state_region', 'postal_code', 'country_code',
            ]), [
                'discount_rate' => null,
                'minimum_order_quantity' => null,
                'minimum_order_value' => null,
                'payment_terms' => null,
                'lead_time_days' => null,
                'allows_preorders' => false,
                'status' => 'active',
                'notes' => null,
            ]);
        }

        return Inertia::render('creator/stores/StoreEditPage', [
            'store' => $storeData,
            'orders' => $orders,
            'ordersTruncated' => $ordersTruncated,
        ]);
    }

    /**
     * Update the creator-store relationship status (active, paused, ended).
     */
    public function updateStatus(UpdateStoreStatusRequest $request, Store $store): RedirectResponse
    {
        $user = $request->user();
        $creator = $user->account?->creator;
        if (! $creator) {
            abort(404);
        }

        $storeWithPivot = $creator->stores()->where('stores.id', $store->id)->first();
        if ($storeWithPivot === null) {
            abort(404);
        }

        $currentStatus = $storeWithPivot->pivot->status ?? 'active';
        if ($currentStatus === 'ended') {
            throw ValidationException::withMessages([
                'status' => ['Cannot change status from ended.'],
            ]);
        }

        $creator->stores()->updateExistingPivot($store->id, [
            'status' => $request->validated('status'),
        ]);

        return redirect()->route('stores.edit', $store);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateStoreRequest $request, Store $store): RedirectResponse
    {
        $user = $request->user();
        if ($user->account?->type === AccountType::Creator && $user->account->creator) {
            $validated = $request->validated();
            if (array_key_exists('discount_rate', $validated) && $validated['discount_rate'] !== null) {
                $validated['discount_rate'] = (float) $validated['discount_rate'] / 100;
            }
            $user->account->creator->stores()->updateExistingPivot($store->id, $validated);
        } else {
            $store->update($request->validated());
        }

        return redirect()->route('stores.index');
    }

    /**
     * Convert stored discount_rate (decimal 0–1 or legacy percentage 0–100) to form percentage 0–100.
     */
    private function discountRateForForm(float $stored): float
    {
        return $stored <= 1 ? $stored * 100 : $stored;
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
