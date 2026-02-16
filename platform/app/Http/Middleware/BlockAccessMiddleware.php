<?php

namespace App\Http\Middleware;

use App\Models\BlockedAccess;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Response;

class BlockAccessMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $blocklist = $this->getBlocklist();
        } catch (\Throwable) {
            return $next($request);
        }

        if ($this->isBlocked($request, $blocklist)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return $next($request);
    }

    /**
     * Get the blocklist from cache or database.
     *
     * @return array{ip: array<string>, ip_range: array<string>, user_agent: array<string>}
     */
    protected function getBlocklist(): array
    {
        return Cache::remember('blocklist:all', 60, function () {
            return BlockedAccess::active()
                ->get()
                ->groupBy('type')
                ->map(fn ($group) => $group->pluck('value')->all())
                ->all();
        });
    }

    /**
     * Check if the request should be blocked.
     *
     * @param  array{ip: array<string>, ip_range: array<string>, user_agent: array<string>}  $blocklist
     */
    protected function isBlocked(Request $request, array $blocklist): bool
    {
        $clientIp = $request->ip();
        $userAgent = $request->userAgent();

        // Check individual IP addresses
        if (isset($blocklist['ip']) && in_array($clientIp, $blocklist['ip'], true)) {
            return true;
        }

        // Check CIDR ranges
        if (isset($blocklist['ip_range'])) {
            foreach ($blocklist['ip_range'] as $range) {
                if (IpUtils::checkIp($clientIp, $range)) {
                    return true;
                }
            }
        }

        // Check user agent patterns (case-insensitive substring match)
        if (isset($blocklist['user_agent']) && $userAgent !== null) {
            foreach ($blocklist['user_agent'] as $pattern) {
                if (str_contains(strtolower($userAgent), strtolower($pattern))) {
                    return true;
                }
            }
        }

        return false;
    }
}
