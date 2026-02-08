# Epic 3: Wholesale Ordering (Store-Facing)

## Goal

Build the store-facing wholesale experience. End state: a store can log in to Fibermade, browse a creator's catalog with wholesale pricing, build an order, and submit it.

## Current State

- **Store model exists** with fields for name, email, owner_name, and full address. Has a many-to-many relationship with Creator via `creator_store` pivot table that includes wholesale terms: `discount_rate`, `minimum_order_quantity`, `minimum_order_value`, `payment_terms`, `lead_time_days`, `allows_preorders`, `status`, `notes`.
- **Invite system exists.** The Invite model supports multiple invite types including `store`. Has token generation, expiration, pending scopes. An `AcceptInvitePage.vue` form already handles store invite acceptance with store name, owner name, address, password, and terms.
- **Store routes exist** at `/store/` with `auth` + `verified` middleware. `StoreController` has a `home()` method that shows active creators with order status.
- **Store home page exists** (`HomePage.vue`) showing creators the store buys from, with status filtering, current order details, and "New Order" / "Order History" actions.
- **Order system exists.** Orders are polymorphic -- stores can have wholesale orders via `MorphMany`. OrderItems reference Colorway + Base with quantity and pricing.
- **Catalog is populated** (after Epics 0-2). Colorways, Bases, Collections, and Inventory records exist with data.
- **What's missing:** No catalog browsing page for stores, no order builder, no order submission flow, no order history page.

## What This Epic Delivers

By the end of this epic:
- Stores can browse a creator's catalog (Colorways and Bases with wholesale pricing)
- Stores can build a wholesale order (select items, set quantities)
- Stores can submit an order
- Stores can view their order history and order status
- The full store-facing ordering flow works end-to-end

## What This Epic Does NOT Do

- No creator-facing order management (that's Epic 4)
- No order processing workflow (accept, fulfill, complete -- that's Epic 4)
- No email notifications for new orders (that's Epic 6)
- No payment processing or invoicing
- No store self-registration -- stores are invited by creators only

## Stories

### Story 3.1: Store Catalog Browsing

Build a catalog page where stores can browse a creator's available Colorways and Bases.

- Create a catalog page at `/store/catalog/{creator}` or similar
- Show Colorways grouped by Collection (if collections exist) or as a flat list
- Each Colorway shows: name, primary image, technique, available Bases
- Each Base shows: descriptor, weight, wholesale price (retail_price adjusted by the `discount_rate` from the creator-store pivot)
- Show inventory availability (quantity > 0) for each Colorway + Base combination
- Filter by: collection, status (active only by default), base weight
- The creator-store relationship determines which creator's catalog the store sees

### Story 3.2: Order Builder

Build the order creation flow where stores select items, set quantities, and review before submitting.

- Add to order: store selects a Colorway + Base combination and sets a quantity
- Order builder UI: running list of selected items with quantities, unit prices, and line totals
- Auto-calculate subtotal as items are added/modified
- Validate against creator's wholesale terms: `minimum_order_quantity`, `minimum_order_value`
- Show validation messages if minimums aren't met (but don't block saving as draft)
- Support saving as draft (status: draft) so the store can come back to it
- The `StoreController::home()` already tracks "current order" (draft/open) per creator -- build on this

### Story 3.3: Order Submission

Submit the completed order to the creator.

- Submit changes order status from `draft` to `open`
- Validate minimums are met before allowing submission
- Set order metadata: `order_date`, `type: wholesale`, `orderable_type: Store`, `orderable_id: store.id`
- Calculate final totals: subtotal (sum of line totals), shipping (0 for now), discount, tax, total
- Confirmation screen before submission
- After submission, redirect to order detail or order history

### Story 3.4: Order History & Detail

Let stores view their past and current orders.

- Order history page at `/store/orders` showing all orders for the store across all creators
- Filter by: status (draft, open, closed, cancelled), creator
- Order detail page at `/store/orders/{order}` showing: order date, status, creator info, line items (colorway, base, quantity, unit_price, line_total), totals, notes
- Show order status progression (draft → open → closed or cancelled)
