<?php

namespace App\Http\Controllers;

use App\Enums\AccountType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BillingPortalController extends Controller
{
    /**
     * Redirect Creator to Stripe Customer Portal. Store/Buyer receive 403.
     */
    public function __invoke(Request $request): RedirectResponse|Response
    {
        $account = $request->user()?->account;

        if (! $account || $account->type !== AccountType::Creator) {
            abort(403, 'Billing is only available for Creator accounts.');
        }

        if (! $account->stripe_id) {
            abort(403, 'No billing account found.');
        }

        return $account->redirectToBillingPortal(route('user.edit'));
    }
}
