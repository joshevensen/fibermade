# Shopify Inventory — Overview (Stub)

## Goal

Two-way inventory sync between Fibermade and Shopify. When a retail order is placed in Shopify, inventory in Fibermade is reduced. When inventory is updated in Fibermade, it is pushed to Shopify (already partially covered by the shopify-push spec).

## Why This Spec Exists

The shopify-orders spec intentionally excludes inventory deduction from retail orders. Fibermade is the source of truth for inventory, but Shopify retail sales reduce inventory in Shopify automatically. Without this spec, inventory will drift between the two systems whenever a retail sale occurs.

## Expected Scope (to be detailed)

- Webhook: `inventory_levels/update` — when Shopify inventory changes (e.g. from a sale), update the corresponding `Inventory` record in Fibermade
- Reconciliation: handle cases where a Fibermade push and a Shopify sale cross in flight
- Conflict resolution strategy: TBD — likely last-write-wins with a log entry

## Dependency

- `shopify-orders` spec should be complete before this spec is detailed, as the order import flow and `ExternalIdentifier` mappings for orders/line items will inform how we identify which inventory records to update.
- `shopify-push` spec (Task 04 — inventory auto-push) should be complete so the outbound sync is stable before adding inbound.

## Status

**Stub only** — tasks not yet written. Detail this spec when ready to implement.
