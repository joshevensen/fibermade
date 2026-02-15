## Context

Fibermade currently has no revenue model. Creator accounts need a subscription system to sustain platform development while Store/Buyer accounts remain free to encourage wholesale adoption.

**Current state:**
- Account model already has Laravel Cashier's `Billable` trait
- Account has `type` enum (Creator, Store, Buyer)
- Account has `status` enum (using `BaseStatus`)
- Authentication uses Laravel Fortify + Sanctum
- Registration flow creates accounts immediately

**Stakeholders:**
- Creators: Need affordable, simple subscription ($39/mo)
- Stores: Free access to place wholesale orders
- Platform: Needs revenue and clean subscription state management

**Constraints:**
- Laravel 12 + Cashier v16
- Must use Stripe Checkout (hosted) and Customer Portal (no-code)
- PCI compliance handled entirely by Stripe
- Only Creator accounts pay; Store/Buyer accounts free
- No admin panel (use Stripe Dashboard + database for management)

## Goals / Non-Goals

**Goals:**
- Creator accounts require active subscription for platform access
- Store/Buyer accounts unaffected (free access)
- Clean registration flow: no abandoned accounts in database
- Graceful payment failure handling with user-facing warning
- 90-day data retention for inactive accounts
- Automatic 30-day money-back refunds
- Webhook-driven state synchronization with Stripe
- Support discount codes via URL parameters

**Non-Goals:**
- Multiple pricing tiers (single $39/mo plan only)
- Admin UI for subscription management (use Stripe Dashboard)
- Team member billing or per-seat pricing
- Payment dunning emails (Stripe handles this)
- Custom payment flows or embedded Stripe Elements
- Store/Buyer subscription requirements
- Data export feature (most data in Shopify)

## Decisions

### 1. Account Creation After Payment (Not Before)

**Decision:** Create Creator accounts via `checkout.session.completed` webhook, not during registration form submission.

**Rationale:**
- Eliminates abandoned/pending accounts in database
- Single source of truth: Account exists = Payment succeeded
- Simpler state management (no "pending" status)
- Registration data stored in session, passed to Stripe as metadata

**Alternative considered:** Create account first, redirect to checkout
- ✗ Leaves abandoned accounts if user doesn't complete checkout
- ✗ Requires cleanup jobs for stale accounts
- ✗ More complex state machine (pending → active)

**Implementation:**
1. Registration form submits to `POST /register/checkout`
2. Backend validates, stores data in session
3. Creates Stripe Checkout Session with metadata: `{email, name, business_name, password_hash, promo_code}`
4. Returns checkout URL, frontend redirects
5. Webhook `checkout.session.completed` creates Account + User
6. Success URL shows "Account created!" message

**Session data structure:**
```php
session(['registration' => [
    'name' => $validated['name'],
    'email' => $validated['email'],
    'business_name' => $validated['business_name'],
    'password' => Hash::make($validated['password']),
    'promo_code' => $request->query('promo'),
]]);
```

**Checkout Session metadata:**
```php
Checkout\Session::create([
    'mode' => 'subscription',
    'metadata' => [
        'email' => $data['email'],
        'name' => $data['name'],
        'business_name' => $data['business_name'],
        'password_hash' => $data['password'],
        'promo_code' => $data['promo_code'],
    ],
    // ... line_items, etc.
]);
```

---

### 2. Subscription Status Enum

**Decision:** Add `subscription_status` enum to accounts table with 5 states.

**States:**
- `active`: Paid, full access
- `past_due`: Payment failed, grace period with banner
- `cancelled`: User cancelled, access until period ends
- `inactive`: Expired, read-only for 90 days before deletion
- `refunded`: Refunded within 30-day guarantee, account closed

**Rationale:**
- Explicit state machine easier to reason about than boolean flags
- Maps directly to Stripe subscription statuses
- Supports business requirements (grace periods, retention)

**Alternative considered:** Use Cashier's `subscribed()` method only
- ✗ Doesn't capture nuanced states (past_due vs cancelled vs inactive)
- ✗ Can't implement 90-day retention without custom state
- ✗ Harder to show appropriate UI for each state

**Migration:**
```php
Schema::table('accounts', function (Blueprint $table) {
    $table->enum('subscription_status', [
        'active', 
        'past_due', 
        'cancelled', 
        'inactive', 
        'refunded'
    ])->nullable();
});
```

---

### 3. Creator-Only Middleware

**Decision:** Subscription checks only apply to Creator accounts, not Store/Buyer.

**Rationale:**
- Business model: Creators pay, Stores/Buyers are free
- Stores need frictionless access to place wholesale orders
- Account type already tracked via `AccountType` enum

**Middleware logic:**
```php
// EnsureActiveSubscription middleware
public function handle(Request $request, Closure $next)
{
    $account = $request->user()->account;
    
    // Bypass for non-Creator accounts
    if ($account->type !== AccountType::Creator) {
        return $next($request);
    }
    
    // Check subscription status
    if (!in_array($account->subscription_status, ['active', 'past_due', 'cancelled'])) {
        return redirect()->route('subscription.expired');
    }
    
    return $next($request);
}
```

**Route application:**
```php
// Apply to Creator-specific routes only
Route::middleware(['auth', 'ensure.subscription'])->group(function () {
    Route::get('/creator/dashboard', ...);
    Route::resource('/colorways', ...);
    // etc.
});

// Store routes have no subscription check
Route::middleware(['auth'])->group(function () {
    Route::get('/store/catalog', ...);
    Route::post('/orders', ...);
});
```

---

### 4. Payment Failure Banner (Grace Period)

**Decision:** Show site-wide banner when `subscription_status === 'past_due'` with "Update Payment Method" button.

**Rationale:**
- Stripe retries failed payments automatically (~7-14 days)
- User needs clear call-to-action during retry period
- Maintains access during grace period (better UX than immediate lockout)

**Implementation:**
- Banner component in main layout (Creators only)
- Props: `account.subscription_status` shared via Inertia
- Button opens Stripe Customer Portal directly
- Non-dismissible (critical action required)

**Banner component location:**
```vue
<!-- In CreatorLayout.vue or similar -->
<div v-if="$page.props.account.subscription_status === 'past_due'" 
     class="bg-yellow-50 border-b border-yellow-200">
    <!-- Banner content with Update Payment Method button -->
</div>
```

---

### 5. Webhook Handlers & Idempotency

**Decision:** Handle 5 critical Stripe webhooks with idempotent processing.

**Critical webhooks:**
1. `checkout.session.completed` - Create account + user, set `active`
2. `invoice.payment_succeeded` - Confirm renewal, ensure `active`
3. `invoice.payment_failed` - Set `past_due`, schedule Stripe retries
4. `customer.subscription.deleted` - Set `cancelled` or `inactive`
5. `charge.refunded` - Check if within 30 days, set `refunded`, close account

**Idempotency strategy:**
```php
// Log all webhook events to database
WebhookEvent::create([
    'stripe_id' => $event->id,
    'type' => $event->type,
    'payload' => $event->data,
    'processed_at' => null,
]);

// Check if already processed
if (WebhookEvent::where('stripe_id', $event->id)->whereNotNull('processed_at')->exists()) {
    return; // Already handled
}

// Process event...

// Mark as processed
WebhookEvent::where('stripe_id', $event->id)->update(['processed_at' => now()]);
```

**Safety nets:**
- Stripe retries failed webhooks automatically
- Daily sync job: `php artisan subscriptions:sync` (compare Stripe → Database)
- Webhook signing verification (Stripe signing secret)
- Logging for debugging failed webhook processing

---

### 6. 90-Day Data Retention with Email Reminders

**Decision:** When subscription expires, set status to `inactive` and schedule 5 reminder emails before deletion on day 90.

**Email schedule:**
- Day 7: "Reactivate within 90 days to keep your data"
- Day 30: "60 days left to reactivate"
- Day 60: "30 days left before data deletion"
- Day 80: "10 days left! Reactivate now"
- Day 90: Hard delete account + related data

**Implementation:**
- Laravel scheduled job runs daily: `php artisan schedule:work`
- Check for accounts where `subscription_status === 'inactive'`
- Calculate days since subscription ended (via Cashier's `ends_at`)
- Send appropriate email based on day count
- On day 90: Hard delete account (or archive to cold storage)

**Access during retention:**
- User can log in
- Read-only access to data (colorways, orders, etc.)
- Cannot create new items or sync with Shopify
- Prominent banner: "Reactivate subscription" with countdown
- Button to restart subscription (new Stripe Checkout Session)

**Deletion scope:**
```php
// Day 90 cleanup
$account->colorways()->delete();
$account->bases()->delete();
$account->orders()->delete();
$account->integrations()->delete();
$account->users()->delete();
$account->delete(); // Soft delete (SoftDeletes trait)
```

---

### 7. Discount Code URL Pass-Through

**Decision:** Support `?promo=CODE` URL parameter in registration, pass to Stripe Checkout.

**Rationale:**
- Enables marketing campaigns (email links, QR codes at fiber festivals)
- Stripe validates coupon, no need for custom validation
- Hidden from UI (not obvious), but supported for flexibility
- Future-proof for affiliate/partner programs

**Implementation:**
```php
// In POST /register/checkout
$promoCode = $request->query('promo');

// Pass to Stripe Checkout Session
Checkout\Session::create([
    'discounts' => $promoCode ? [['coupon' => $promoCode]] : [],
    // ...
]);
```

**Example URLs:**
- `/register?promo=LAUNCH2026` - Early adopter discount
- `/register?promo=STITCHES50` - Fiber festival promotion
- `/register?promo=PARTNER25` - Partner referral discount

---

### 8. Welcome Email (Only Fibermade-Sent Email)

**Decision:** Send welcome email when account created via webhook. All payment-related emails handled by Stripe.

**Rationale:**
- Stripe sends payment receipts, renewal confirmations, failure notices
- Fibermade only sends: welcome email + 90-day retention reminders
- Avoid duplicate notifications
- Let Stripe handle dunning (payment retries)

**Welcome email triggers:**
- Sent in `checkout.session.completed` webhook handler
- After account + user created successfully
- Contains: login credentials, getting started tips, link to dashboard

**Retention emails:**
- Scheduled job (daily)
- Only for `inactive` accounts
- 5 emails over 90 days (see #6 above)

---

### 9. Testing with Stripe Test Mode

**Decision:** Use Stripe test mode for local/staging, live mode for production only.

**Test card numbers:**
```
Success: 4242 4242 4242 4242
Decline: 4000 0000 0000 0002
Requires authentication: 4000 0025 0000 3155
```

**Webhook testing:**
- Stripe CLI: `stripe listen --forward-to localhost:8000/webhooks/stripe`
- Trigger events: `stripe trigger checkout.session.completed`
- Logs webhook payloads for debugging

**Factory/Seeder:**
```php
// AccountFactory
$factory->state('subscribed', function () {
    return [
        'subscription_status' => 'active',
    ];
});

$factory->state('payment_failed', function () {
    return [
        'subscription_status' => 'past_due',
    ];
});

// Seeder creates Creator accounts in various states
Account::factory()
    ->creator()
    ->subscribed()
    ->create();
```

## Risks / Trade-offs

### Risk: Webhook failure during account creation
**Impact:** User pays but account not created
**Mitigation:** 
- Stripe retries webhooks automatically
- Manual sync command: `php artisan subscriptions:sync`
- Checkout success page polls: "Processing your account..." with fallback message
- Customer support can manually trigger account creation from Stripe Dashboard

### Risk: Session loss before checkout
**Impact:** Registration data lost if session expires/cleared
**Mitigation:**
- Session lifetime configured to 2 hours (Laravel default)
- User must re-enter form data (acceptable, rare case)
- Future: Store in Redis for longer TTL

### Risk: Race condition between webhook and user redirect
**Impact:** User redirected to success page before account created
**Mitigation:**
- Success page polls backend: "Is account ready?"
- Show spinner: "Setting up your account..."
- After 30 seconds, show message: "Taking longer than expected, check email"

### Trade-off: 90-day retention vs storage costs
**Impact:** Storing inactive accounts for 90 days increases database size
**Benefit:** Better customer goodwill, higher reactivation rate
**Mitigation:** Soft delete on day 90 (can be purged later if needed)

### Trade-off: No admin panel
**Impact:** Josh must use Stripe Dashboard + database queries to manage subscriptions
**Benefit:** Faster initial implementation, Stripe Dashboard is robust
**Future:** Build admin panel when scale requires it (Stage 2+)

### Risk: Duplicate account creation (same email)
**Impact:** User starts checkout twice, creates two accounts
**Mitigation:**
- Email uniqueness constraint in database (will fail on second webhook)
- Webhook handler checks if account exists before creating
- Stripe deduplicates customers by email automatically

### Trade-off: Payment failure grace period
**Impact:** Users can access platform for ~7-14 days without paying
**Benefit:** Better UX, Stripe retries increase successful payment rate
**Acceptable:** Industry standard, revenue loss is minimal vs. churn from immediate lockout
