## 1. Backend — Controller & Data Preparation

- [x] 1.1 Update DashboardController to add aggregate stats (colorway count, collection count, store count)
- [x] 1.2 Add query for active wholesale orders grouped by status (pending, accepted, fulfilled, delivered)
- [x] 1.3 Add query for pending wholesale orders count (needs attention)
- [x] 1.4 Add query for pending store invites count (needs attention)
- [x] 1.5 Add eager loading for order relationships (store, orderable) to prevent N+1 queries
- [x] 1.6 Filter delivered orders to last 30 days only
- [x] 1.7 Exclude cancelled orders from active orders query
- [x] 1.8 Remove dyeList and revenueThisMonth props from controller

## 2. Frontend — Dashboard Page Restructure

- [x] 2.1 Update DashboardPage.vue to remove DyeListCard and RevenueThisMonthCard imports
- [x] 2.2 Update DashboardPage.vue layout to new grid structure (stats row + two-column layout)
- [x] 2.3 Update Props interface to include new data: colorwayCount, collectionCount, storeCount, activeOrders, needsAttention
- [x] 2.4 Remove old props: dyeList, revenueThisMonth

## 3. Frontend — Stats Row Component

- [x] 3.1 Create StatsCard.vue component with stat display (label, count, icon)
- [x] 3.2 Add Inertia Link wrapper to make entire stat card clickable
- [x] 3.3 Add hover/focus states for stat cards
- [x] 3.4 Add responsive layout for stats row (horizontal on desktop, stack on mobile)
- [x] 3.5 Integrate StatsCard into DashboardPage.vue for colorways stat with link to `/creator/colorways`
- [x] 3.6 Integrate StatsCard into DashboardPage.vue for collections stat with link to `/creator/collections`
- [x] 3.7 Integrate StatsCard into DashboardPage.vue for stores stat with link to store relationships page
- [x] 3.8 Add appropriate icons for each stat type

## 4. Frontend — Wholesale Orders Summary Component

- [x] 4.1 Create WholesaleOrdersSummaryCard.vue component
- [x] 4.2 Add status group sections (Pending, Accepted, Fulfilled, Delivered)
- [x] 4.3 Display order details for each order (store name, order number, total amount, order date)
- [x] 4.4 Add Inertia Link for each order to navigate to order detail page
- [x] 4.5 Add empty state for status groups with no orders
- [x] 4.6 Add visual grouping/headers for each status section
- [x] 4.7 Integrate WholesaleOrdersSummaryCard into DashboardPage.vue main column

## 5. Frontend — Needs Attention Component

- [x] 5.1 Create NeedsAttentionCard.vue component for sidebar
- [x] 5.2 Add pending orders section with count badge
- [x] 5.3 Add pending store invites section with count badge
- [x] 5.4 Add Inertia Link from pending orders to filtered wholesale orders page
- [x] 5.5 Add Inertia Link from pending invites to store relationships page
- [x] 5.6 Add empty state when nothing needs attention ("Nothing needs attention" with positive visual)
- [x] 5.7 Add visual styling for urgency (badges, colors)
- [x] 5.8 Integrate NeedsAttentionCard into DashboardPage.vue sidebar column

## 6. Frontend — Component Cleanup

- [x] 6.1 Remove DyeListCard.vue component file
- [x] 6.2 Remove RevenueThisMonthCard.vue component file
- [x] 6.3 Remove or update OpenOrdersCard.vue (replaced by WholesaleOrdersSummaryCard)
- [x] 6.4 Update any imports or references to removed components

## 7. Routing & Navigation

- [x] 7.1 Verify colorway index route exists at `/creator/colorways`
- [x] 7.2 Verify collection index route exists at `/creator/collections`
- [x] 7.3 Verify store relationships route exists and is accessible
- [x] 7.4 Verify wholesale order detail route accepts order ID parameter
- [x] 7.5 Verify wholesale orders index supports filtering by status (for needs attention link)

## 8. Styling & UI Polish

- [x] 8.1 Add Tailwind classes for stats row responsive layout
- [x] 8.2 Add Tailwind classes for two-column main layout (2:1 ratio on desktop)
- [x] 8.3 Add hover/focus states for clickable elements
- [x] 8.4 Add spacing and visual hierarchy between dashboard sections
- [x] 8.5 Add dark mode support if other dashboard components support it
- [x] 8.6 Ensure mobile-responsive layout (stats stack, then needs attention, then orders)

## 9. Database Optimization

- [x] 9.1 Add database index on orders table: (creator_id, status)
- [x] 9.2 Add database index on orders table: delivered_at (for 30-day filter)
- [x] 9.3 Add database index on store_relationships table: (creator_id, status)
- [x] 9.4 Verify colorways table has index on creator_id
- [x] 9.5 Verify collections table has index on creator_id

## 10. Tests — Controller

- [x] 10.1 Write feature test: dashboard returns correct colorway count
- [x] 10.2 Write feature test: dashboard returns correct collection count
- [x] 10.3 Write feature test: dashboard returns correct store count
- [x] 10.4 Write feature test: dashboard returns active orders grouped by status
- [x] 10.5 Write feature test: dashboard excludes cancelled orders
- [x] 10.6 Write feature test: dashboard includes only delivered orders from last 30 days
- [x] 10.7 Write feature test: dashboard returns pending orders count in needsAttention
- [x] 10.8 Write feature test: dashboard returns pending store invites count in needsAttention
- [x] 10.9 Write feature test: dashboard eager loads order relationships (no N+1)

## 11. Tests — Component

- [ ] 11.1 Write component test: StatsCard renders count and label
- [ ] 11.2 Write component test: StatsCard links to correct route
- [ ] 11.3 Write component test: WholesaleOrdersSummaryCard groups orders by status
- [ ] 11.4 Write component test: WholesaleOrdersSummaryCard shows empty state for empty status
- [ ] 11.5 Write component test: NeedsAttentionCard shows pending counts
- [ ] 11.6 Write component test: NeedsAttentionCard shows empty state when no items
- [ ] 11.7 Write component test: NeedsAttentionCard links navigate correctly

## 12. Integration Testing

- [ ] 12.1 Manual test: Navigate to dashboard and verify stats display correctly
- [ ] 12.2 Manual test: Click each stat card and verify navigation to correct page
- [ ] 12.3 Manual test: Verify wholesale orders appear in correct status groups
- [ ] 12.4 Manual test: Click order and verify navigation to order detail
- [ ] 12.5 Manual test: Verify needs attention card shows correct counts
- [ ] 12.6 Manual test: Click needs attention items and verify filtered navigation
- [ ] 12.7 Manual test: Test mobile responsive layout
- [ ] 12.8 Manual test: Verify no N+1 queries using Laravel Debugbar or Telescope
- [ ] 12.9 Manual test: Verify dashboard load time is under 500ms
