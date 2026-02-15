**Sub-project**: platform

## Why

The current creator dashboard shows production-focused information (dye list) and revenue metrics, but lacks visibility into wholesale order activity and store relationships. Creators need a dashboard that surfaces actionable wholesale insights: pending orders to accept, active order status, and store relationship health.

## What Changes

- Remove DyeListCard and RevenueThisMonthCard components
- Add stats summary row showing:
  - Total # of Colorways (linked to colorway index)
  - Total # of Collections (linked to collection index)
  - Total # of Stores (linked to store relationships index)
- Add two-column layout below stats:
  - **Main column**: Active wholesale orders grouped by status (pending, accepted, fulfilled, delivered)
  - **Sidebar**: "Needs Attention" card showing:
    - New wholesale orders awaiting acceptance
    - Stores with pending invites
- Update DashboardPage controller to provide:
  - Aggregate stats (colorway count, collection count, store count)
  - Active wholesale orders grouped by status
  - Pending wholesale orders (needs attention)
  - Stores with pending invites (needs attention)

## Capabilities

### New Capabilities
- `creator-dashboard-stats`: Display aggregate statistics (colorways, collections, stores) with navigation links
- `wholesale-order-dashboard-summary`: View active wholesale orders grouped by status on the dashboard
- `dashboard-needs-attention`: Display actionable items requiring creator attention (pending orders, pending store invites)

### Modified Capabilities
<!-- No existing capabilities are having their requirements changed. This is a UI-only dashboard redesign. -->

## Impact

**Frontend:**
- `platform/resources/js/pages/creator/dashboard/DashboardPage.vue` - Complete redesign of layout and data display
- Remove or repurpose existing dashboard components:
  - `DyeListCard.vue`
  - `RevenueThisMonthCard.vue`
  - `OpenOrdersCard.vue`
- Create new dashboard components:
  - Stats row component for aggregate counts
  - Wholesale orders summary component
  - Needs attention sidebar component

**Backend:**
- `platform/app/Http/Controllers/Creator/DashboardController.php` - Update to provide new data:
  - Colorway, collection, and store counts
  - Active wholesale orders with status grouping
  - Pending orders and pending store invites
- May need new Eloquent queries for efficient data fetching
- Consider eager loading to prevent N+1 queries

**Related User Stories:**
- **WM-1**: Dashboard view of all open wholesale orders
- **WM-7**: Ability to filter/group orders by status
