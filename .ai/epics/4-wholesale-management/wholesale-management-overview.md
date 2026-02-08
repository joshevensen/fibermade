# Epic 4: Wholesale Management (Creator-Facing)

## Goal

Build the creator-facing wholesale order management. End state: a creator can view incoming wholesale orders from stores, process them through a workflow (accept → fulfill → complete), and manage their store relationships.

## Current State

- **Epic 3 complete.** Stores can browse catalogs, build orders, submit them. Orders are created with `type: wholesale`, `status: open`, and linked to a Store via the polymorphic `orderable` relationship.
- **Creator routes exist** at `/creator/` with `auth` + `verified` middleware. Existing pages include dashboard, colorways, bases, collections, inventory, dyes, stores, invites, orders, shows.
- **Order management is partially built.** `OrderController` has index/show/edit/destroy actions. The web routes exist but write operations (store, update, delete) are disabled via `OrderPolicy` -- those were re-enabled in Epic 0 Story 0.6 for the API. The web-side policy was re-enabled there too.
- **Store management exists.** `StoreController::index()` shows stores and pending invites. Creators can invite stores via `InviteController`. The `creator_store` pivot stores wholesale terms (discount_rate, minimums, payment_terms, lead_time_days, etc.).
- **Order index page exists** but likely needs enhancement for wholesale-specific features (filtering by type, status actions, store info).
- **What's missing:** Order processing workflow (accept/fulfill/complete), wholesale-specific order views, enhanced store relationship management.

## What This Epic Delivers

By the end of this epic:
- Creators see incoming wholesale orders in their orders dashboard
- Creators can process orders through a workflow: open → accepted → fulfilled → closed
- Creators can view order details with store info, line items, and totals
- Creators can manage store relationships (view active stores, manage terms, pause/end relationships)
- The wholesale order lifecycle is complete from both sides

## What This Epic Does NOT Do

- No inventory deduction when orders are fulfilled (manual for Stage 1)
- No packing slips or invoices
- No email notifications (that's Epic 6)
- No payment tracking beyond the order total
- No bulk order operations

## Stories

### Story 4.1: Wholesale Order Dashboard

Enhance the creator's order view to surface wholesale orders and their status.

- Filter orders by type: wholesale, retail, show (or all)
- Filter by status: open, accepted, fulfilled, closed, cancelled
- Each order row shows: order date, store name, item count, total amount, status
- Sort by most recent first
- Quick status indicator (color-coded badges)
- Note: the `OrderStatus` enum has draft, open, closed, cancelled. This epic may need to add `accepted` and `fulfilled` statuses, or use a different mechanism (e.g., a separate `fulfillment_status` field) to track the processing workflow without changing the core order status

### Story 4.2: Order Processing Workflow

Build the workflow actions that move an order through its lifecycle.

- Accept: creator acknowledges the order (open → accepted/in-progress)
- Fulfill: creator marks the order as shipped/complete (accepted → fulfilled)
- Close: order is done (fulfilled → closed)
- Cancel: creator or store can cancel an open order (any status → cancelled)
- Each action updates the order status and records who performed it (`updated_by`)
- Add notes when changing status (e.g., tracking number on fulfillment)
- Guard transitions: only valid status changes are allowed (can't go from cancelled to fulfilled)

### Story 4.3: Order Detail View

Enhanced order detail page for creators with wholesale-specific information.

- Store info: name, owner, contact, address
- Wholesale terms: discount rate, payment terms, lead time from the creator-store pivot
- Line items: colorway name, base descriptor, quantity, unit price, line total
- Order totals: subtotal, shipping, discount, tax, total
- Status history / timeline (if tracking status changes)
- Action buttons for the current workflow step (accept, fulfill, close, cancel)

### Story 4.4: Store Relationship Management

Enhance the existing store management for wholesale operations.

- View all active store relationships with their wholesale terms
- Edit wholesale terms per store: discount rate, minimum order quantity, minimum order value, payment terms, lead time, preorder settings
- Pause or end a store relationship (updates `status` on the creator-store pivot)
- View order history per store
- The invite flow already exists (`InviteController`) -- ensure it works smoothly for the wholesale context
