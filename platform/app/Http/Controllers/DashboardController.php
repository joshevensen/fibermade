<?php

namespace App\Http\Controllers;

use App\Enums\InviteType;
use App\Enums\OrderStatus;
use App\Models\Creator;
use App\Models\Invite;
use App\Models\Order;
use App\Models\Show;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the dashboard.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $routeName = $request->route()->getName();
        $page = $routeName === 'store.dashboard'
            ? 'store/dashboard/DashboardPage'
            : 'creator/dashboard/DashboardPage';

        if ($page === 'store/dashboard/DashboardPage') {
            return $this->renderStoreDashboard($user, $page);
        }

        return $this->renderCreatorDashboard($user, $page);
    }

    /**
     * @param  \App\Models\User  $user
     */
    private function renderStoreDashboard($user, string $page): Response
    {
        $openOrders = $user->is_admin
            ? Order::where('status', OrderStatus::Open)
                ->with(['orderItems.colorway', 'orderItems.base'])
                ->get()
            : ($user->account_id
                ? Order::where('status', OrderStatus::Open)
                    ->where('account_id', $user->account_id)
                    ->with(['orderItems.colorway', 'orderItems.base'])
                    ->get()
                : collect());

        $dyeList = $openOrders
            ->flatMap(fn ($order) => $order->orderItems)
            ->filter(fn ($item) => $item->colorway && $item->base)
            ->groupBy(fn ($item) => $item->colorway_id.'-'.$item->base_id)
            ->map(fn ($group) => [
                'colorway' => $group->first()->colorway,
                'base' => $group->first()->base,
                'quantity' => $group->sum('quantity'),
            ])
            ->sortBy(fn ($item) => $item['colorway']->name)
            ->values();

        $now = now();
        $ninetyDaysFromNow = $now->copy()->addDays(90);
        $upcomingShows = $user->is_admin
            ? Show::where('start_at', '>=', $now)
                ->where('start_at', '<=', $ninetyDaysFromNow)
                ->orderBy('start_at')
                ->get()
            : ($user->account_id
                ? Show::where('account_id', $user->account_id)
                    ->where('start_at', '>=', $now)
                    ->where('start_at', '<=', $ninetyDaysFromNow)
                    ->orderBy('start_at')
                    ->get()
                : collect());

        $openOrdersList = $user->is_admin
            ? Order::where('status', OrderStatus::Open)
                ->with('orderable')
                ->orderBy('order_date', 'desc')
                ->get()
            : ($user->account_id
                ? Order::where('status', OrderStatus::Open)
                    ->where('account_id', $user->account_id)
                    ->with('orderable')
                    ->orderBy('order_date', 'desc')
                    ->get()
                : collect());

        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();
        $revenueThisMonth = $user->is_admin
            ? Order::whereBetween('order_date', [$startOfMonth, $endOfMonth])
                ->whereNotNull('total_amount')
                ->sum('total_amount')
            : ($user->account_id
                ? Order::where('account_id', $user->account_id)
                    ->whereBetween('order_date', [$startOfMonth, $endOfMonth])
                    ->whereNotNull('total_amount')
                    ->sum('total_amount')
                : 0);

        return Inertia::render($page, [
            'dyeList' => $dyeList,
            'upcomingShows' => $upcomingShows,
            'openOrders' => $openOrdersList,
            'revenueThisMonth' => $revenueThisMonth,
        ]);
    }

    /**
     * @param  \App\Models\User  $user
     */
    private function renderCreatorDashboard($user, string $page): Response
    {
        $account = $user->account;
        $creator = $account?->creator;

        $colorwayCount = $account ? $account->colorways()->count() : 0;
        $collectionCount = $account ? $account->collections()->count() : 0;
        $storeCount = $creator ? $creator->stores()->wherePivot('status', 'active')->count() : 0;

        $activeOrdersQuery = Order::query()
            ->where('account_id', $account?->id ?? 0)
            ->whereIn('status', [
                OrderStatus::Open,
                OrderStatus::Accepted,
                OrderStatus::Fulfilled,
                OrderStatus::Delivered,
            ])
            ->where(fn ($q) => $q->where('status', '!=', OrderStatus::Delivered)
                ->orWhere('delivered_at', '>=', now()->subDays(30)))
            ->with(['orderable'])
            ->orderBy('created_at', 'desc')
            ->get();

        $activeOrders = $activeOrdersQuery->groupBy(fn (Order $o) => $o->status->value);

        $pendingOrdersCount = $account
            ? Order::where('account_id', $account->id)
                ->where('status', OrderStatus::Open)
                ->count()
            : 0;

        $pendingStoreInvitesCount = $creator
            ? Invite::pending()
                ->where('invite_type', InviteType::Store)
                ->where('inviter_type', Creator::class)
                ->where('inviter_id', $creator->id)
                ->count()
            : 0;

        $needsAttention = [
            'pending_orders' => $pendingOrdersCount,
            'pending_store_invites' => $pendingStoreInvitesCount,
        ];

        return Inertia::render($page, [
            'colorwayCount' => $colorwayCount,
            'collectionCount' => $collectionCount,
            'storeCount' => $storeCount,
            'activeOrders' => $activeOrders->map(fn ($orders) => $orders->values()->all())->all(),
            'needsAttention' => $needsAttention,
            'upcomingShows' => [],
        ]);
    }
}
