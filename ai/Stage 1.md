# Stage 1 — Shopify Augmentation

## Purpose
Stage 1 exists to make Fibermade **indispensable without disruption**.

The goal is not to replace Shopify, redirect buyers, or change how customers sell.
The goal is to take over the parts of the business that Shopify is fundamentally bad at for hand-dyed yarn:
- production reality
- wholesale management
- inventory truth
- discount intent
- production planning across orders

If Stage 1 is successful, Fibermade becomes the tool dyers open first every day, while Shopify fades into the background.

---

## Primary Customer Problem
Hand-dyed yarn businesses routinely manage:
- wholesale orders in spreadsheets
- production planning in notebooks or notes apps
- inventory that is technically “accurate” but operationally wrong
- discounts scattered across Shopify settings that are hard to reason about
- no unified view of what actually needs to be dyed across multiple orders
- manual, error-prone inventory adjustments after sales occur

These problems create:
- constant mental overhead
- errors during busy periods
- friction when scaling wholesale
- anxiety around launches, events, and sales
- loss of trust in inventory numbers

Stage 1 is designed to remove that burden without forcing dyers to change their storefront, checkout, or fulfillment workflow.

---

## What Changes for the Customer
Before Stage 1:
- Shopify is the center of truth
- Inventory numbers lie
- Wholesale lives outside the system
- Discounts are configured directly in Shopify
- Production planning is manual and fragmented
- Inventory reconciliation is done by hand

After Stage 1:
- Fibermade becomes the daily operational tool
- Production and inventory reflect reality
- Wholesale orders live inside a real system
- Discount intent is managed in Fibermade
- Dye planning is generated automatically from real orders
- Inventory reconciliation is explicit and intentional
- Shopify remains the sales and fulfillment surface, not the brain

The dyer’s workflow changes even though their storefront does not.

---

## System Boundaries (Critical)
This stage establishes non-negotiable architectural rules:

**Fibermade owns production, discount intent, planning, and inventory truth.  
Shopify owns presentation, checkout, and fulfillment execution.**

Specifically:
- Fibermade is the system of record for:
  - inventory truth
  - production batches
  - wholesale relationships and pricing
  - discount presets and activation
  - order intake for production planning
  - inventory reservation and reconciliation
  - operational statuses and reporting
- Shopify remains responsible for:
  - storefront presentation
  - customer checkout
  - payment processing
  - shipping and fulfillment

Fibermade augments Shopify; it does not compete with it in Stage 1.

---

## Shopify Integration Principles
- **GraphQL Admin API only** (GraphQL-first)
- **Single location only** is supported  
  (multi-location stores exceed Stage 1 scope)
- Shopify is treated as a **presentation projection**
- Fibermade IDs are stored in Shopify via **metafields**
- Only Shopify features that are fully supported via GraphQL are used
- Shopify Functions are explicitly out of scope for Stage 1

---

## Core Capabilities (High Level)

### Catalog Awareness
- Colorways
- Bases
- Collections
- Color Tags
- Status (active, retired) (more down the road)

### Production-Aware Inventory
- Inventory truth reflects physical reality
- Orders reserve inventory but do not consume it
- Clear separation between:
  - inventory truth
  - inventory availability
  - inventory reconciliation
- Inventory values pushed to Shopify represent availability, not truth

### Wholesale Management
- Store accounts and relationships
- Store account-level pricing or discount logic
- Wholesale orders tracked independently of retail
- Order statuses reflect both business and production reality
- Wholesale remains primarily a Fibermade concept
- Ability to create and send invoices
- Store accounts can track their orders

### Discount Presets (Opinionated)
Fibermade manages **discount intent** using a curated set of preset types that reflect how dyers actually sell.

Discount presets are:
- reusable
- parameter-driven (amounts, thresholds, dates)
- intentionally limited in shape
- activated and managed in Fibermade
- executed by Shopify at checkout via GraphQL-supported discount types

Initial supported preset types include:
- **Order threshold free shipping**  
  (e.g. free shipping on orders over $75)
- **Quantity-based per-skein discount**  
  (e.g. buy 5 skeins, save $3 per skein — “sweater discount”)
- **Percentage-based discount**  
  (e.g. 10% event discount, 20% customer goodwill discount)
- **Manual free shipping code**
- **Time-boxed sale discounts**

Arbitrary discount rule builders and unsupported Shopify features are intentionally excluded.

### Orders and Planning
Fibermade supports three order types in Stage 1:
- **Wholesale Orders** (external buyers, internal execution)
- **Retail Orders** (paid Shopify orders, imported for planning)
- **Show Orders** (internal allocation for in-person events)

All order types:
- reserve inventory
- contribute to Dye Lists
- participate in production planning

---

### Order Intake and Dye Lists
- Paid Shopify orders are pulled into Fibermade via GraphQL
- Order intake is triggered via webhooks and refreshed via API
- Only **paid orders** contribute to production planning
- Cancelled or refunded orders are removed from Dye Lists

Fibermade generates **Dye Lists** that:
- aggregate required quantities by colorway and base
- combine wholesale, retail, and show demand
- support printing and batch planning
- allow drilldown into contributing orders
- link back to Shopify order pages for fulfillment

Dye Lists answer one question clearly:
“What do I need to dye next, and how much?”

---

### Inventory Reconciliation (Explicit)
Inventory reconciliation aligns Fibermade’s inventory truth with what has already happened in the real world.

Reconciliation is:
- manual
- intentional
- auditable
- irreversible without a new explicit adjustment

#### Wholesale Orders
- A “Reconcile Inventory” action:
  - consumes the full order quantities
  - updates inventory truth
  - closes the order

#### Retail Orders (Shopify)
- A “Reconcile Inventory” action:
  - consumes the full order quantities
  - updates inventory truth
  - closes the order
- Fulfillment and shipping continue to happen in Shopify

#### Show Orders
- Inventory is reserved before the show
- After the show, the dyer records quantities **returned**
- Fibermade computes quantities sold
- Inventory truth is updated accordingly
- The Show Order is closed

Reconciliation exists to reflect reality, not predict it.

---

## Non-Goals (Explicit)
Stage 1 does **not** attempt to:
- Replace the Shopify storefront
- Handle consumer checkout
- Process payments
- Handle shipping labels or fulfillment
- Act as a POS system
- Aggregate buyers or act as a marketplace
- Support multi-location inventory
- Use Shopify Functions
- Implement arbitrary discount rule builders
- Perform live inventory updates during shows
- Solve taxes or accounting

Avoiding these is intentional and necessary for focus.

---

## Exit Criteria (Stage 1 → Stage 2)
Stage 1 is considered successful when:
- Fibermade is opened daily by active users
- Wholesale orders are primarily managed inside Fibermade
- Inventory decisions are made based on Fibermade data, not Shopify counts
- Discount creation and management happens in Fibermade, not Shopify
- Dye Lists are used for real production planning
- Inventory reconciliation happens inside Fibermade
- Shopify is treated as a sales and fulfillment surface, not an operational tool

Only once Fibermade is clearly the system of record should Stage 2 be considered.

---

## Risks to Monitor
- Fibermade being treated as “nice to have” instead of essential
- Shopify remaining the de facto source of truth
- Overbuilding discount logic instead of enforcing preset boundaries
- Order sync drift or webhook edge cases
- Dye Lists becoming advisory instead of trusted
- Reconciliation being skipped or misunderstood

Stage 1 succeeds through discipline, not completeness.

---

## Strategic Role of Stage 1
Stage 1 earns trust.

It embeds Fibermade into real workflows without forcing migration risk.
It establishes Fibermade as the place where dyers think about production, inventory, wholesale, discounts, planning, and reconciliation.
It generates the operational data and confidence required to safely reduce dependence on Shopify later.

Nothing beyond Stage 1 works if this stage does not.
