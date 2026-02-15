# Fibermade Stage 1: Shopify App

## Purpose

Stage 1 delivers wholesale management capabilities to yarn dyers already using Shopify. It's a Shopify App that connects to the Fibermade SaaS platform, adding fiber-specific wholesale features without forcing users to abandon their existing retail infrastructure.

This stage validates the core wholesale workflows and fiber-specific catalog structure before asking users to migrate away from Shopify entirely.

---

## Core Problem

Hand dyers using Shopify face wholesale pain that generic ecommerce tools don't solve:

- **Scattered wholesale orders** — emails, PDFs, text messages with stores asking "what do you have available?"
- **Inventory confusion** — Shopify shows quantity available, but doesn't understand what's already committed to wholesale vs. what's free for retail
- **Manual coordination** — accepting orders without clear visibility into actual capacity
- **No wholesale catalog** — stores can't browse and order like retail customers can
- **Duplicate product management** — creating separate "wholesale" variants just to show different pricing

Shopify Plus offers B2B features, but costs $2,000+/month. Existing wholesale apps overlay discount rules but don't understand fiber-specific workflows or solve the catalog/inventory reservation problem.

---

## What Stage 1 Delivers

### Wholesale Catalog & Ordering

**Store-Facing Catalog:**
- Stores browse the creator's full catalog organized by colorway, base, and collection
- Wholesale pricing visible only to authenticated store accounts
- Pan-based inline quantity selection (respects pan constraints for small-batch dyeing)
- Draft orders saved and resumable
- Order submission creates wholesale order in Fibermade

**Fiber-Specific Structure:**
- Catalog managed in Fibermade using fiber terminology (colorways, bases, collections)
- No need to understand Shopify's product/variant model for wholesale
- Catalog syncs to Shopify for retail presence

### Store Relationship Management

**Per-Store Terms:**
- Wholesale discount (percentage or custom pricing)
- Minimum order requirements
- Lead time expectations
- Payment terms (net 15/30/60, or "check/Venmo on delivery")

**Order Lifecycle:**
- Pending → Accepted → Fulfilled → Delivered
- Dyer manages orders in Fibermade
- Stores see order status and history
- No payment processing (handled outside system via check/Venmo/etc)

**Store Dashboard:**
- View all stores and their terms
- See order history per store
- Manage active/paused relationships

### Smart Inventory Management

**Automatic Reservation:**
- When wholesale order is **accepted**: inventory reserves in Fibermade, decrements in Shopify immediately
- When wholesale order is **fulfilled**: moves from reserved to decremented in Fibermade (no Shopify change, already decremented)
- When wholesale order is **cancelled**: unreserves in Fibermade, increments back in Shopify

**Visibility:**
- Total inventory vs. available inventory (total - reserved)
- Dyers see what's committed vs. what's free to sell
- Prevents overselling to retail when wholesale is committed

**Dye-to-Order Support:**
- Option to accept orders without reserving inventory (for made-to-order)
- Dyer decides per-order whether to reserve existing stock

### Bi-Directional Shopify Sync

**Fibermade → Shopify:**
- Product/variant creation when new colorways added
- Inventory updates (manual adjustments, wholesale reservations)
- Collection structure
- Product metadata

**Shopify → Fibermade:**
- Retail sales (decrements inventory)
- Manual inventory adjustments made in Shopify
- Product updates (if dyer edits in Shopify admin)

**Integration Approach:**
- Custom Shopify App (not public app marketplace initially)
- Real-time webhooks for inventory and order events
- Thin connector pattern (no heavy polling)

---

## What Stage 1 Does NOT Do

**No Retail Storefront:**
- Shopify remains the retail-facing website
- Fibermade is wholesale-only in Stage 1

**No Payment Processing:**
- Wholesale orders are paid via check, Venmo, wire transfer
- Dyer marks orders "paid" manually in Fibermade
- Retail payments stay in Shopify

**No Production Planning:**
- Stage 1 focuses on order management and inventory
- Dye lists and production planning come later

**No Show/Event Management:**
- Stage 1 is wholesale-first
- Show workflows deferred to later stages

---

## User Workflow

### For Creators (Dyers)

**Setup:**
1. Install Fibermade Shopify App
2. Existing Shopify products import into Fibermade
3. Organize products into fiber structure (colorways, bases, collections)
4. Add store relationships with wholesale terms

**Daily Operations:**
1. Manage catalog in Fibermade (add colorways, update inventory after dye sessions)
2. Accept/reject wholesale orders in Fibermade
3. Mark orders fulfilled when yarn is dyed and ready
4. Mark orders delivered when shipped
5. Manage retail orders/fulfillment in Shopify as usual

**Inventory Stays in Sync:**
- Adjustments in Fibermade sync to Shopify
- Retail sales in Shopify sync to Fibermade
- Wholesale acceptance automatically reserves inventory

### For Stores (Buyers)

**Setup:**
1. Receive invitation from creator
2. Create account in Fibermade
3. View wholesale terms and catalog

**Ordering:**
1. Browse creator's catalog (colorways, bases, collections)
2. Add items to cart with inline quantity selection
3. Save draft or submit order
4. Receive notification when order accepted
5. Receive notification when fulfilled/shipped
6. Pay outside system (check, Venmo, etc.)

---

## Technical Architecture

**Fibermade SaaS (Laravel/Inertia/Vue):**
- Wholesale catalog management
- Store relationship and order management
- Inventory tracking (reserved vs. available)
- Shopify sync orchestration

**Shopify Integration:**
- Custom app with OAuth authentication
- Webhooks for inventory, orders, products
- Admin API for pushing/pulling product data
- No public app marketplace (private installations)

**Data Flow:**
- Catalog/inventory master lives in Fibermade
- Shopify is a synchronized replica for retail purposes
- Wholesale orders exist only in Fibermade
- Retail orders flow from Shopify to Fibermade for inventory updates

---

## Pricing

**$39/month**

**Why this price:**
- Matches Shopify Basic plan cost (easy mental anchor)
- Total stack: $78/mo (Shopify $39 + Fibermade $39)
- Affordable for $20-80K annual revenue businesses
- Sets up clean migration to Stage 2 (full platform at $79/mo = net savings)

**Payment:**
- Monthly recurring via Stripe
- Charged directly by Fibermade (not through Shopify billing initially)

**Money-Back Guarantee:**
- 30 days, no questions asked
- Cancel within 30 days → automatic full refund via Stripe
- No setup waste from trial expiration pressure
- Matches wholesale sales cycles (stores need time to actually order)

**Discount Codes:**
- Support for promotional and partner discount codes
- Use cases: early adopter friends (free months), fiber festival promotions, community partnerships
- Applied at signup, managed through Stripe coupon system

**Future:**
- Annual billing with 2 months free ($390/year = $32.50/mo effective)

---

## Success Metrics

**Adoption:**
- 50-100 creators in first 6 months
- 200-500 creators within 18 months

**Engagement:**
- Active wholesale orders managed per creator
- Number of store relationships per creator
- Frequency of inventory updates

**Retention:**
- Monthly churn rate <5%
- Wholesale orders processed through platform vs. email

**Validation for Stage 2:**
- % of users expressing interest in full platform migration
- Pain points with Shopify dependency
- Willingness to pay for standalone solution

---

## Migration Path to Stage 2

**Stage 1 (Current):**
- Shopify App
- Wholesale-only
- $39/mo
- Total cost: $78/mo ($39 Shopify + $39 Fibermade)

**Stage 2 (Future):**
- Full platform with retail frontend
- Still uses Shopify for payment processing
- Manages catalog/inventory/wholesale natively
- $99/mo standalone
- Net savings: $1/mo, one less system

**Stage 3 (Future):**
- Complete independence
- Direct payment processing (Stripe)
- POS integration
- $139/mo
- No Shopify dependency

Users who start in Stage 1 get grandfathered pricing and migration support when Stage 2 launches.

---

## Why Stage 1 First

**Lower Risk for Users:**
- Doesn't require abandoning existing Shopify store
- Retail customers see no change
- Can evaluate Fibermade with existing infrastructure

**Faster Validation:**
- Proves wholesale workflows before building full commerce platform
- Real user feedback on fiber-specific features
- Revenue while building Stage 2

**Network Effects:**
- Store owners using Fibermade with one dyer discover other dyers
- Creates demand for dyers to join the platform
- Builds wholesale marketplace organically

**Technical Pragmatism:**
- Avoids rebuilding payment processing, POS, storefront immediately
- Focuses development on unique value (wholesale + fiber structure)
- Shopify handles the commodity infrastructure

---

## What Stage 1 Proves

Before investing in Stage 2 (full platform), Stage 1 must validate:

1. **Wholesale workflows are valuable** — creators actively manage orders through Fibermade instead of email
2. **Fiber-specific catalog structure works** — colorways/bases/collections model fits real usage
3. **Inventory reservation is essential** — prevents overselling and reduces anxiety
4. **Store relationships matter** — per-store terms and history are used consistently
5. **Users trust the sync** — comfortable with bi-directional Shopify integration
6. **Willingness to pay $39/mo** — price is sustainable for target market
7. **Demand for Stage 2 exists** — users express frustration with Shopify dependency

If these hold true, Stage 2 becomes a natural evolution that users actively want, not a forced migration.

---

## Non-Goals for Stage 1

**Not trying to:**
- Replace Shopify entirely (that's Stage 2)
- Handle payment processing (deferred to Stage 2+)
- Compete on retail features (Shopify is fine for that)
- Build for mass-market dyers (focus on small-batch, wholesale-selective)
- Support every edge case (opinionated defaults over infinite flexibility)

**Explicitly avoiding:**
- Feature parity with Shopify
- Generic ecommerce capabilities
- Complex pricing models (one tier, $39/mo)
- Public Shopify App Store (controlled rollout via invitation)

Stage 1 is about proving wholesale management for fiber people works.
Everything else is distraction.