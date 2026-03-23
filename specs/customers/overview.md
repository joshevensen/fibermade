# Customers — Overview (Stub)

## Goal

Make customers a first-class managed entity in Fibermade. Creators can view all their customers (retail and wholesale), see full order history per customer, add notes, and edit contact details.

## Why This Spec Exists

The `shopify-orders` spec creates `Customer` records as a side effect of importing retail orders from Shopify. Those customers exist in the database but there's no usable UI for them yet. This spec surfaces them properly and re-enables the write operations that were intentionally deferred to Stage 2.

## Current State

- `Customer` model exists with full contact fields (`name`, `email`, `phone`, `address_*`, `notes`)
- `CustomerController` exists with all resource actions — write operations are currently disabled via `CustomerPolicy` (see Stage 1 TODO comment in `routes/creator.php`)
- `orders()` relationship already exists on `Customer` (polymorphic via `orderable`)
- `ExternalIdentifier` support already on `Customer` — Shopify customers will have a `shopify_customer` mapping
- Customer index and show pages likely exist but may be minimal

## Expected Scope (to be detailed)

**Customer list (index)**
- All customers scoped to the account
- Searchable by name/email
- Shows customer source (Shopify badge for retail customers, manual for wholesale)
- Order count and total spend at a glance

**Customer detail (show)**
- Full contact info with address
- Notes (editable)
- Full order history — both retail (from Shopify) and wholesale orders
- "View in Shopify" link for customers with a `shopify_customer` ExternalIdentifier

**Customer create/edit**
- Manual customer creation for wholesale customers
- Edit contact info
- Re-enable write routes currently blocked by `CustomerPolicy`

**No customer creation from Shopify**
- Shopify customers are created automatically via the `shopify-orders` sync service
- This spec only enables manual creation for non-Shopify customers

## Dependencies

- `shopify-orders` spec should be complete so Shopify-sourced customers already exist in the database before this UI is built

## Status

**Stub only** — tasks not yet written. Detail this spec when ready to implement.
