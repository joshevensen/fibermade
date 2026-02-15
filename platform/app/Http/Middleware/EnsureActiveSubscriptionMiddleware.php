<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveSubscriptionMiddleware
{
    /**
     * Allow Creator routes only when account has active, past_due, or cancelled subscription.
     * Store and Buyer accounts bypass the check.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        $account = $user->account;
        if (! $account) {
            return $next($request);
        }

        if (! $account->requiresSubscription()) {
            return $next($request);
        }

        if ($account->hasActiveSubscription()) {
            return $next($request);
        }

        if ($account->subscription_status === \App\Enums\SubscriptionStatus::Inactive) {
            return $next($request);
        }

        if ($request->routeIs('subscription.expired') || $request->routeIs('subscription.reactivate')) {
            return $next($request);
        }

        return redirect()->route('subscription.expired');
    }
}
