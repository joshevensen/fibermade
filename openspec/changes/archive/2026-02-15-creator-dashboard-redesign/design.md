## Context

The creator dashboard currently displays production-focused data (dye list, revenue) but lacks wholesale order visibility. Creators spend time manually checking order status across multiple pages. This redesign shifts the dashboard focus from production to wholesale order management and store relationships.

**Current State:**
- DashboardPage.vue renders DyeListCard, RevenueThisMonthCard, and OpenOrdersCard
- DashboardController provides: dyeList, upcomingShows, openOrders, revenueThisMonth
- Layout: 2-column grid (2:1 ratio) with dye list in main column

**Constraints:**
- Must work within existing Inertia + Vue 3 architecture
- Must follow existing component patterns in `resources/js/components/ui/`
- Should avoid N+1 queries on dashboard load
- Dashboard must remain fast (<500ms load time)

## Goals / Non-Goals

**Goals:**
- Surface actionable wholesale insights on dashboard
- Display aggregate statistics with navigation shortcuts
- Show active wholesale orders grouped by status
- Highlight pending actions (orders to accept, pending store invites)
- Maintain clean, scannable UI with clear visual hierarchy

**Non-Goals:**
- Not replacing the full wholesale orders management page
- Not adding order editing capabilities to dashboard
- Not including historical/archived orders
- Not replacing production planning views (dye list may return in separate area)
- Not adding filtering or search on dashboard (use dedicated pages)

## Decisions

### Decision 1: Stats Row with Direct Navigation Links

**Rationale:** Creators need quick access to catalog management pages. Stats provide context (scale of catalog/relationships) and serve as navigation shortcuts.

**Implementation:**
- Create StatsCard component with three stat items
- Each stat displays: label, count, icon
- Entire card is clickable/hoverable, linking to respective index page
- Use Inertia `<Link>` for client-side navigation
- Stats: Colorways, Collections, Stores

**Route Targets:**
- Colorways → `/creator/colorways`
- Collections → `/creator/collections`
- Stores → `/creator/store-relationships` (or existing stores route)

**Backend:**
- Simple count queries in DashboardController:
  - `$user->colorways()->count()`
  - `$user->collections()->count()`
  - `Store::whereHas('relationship', fn($q) => $q->where('creator_id', $user->id))->count()`

**Alternatives Considered:**
- **Plain stats without links**: Rejected - misses opportunity for quick navigation
- **Dropdown menus from each stat**: Rejected - adds complexity, violates simplicity goal

### Decision 2: Wholesale Orders Summary Grouped by Status

**Rationale:** Creators need to see order pipeline at a glance. Grouping by status (pending, accepted, fulfilled, delivered) provides clear view of where orders are in workflow.

**Implementation:**
- Create WholesaleOrdersSummaryCard component
- Display orders in status-based sections
- Each order shows: store name, order number, total amount, order date
- Limit to active orders only (exclude cancelled/completed beyond delivery)
- Link to full order detail page on click

**Status Groups:**
- **Pending**: Orders awaiting creator acceptance
- **Accepted**: Orders accepted but not yet fulfilled
- **Fulfilled**: Orders fulfilled but not yet delivered
- **Delivered**: Recently delivered orders (last 30 days)

**Backend Query Structure:**
```php
$activeOrders = Order::query()
    ->where('creator_id', $user->id)
    ->whereIn('status', ['pending', 'accepted', 'fulfilled', 'delivered'])
    ->where(fn($q) => $q->where('status', '!=', 'delivered')
        ->orWhere('delivered_at', '>=', now()->subDays(30)))
    ->with(['store', 'orderable'])
    ->orderBy('created_at', 'desc')
    ->get()
    ->groupBy('status');
```

**Alternatives Considered:**
- **Table format**: Rejected - less scannable, harder to see status distribution
- **Kanban-style columns**: Rejected - overkill for dashboard, better suited to full order management page
- **Include cancelled orders**: Rejected - adds noise, not actionable

### Decision 3: "Needs Attention" Sidebar

**Rationale:** Creators need to know what requires immediate action without scanning entire order list.

**Implementation:**
- Create NeedsAttentionCard component in sidebar
- Two sections:
  1. **Pending Orders**: Orders awaiting acceptance (count + link to filtered order page)
  2. **Pending Store Invites**: Stores with pending invites (count + link to relationships page)
- Show badge with count on each section
- Empty state: "Nothing needs attention" with checkmark icon

**Backend Data:**
```php
$needsAttention = [
    'pending_orders' => Order::where('creator_id', $user->id)
        ->where('status', 'pending')
        ->count(),
    'pending_store_invites' => StoreRelationship::where('creator_id', $user->id)
        ->where('status', 'invited')
        ->count(),
];
```

**Alternatives Considered:**
- **Inline notifications**: Rejected - competes with order summary, less focused
- **Dismissible items**: Rejected - adds state management complexity for little benefit

### Decision 4: Layout Structure

**Rationale:** Two-column layout balances primary content (orders) with secondary/actionable content (needs attention).

**Layout:**
```
┌─────────────────────────────────────────────────────┐
│  Stats Row (3 equal cards)                          │
├─────────────────────────────────────┬───────────────┤
│  Wholesale Orders Summary           │  Needs        │
│  (grouped by status)                │  Attention    │
│                                     │  Card         │
│  Main Column (2/3)                  │  Sidebar (1/3)│
└─────────────────────────────────────┴───────────────┘
```

**Responsive Behavior:**
- Mobile: Stack to single column (stats → needs attention → orders)
- Tablet: Maintain two-column layout
- Desktop: Two-column with 2:1 ratio

### Decision 5: Remove Existing Dashboard Components

**Rationale:** Dye list and revenue cards don't align with wholesale-focused dashboard goals.

**Migration:**
- **DyeListCard**: Remove from dashboard. Production planning will be addressed in separate feature.
- **RevenueThisMonthCard**: Remove from dashboard. Financial reporting will be addressed in separate feature.
- **OpenOrdersCard**: Replace with new WholesaleOrdersSummaryCard

**Files to Remove/Archive:**
- `DyeListCard.vue` - Delete (specific to old dashboard)
- `RevenueThisMonthCard.vue` - Delete (specific to old dashboard)
- `OpenOrdersCard.vue` - Replace with WholesaleOrdersSummaryCard

### Decision 6: Component Reuse Strategy

**Rationale:** Leverage existing UI components for consistency and speed.

**Reuse:**
- `Card` component from `@/components/ui/card/Card.vue`
- `Link` from `@inertiajs/vue3`
- Badge/count indicators from existing UI library
- Icons from current icon system (likely Heroicons)

**New Components:**
- `StatsCard.vue` - Reusable stat display with link
- `WholesaleOrdersSummaryCard.vue` - Order status summary
- `NeedsAttentionCard.vue` - Action items sidebar

## Risks / Trade-offs

**[Risk] Dashboard query performance degrades with many orders**
→ **Mitigation:** 
  - Limit delivered orders to last 30 days
  - Add database indexes on `creator_id` + `status`
  - Use eager loading for relationships (`with(['store', 'orderable'])`)
  - Consider caching order counts if needed (Redis)

**[Risk] Order status changes don't reflect immediately**
→ **Mitigation:**
  - Use Inertia's automatic revalidation on navigation
  - Consider adding manual refresh button if users report stale data
  - Polling/real-time updates out of scope for MVP

**[Risk] Removing dye list disrupts production workflows**
→ **Mitigation:**
  - Document that production planning features are out of scope for this change
  - Plan separate production dashboard/page if needed
  - This change focuses on wholesale order management

**[Risk] Stats row adds extra queries on every page load**
→ **Mitigation:**
  - Count queries are fast with proper indexes
  - Consider adding cache layer if counts exceed 1000s
  - Monitor query performance in production

**[Trade-off] Dashboard is wholesale-only, not multi-purpose**
→ **Accepted:** This change intentionally narrows dashboard focus. Production, financial, and other views will be separate pages.

**[Trade-off] No filtering or search on dashboard**
→ **Accepted:** Dashboard is overview only. Full order management page provides filtering/search.
