# Task 02 — ShopifyOrderSyncService

## Starting Prompt

> I'm working through the Shopify orders spec at `specs/shopify-orders/`. Please read `specs/shopify-orders/overview.md` and `specs/shopify-orders/tasks/02-order-sync-service.md`, then implement this task. Work through the checklist before marking done. Don't start Task 03.

---

## Goal

Build `ShopifyOrderSyncService` — the core service that fetches a single order from Shopify via GraphQL and upserts it into Fibermade as a retail `Order` with its `Customer`, `OrderItem` records, and `ExternalIdentifier` mappings.

---

## What to Build

### 1. `ShopifyOrderSyncService`

New file: `app/Services/Shopify/ShopifyOrderSyncService.php`

Constructor dependencies: `ShopifyGraphqlClient`

#### Method: `syncOrder(string $orderGid, Integration $integration): Order`

The main entry point. Fetches one order from Shopify by GID and upserts it into Fibermade.

Steps:
1. Run the single-order GraphQL query (see overview) against the integration's shop
2. Map the response to an `Order` (see field mapping in overview)
3. Upsert the `Customer` — see below
4. Upsert the `Order` — find by `ExternalIdentifier` or create new; always update fields
5. Upsert `OrderItem` records — see below
6. Store/update `ExternalIdentifier` for the order with `admin_url` and `tracking` in `data`
7. Return the order

#### Method: `resolveStatus(string $shopifyStatus, ?string $fulfillmentStatus): OrderStatus`

Maps combined Shopify status to Fibermade `OrderStatus` per the table in the overview.

#### Method: `resolvePaymentStatus(string $financialStatus): PaymentStatus`

Maps Shopify `financial_status` to Fibermade `PaymentStatus` per the table in the overview.

---

### 2. Customer upsert logic

When an order has a `customer` in the Shopify response:

1. Look up `ExternalIdentifier` where `integration_id = $integration->id`, `external_type = 'shopify_customer'`, `external_id = $customer['id']`
2. If found, load the associated `Customer` model and update name/email/phone
3. If not found, create a new `Customer` scoped to `$integration->account_id`; create `ExternalIdentifier` mapping
4. Set the order's `orderable` to this Customer

When the Shopify order has no customer (guest checkout), leave `orderable` null.

**Customer fields to populate:**

| Fibermade `Customer` field | Shopify source |
|---|---|
| `account_id` | `$integration->account_id` |
| `name` | `firstName + ' ' + lastName` |
| `email` | `email` |
| `phone` | `phone` |

Check the existing `Customer` model and factory for the correct fillable fields before writing.

---

### 3. OrderItem upsert logic

For each line item in the Shopify response:

1. Try to find a matching `Colorway` via `ExternalIdentifier` where `external_type = 'shopify_product'` and `external_id = $lineItem['product']['id']`
2. Try to find a matching `Base` via `ExternalIdentifier` where `external_type = 'shopify_variant'` and `external_id = $lineItem['variant']['id']`
3. Upsert `OrderItem` keyed on `order_id` + the Shopify line item ID (store line item GID in a new `external_id` column — see migration note below)

**OrderItem fields to populate:**

| Field | Source |
|---|---|
| `order_id` | the upserted order |
| `colorway_id` | matched Colorway id — null if no match |
| `base_id` | matched Base id — null if no match |
| `quantity` | `quantity` |
| `unit_price` | `originalUnitPriceSet.shopMoney.amount` |
| `title` | `title` |
| `variant_title` | `variantTitle` |

> **Migration note:** To upsert line items idempotently, add an `external_id` nullable string column to `order_items` in this task. This stores the Shopify line item GID and is used as the upsert key.

---

### 4. ExternalIdentifier for order

After upserting the order, create or update its `ExternalIdentifier`:

```php
ExternalIdentifier::updateOrCreate(
    [
        'integration_id'    => $integration->id,
        'identifiable_type' => Order::class,
        'identifiable_id'   => $order->id,
        'external_type'     => 'shopify_order',
    ],
    [
        'external_id' => $orderGid,
        'data'        => [
            'admin_url' => $this->buildAdminUrl($integration, $orderGid),
            'tracking'  => $this->extractTracking($shopifyOrder),
        ],
    ]
);
```

#### `buildAdminUrl(Integration $integration, string $gid): string`

Extract the numeric ID from the GID (`gid://shopify/Order/12345` → `12345`) and build:
`https://{shop}/admin/orders/{numericId}`

#### `extractTracking(array $shopifyOrder): array`

Flatten tracking info from all fulfillments into an array:
```php
[
    ['carrier' => '...', 'number' => '...', 'url' => '...'],
    ...
]
```

---

## Additional Migration (in this task)

Add `external_id` to `order_items`:

```php
$table->string('external_id')->nullable()->after('variant_title');
```

Used as the upsert key for Shopify line items.

---

## Files to Touch

| File | Change |
|---|---|
| `app/Services/Shopify/ShopifyOrderSyncService.php` | New service |
| `database/migrations/xxxx_add_external_id_to_order_items_table.php` | New migration |

---

## Tests

- `syncOrder()` creates a new `Order` with correct type, status, payment status, and amounts
- `syncOrder()` updates an existing `Order` on re-sync (idempotent)
- `syncOrder()` creates a new `Customer` and `ExternalIdentifier` for an unknown customer
- `syncOrder()` reuses an existing `Customer` when `ExternalIdentifier` match found
- `syncOrder()` sets `orderable` to null for guest checkout (no customer in response)
- `syncOrder()` creates `OrderItem` records with null `colorway_id`/`base_id` when no Fibermade match
- `syncOrder()` links `colorway_id` and `base_id` when `ExternalIdentifier` matches exist
- `syncOrder()` stores `admin_url` and `tracking` in `ExternalIdentifier.data`
- `resolveStatus()` maps all Shopify status combinations correctly
- `resolvePaymentStatus()` maps all Shopify financial statuses correctly
- `buildAdminUrl()` extracts numeric ID from GID correctly
- `extractTracking()` flattens tracking across multiple fulfillments

---

## Checklist

- [ ] Read `app/Services/Shopify/ShopifyGraphqlClient.php` to understand how to make requests
- [ ] Read `app/Models/Customer.php` and `app/Models/OrderItem.php` for correct fields
- [ ] Read `app/Models/ExternalIdentifier.php` for correct usage pattern
- [ ] Create migration: `external_id` on `order_items`
- [ ] Run migration
- [ ] Build `ShopifyOrderSyncService` with all methods above
- [ ] Write tests — mock `ShopifyGraphqlClient`, use factories for supporting models
- [ ] Run `php artisan test --compact` — all passing
- [ ] Run `vendor/bin/pint --dirty`
