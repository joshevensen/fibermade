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
- Stores can build a wholesale order (select colorways, then pick bases and quantities)
- Stores can submit an order
- Stores can view their order history and order details
- The full store-facing ordering flow works end-to-end

## What This Epic Does NOT Do

- No creator-facing order management (that's Epic 4)
- No order processing workflow (accept, fulfill, complete -- that's Epic 4)
- No email notifications for new orders (that's Epic 6)
- No payment processing or invoicing
- No store self-registration -- stores are invited by creators only

## Pages & UX Flow

### Store Home Page (`/store`)

Full-width list of Creator Cards -- one card per creator the store has a relationship with.

Each Creator Card shows:
- Creator name and basic info
- Order counts: draft orders, open orders, closed orders
- Two buttons: **View Orders** and **New Order**

"New Order" always starts a fresh order. Draft orders are resumed from the orders page.

### Orders Page (`/store/{creator}/orders`)

List of all orders for this store+creator. Each row shows:
- Order date
- Total amount
- Current status (draft, open, closed, cancelled)
- Skein count (total quantity across all line items)
- Colorway count (number of distinct colorways)
- **View Order** button (for non-draft orders) / **Continue Order** button (for drafts)

Filter by status.

### Order Detail Page (`/store/orders/{order}`)

Read-only view of a submitted order. Layout matches the order review page (step 2) but is not editable.

- Status displayed prominently at the top
- Full-width list of colorways, each with a horizontal row of bases showing quantities ordered and unit prices
- Pricing summary at the bottom: subtotal, shipping, discount, tax, total
- Order notes

### New Order -- Step 1: Colorway Selection (`/store/{creator}/order`)

Two-panel layout:
- **Left panel (2/3 width):** List of all active colorways from this creator. Filters at the top (collection, colors). Each colorway list item shows name, primary image, collection, and colors. Items are expandable for more detail. Click/toggle to select a colorway.
- **Right panel (1/3 width):** Simple running list of selected colorways. Acts as a sidebar summary of what's been picked so far.

**Continue** button at the bottom to proceed to step 2.

### New Order -- Step 2: Base & Quantity Selection

Full-width layout showing the selected colorways.

Each colorway is a list item that includes:
- Colorway info (name, image, etc.)
- Horizontal row of available bases for that colorway, each with:
  - Base descriptor and weight
  - Wholesale price (retail_price adjusted by the `discount_rate` from the creator-store pivot)
  - Number input for quantity
  - Small text under each input showing current inventory count
  - If `allows_preorders` is false and inventory is 0, grey out the input
  - If `allows_preorders` is true, allow any quantity regardless of inventory
- Option to remove the colorway from the order

At the bottom:
- **Order notes** textarea
- **Minimum order feedback:** progress indicator showing current totals vs. creator's `minimum_order_quantity` and `minimum_order_value` thresholds (e.g., "Minimum order: $200 -- you're at $150")
- **Pricing summary:** subtotal, shipping (0 for now), discount, tax, total
- **Back** button to return to colorway selection (step 1)
- **Save as Draft** button (saves without minimum validation)
- **Submit Order** button (validates minimums are met, changes status from draft to open)

## Stories

### Story 3.1: Store Home Page & Order List

Build the store home page with creator cards and the per-creator order list page.

- Rework the store home page (`/store`) to show creator cards with order counts (draft, active, completed) and "View Orders" / "New Order" buttons
- Create the orders page at `/store/{creator}/orders` with order list showing date, total, status, skein count, colorway count
- Filter by status
- Draft orders show a "Continue Order" button, others show "View Order"
- "View Order" links to the order detail page; "Continue Order" links to the order builder

### Story 3.2: Order Builder -- Colorway Selection (Step 1)

Build the first step of the order flow where stores browse and select colorways.

- Create the order page at `/store/{creator}/order`
- Left panel (2/3): list of active colorways with filters (collection, colors), expandable items showing name, primary image, collection, colors
- Right panel (1/3): sidebar showing selected colorways
- Wholesale pricing: display prices adjusted by the `discount_rate` from the creator-store pivot
- "Continue" button to proceed to step 2

### Story 3.3: Order Builder -- Base & Quantity Selection (Step 2)

Build the second step where stores choose bases and quantities for their selected colorways.

- Full-width list of selected colorways, each with horizontal base rows
- Each base shows: descriptor, weight, wholesale price, quantity input
- Inventory display: small text under each input showing available quantity
- Preorder handling: grey out inputs for zero-inventory bases unless `allows_preorders` is true
- Remove colorway option per list item
- Order notes textarea at the bottom
- Minimum order feedback: progress toward `minimum_order_quantity` and `minimum_order_value`
- Pricing summary: subtotal, shipping, discount, tax, total
- "Back" button to return to step 1
- "Save as Draft" button (no minimum validation, status stays draft)
- "Submit Order" button (validates minimums, sets status to open, sets order_date, type, orderable)
- After submission, redirect to order detail page

### Story 3.4: Order Detail Page

Build the read-only order detail view.

- Order detail page at `/store/orders/{order}`
- Status displayed prominently at top
- Same layout as step 2 but read-only: colorways with base rows showing quantities, unit prices, line totals
- Pricing summary: subtotal, shipping, discount, tax, total
- Order notes
- Status progression indicator (draft → open → closed or cancelled)
