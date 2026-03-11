<?php

namespace App\Http\Responses;

use App\Enums\AccountType;
use App\Jobs\SyncAccountSubscriptionJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

class LoginResponse implements LoginResponseContract
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  Request  $request
     */
    public function toResponse($request): Response
    {
        $user = $request->user();
        $user->load('account');

        $account = $user->account;
        if ($account && $account->requiresSubscription() && $account->stripe_id) {
            SyncAccountSubscriptionJob::dispatch($account);
        }

        $home = $account?->type === AccountType::Store
            ? route('store.home')
            : route('dashboard');

        return $request->wantsJson()
            ? new JsonResponse(['two_factor' => false], 200)
            : redirect()->intended($home);
    }
}
