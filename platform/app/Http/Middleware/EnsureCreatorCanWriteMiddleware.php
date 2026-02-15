<?php

namespace App\Http\Middleware;

use App\Enums\SubscriptionStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCreatorCanWriteMiddleware
{
    /**
     * Block create/update/delete for inactive Creator accounts (read-only during retention).
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $account = $request->user()?->account;

        if (! $account || $account->subscription_status !== SubscriptionStatus::Inactive) {
            return $next($request);
        }

        if ($request->isMethod('GET') || $request->isMethod('HEAD')) {
            return $next($request);
        }

        abort(403, 'Your subscription has ended. Reactivate to make changes.');
    }
}
