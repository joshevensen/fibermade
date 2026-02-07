<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
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

        // Fetch open orders with eager loaded relationships
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

        // Aggregate colorways from all open orders' orderItems
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

        // Fetch upcoming shows (next 90 days)
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

        // Fetch open orders with orderable relationship
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

        // Calculate revenue for current month
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

        $routeName = $request->route()->getName();
        $page = $routeName === 'store.dashboard'
            ? 'store/dashboard/DashboardPage'
            : 'creator/dashboard/DashboardPage';

        return Inertia::render($page, [
            'dyeList' => $dyeList,
            'upcomingShows' => $upcomingShows,
            'openOrders' => $openOrdersList,
            'revenueThisMonth' => $revenueThisMonth,
        ]);
    }
}
