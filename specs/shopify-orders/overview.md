# Shopify Orders — Overview

## Goal

Pull retail orders from Shopify into Fibermade so creators can see all their orders — wholesale and retail — in one place. Retail orders are read-only in Fibermade; creators click through to Shopify to manage fulfillment, issue refunds, or print shipping labels.

This spec also introduces a `PaymentStatus` field on all orders (paid, unpaid, partially refunded, refunded) to track payment state independently from fulfillment state.

---

## Scope

**In scope:**
- Pull last 60 days of Shopify orders on initial sync
- Keep orders current via webhooks (`orders/create`, `orders/updated`, `orders/cancelled`)
- Manual "Sync Orders" button on the Shopify settings page as a fallback
- Create or match a `Customer` record for each order's buyer
- Store line items, linking to Fibermade `Colorway` + `Base` where a match exists via `ExternalIdentifier`
- Display tracking info (carrier, number, URL) from Shopify fulfillments
- Link to the Shopify admin order page

**Out of scope:**
- Inventory deduction when a retail order is placed — this is covered in the forthcoming `shopify-inventory` spec (two-way inventory webhook sync)
- Printing or creating shipping labels (Shopify's label API is REST-only and tied to Shopify Shipping — not exposed via GraphQL)
- Creating or editing retail orders in Fibermade

---

## Current State

- `Order` model exists with `type` (`OrderType::Retail` already defined), `status`, and `orderable` polymorphic relationship
- `Customer` model and `CustomerController` exist
- `ExternalIdentifier` maps internal models to Shopify GIDs — used here for orders and customers
- Webhook infrastructure exists (see `shopify-v2` spec)
- Shopify settings page exists (`ShopifySyncController`)

**What's missing:**
- `payment_status` column and `PaymentStatus` enum
- `ShopifyOrderSyncService` — GraphQL fetch + upsert logic
- Webhook handlers for order topics
- `SyncAllShopifyOrdersJob`
- UI additions: payment status badge, "View in Shopify" link, tracking section

---

## Architecture

```
[Shopify]
    │
    ├── Webhook: orders/create  ─────────────────> SyncShopifyOrderJob(order_gid)
    ├── Webhook: orders/updated ─────────────────>      │
    ├── Webhook: orders/cancelled ───────────────>      │
    │                                                    ▼
    │                                         ShopifyOrderSyncService
    │                                              │
    │                                              ├── Upsert Order (type: retail)
    │                                              ├── Upsert Customer
    │                                              ├── Upsert OrderItems
    │                                              └── ExternalIdentifier mappings
    │
    └── Manual trigger (settings page button)
            │
            ▼
        SyncAllShopifyOrdersJob
            │
            └── Paginate orders query (last 60 days)
                    └── ShopifyOrderSyncService (per order)
```

---

## Data Model Changes

### New: `PaymentStatus` enum

```
paid | unpaid | partially_refunded | refunded
```

Added as a new column `payment_status` on the `orders` table. Nullable — wholesale/show orders can set it manually in future; retail orders always have it populated from Shopify.

### Shopify status mapping

**`OrderStatus`** (combined from Shopify `status` + `fulfillment_status`):

| Shopify `status` | Shopify `fulfillment_status` | Fibermade `OrderStatus` |
|---|---|---|
| `open` | `null` (unfulfilled) | `Open` |
| `open` | `partial` | `Open` |
| `open` | `fulfilled` | `Fulfilled` |
| `closed` | any | `Delivered` |
| `cancelled` | — | `Cancelled` |

**`PaymentStatus`** (from Shopify `financial_status`):

| Shopify `financial_status` | Fibermade `PaymentStatus` |
|---|---|
| `pending`, `authorized`, `voided`, `partially_paid` | `Unpaid` |
| `paid` | `Paid` |
| `partially_refunded` | `PartiallyRefunded` |
| `refunded` | `Refunded` |

### ExternalIdentifier types

| Model | `external_type` |
|-------|----------------|
| Order | `shopify_order` |
| Customer | `shopify_customer` |

### Order fields populated from Shopify

| Fibermade field | Shopify source |
|---|---|
| `type` | hardcoded `OrderType::Retail` |
| `status` | derived from `status` + `fulfillmentStatus` (see mapping above) |
| `payment_status` | derived from `financialStatus` |
| `order_date` | `processedAt` |
| `subtotal_amount` | `currentSubtotalPriceSet.shopMoney.amount` |
| `shipping_amount` | `totalShippingPriceSet.shopMoney.amount` |
| `discount_amount` | `currentTotalDiscountsSet.shopMoney.amount` |
| `tax_amount` | `currentTotalTaxSet.shopMoney.amount` |
| `total_amount` | `currentTotalPriceSet.shopMoney.amount` |
| `notes` | `note` |
| `cancelled_at` | `cancelledAt` |
| `orderable` | → `Customer` (polymorphic) |

### OrderItem fields populated from Shopify

| Fibermade field | Shopify source |
|---|---|
| `colorway_id` | matched via `ExternalIdentifier` on `product.id` — null if no match |
| `base_id` | matched via `ExternalIdentifier` on `variant.id` — null if no match |
| `quantity` | `quantity` |
| `unit_price` | `originalUnitPriceSet.shopMoney.amount` |
| `title` | `title` (stored for display when no Fibermade match) |
| `variant_title` | `variantTitle` |

> `title` and `variant_title` require a migration to add these columns to `order_items`.

### Shopify admin URL

Stored in `ExternalIdentifier.data` JSON as `{ "admin_url": "https://store.myshopify.com/admin/orders/12345" }`. Constructed from `integration.settings.shop` + the numeric ID extracted from the GID.

### Tracking info

Stored in `ExternalIdentifier.data` JSON for the order as `{ "tracking": [{ "carrier": "...", "number": "...", "url": "..." }] }`. Updated on each webhook/sync.

---

## Shopify GraphQL Query

```graphql
query GetOrders($first: Int!, $after: String, $query: String) {
  orders(first: $first, after: $after, query: $query, sortKey: PROCESSED_AT) {
    pageInfo {
      hasNextPage
      endCursor
    }
    edges {
      node {
        id
        name
        status
        financialStatus
        fulfillmentStatus
        processedAt
        cancelledAt
        updatedAt
        note
        currentTotalPriceSet { shopMoney { amount currencyCode } }
        currentSubtotalPriceSet { shopMoney { amount } }
        currentTotalTaxSet { shopMoney { amount } }
        currentTotalDiscountsSet { shopMoney { amount } }
        totalShippingPriceSet { shopMoney { amount } }
        customer {
          id
          firstName
          lastName
          email
          phone
        }
        shippingAddress {
          firstName
          lastName
          address1
          address2
          city
          province
          zip
          country
          countryCode
        }
        lineItems(first: 50) {
          edges {
            node {
              id
              title
              variantTitle
              quantity
              originalUnitPriceSet { shopMoney { amount } }
              product { id }
              variant { id }
            }
          }
        }
        fulfillments {
          trackingInfo {
            carrier
            number
            url
          }
        }
      }
    }
  }
}
```

For single-order fetch (webhook handlers), use `order(id: $id)` with the same field set.

---

## Webhooks

Register three new topics on the existing webhook infrastructure:

| Topic | Handler | Action |
|---|---|---|
| `orders/create` | `HandleOrderCreatedWebhook` | Dispatch `SyncShopifyOrderJob` |
| `orders/updated` | `HandleOrderUpdatedWebhook` | Dispatch `SyncShopifyOrderJob` |
| `orders/cancelled` | `HandleOrderCancelledWebhook` | Dispatch `SyncShopifyOrderJob` |

All three dispatch the same job — the sync service handles upsert logic regardless of create/update/cancel.

---

## Inventory Note

Retail orders from Shopify reduce inventory in Shopify automatically. Because Fibermade is now the inventory source of truth (shopify-push spec), inventory in Fibermade will drift from Shopify until the `shopify-inventory` spec is implemented. That spec will handle two-way inventory webhook sync. No inventory logic belongs in this spec.

---

## Tasks

| # | Task | Builds On |
|---|---|---|
| 01 | PaymentStatus enum + orders/order_items migration | — |
| 02 | ShopifyOrderSyncService — GraphQL fetch + upsert | Task 01 |
| 03 | Webhook handlers + registration | Task 02 |
| 04 | SyncAllShopifyOrdersJob + settings page button | Task 02 |
| 05 | UI — payment status, Shopify link, tracking, retail read-only | Task 01 |

---

## How to Work Through This

Each task runs in a **separate chat session**. Paste the **Starting Prompt** from each task file into a fresh chat.

| Session | Task | Notes |
|---|---|---|
| 1 | Task 01 | Data model only — no service logic |
| 2 | Task 02 | Core sync service — self-contained |
| 3 | Task 03 | Webhooks — depends on Task 02 being deployed |
| 4 | Task 04 | Bulk sync job + UI button |
| 5 | Task 05 | UI polish — can start once Task 01 is in |
