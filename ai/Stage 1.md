# Stage 1 — Shopify Augmentation (Wholesale & Operations)

## Purpose
Stage 1 exists to make Fibermade **indispensable without disruption**.

The goal is not to replace Shopify, redirect buyers, or change how customers sell.
The goal is to take over the parts of the business that Shopify is fundamentally bad at for hand-dyed yarn—especially for small-batch dyers selling wholesale selectively.

Stage 1 focuses exclusively on:
- wholesale management
- production reality
- inventory truth
- production planning across orders

If Stage 1 is successful, Fibermade becomes the tool dyers open first every day, while Shopify fades into the background.

---

## Primary Customer Problem
Small-batch hand-dyed yarn businesses routinely manage:
- wholesale orders in spreadsheets, email, or PDFs
- production planning in notebooks or notes apps
- inventory that is technically “accurate” but operationally wrong
- no unified view of what actually needs to be dyed across multiple wholesale and retail orders
- manual, error-prone inventory adjustments after sales occur

These problems create:
- constant mental overhead
- mistakes during busy periods
- friction when growing wholesale responsibly
- anxiety around fulfilling commitments
- loss of trust in inventory numbers

Stage 1 is designed to remove this burden **without forcing dyers to change their storefront, checkout, or fulfillment workflow**.

---

## What Changes for the Customer
Before Stage 1:
- Shopify is treated as the center of truth
- Wholesale lives outside the system
- Inventory numbers are unreliable for planning
- Production decisions are made manually
- Reconciliation happens inconsistently, if at all

After Stage 1:
- Fibermade becomes the daily operational tool
- Wholesale orders live in a real system
- Inventory reflects physical reality, not storefront counts
- Production planning is generated from real commitments
- Inventory reconciliation is explicit and intentional
- Shopify remains the sales and fulfillment surface, not the brain

The dyer’s internal workflow changes even though their storefront does not.

---

## System Boundaries (Critical)
This stage establishes non-negotiable architectural rules:

**Fibermade owns production reality, planning, wholesale logic, and inventory truth.  
Shopify owns presentation, checkout, and fulfillment execution.**

### Fibermade is the system of record for:
- inventory truth
- production batches
- wholesale relationships and pricing
- order intake for production planning
- inventory reservation and reconciliation
- operational statuses and reporting

### Shopify remains responsible for:
- storefront presentation
- customer checkout
- payment processing
- shipping and fulfillment

Fibermade augments Shopify; it does not compete with it in Stage 1.

---

## Shopify Integration Principles
- **GraphQL Admin API only** (GraphQL-first)
- **Single location only** is supported  
  (multi-location inventory exceeds Stage 1 scope)
- Shopify is treated as a **presentation projection**
- Fibermade IDs are stored in Shopify via **metafields**
- Only Shopify features fully supported via GraphQL are used
- Shopify Functions are explicitly out of scope for Stage 1

---

## Core Capabilities (Stage 1)

### Catalog Awareness
- Colorways
- Bases
- Collections
- Status (active, retired)

Catalog data exists to support production and wholesale planning, not merchandising.

---

### Production-Aware Inventory
- Inventory truth reflects physical reality
- Orders reserve inventory but do not consume it
- Clear separation between:
  - inventory truth
  - inventory availability
  - inventory reconciliation
- Inventory values pushed to Shopify represent **availability**, not truth

Inventory is designed to support confident planning, not real-time sales accuracy.

---

### Wholesale Management (Primary Value)
- Store accounts and relationships
- Store-level pricing or terms
- Wholesale orders tracked independently of retail
- Order statuses reflect both business and production reality
- Ability to create and send wholesale invoices
- Store accounts can view and track their orders

Wholesale workflows are optimized for:
- selective relationships
- small-batch constraints
- reliability without industrial assumptions

---

### Orders and Production Planning
Fibermade supports two order sources in Stage 1:
- **Wholesale Orders** (created and managed in Fibermade)
- **Retail Orders** (paid Shopify orders imported for planning)

Both order types:
- reserve inventory
- contribute to Dye Lists
- participate in production planning

Fibermade does not model sales channels beyond wholesale and Shopify retail in Stage 1.

---

### Order Intake and Dye Lists
- Paid Shopify orders are pulled into Fibermade via GraphQL
- Order intake is triggered via webhooks and refreshed via API
- Only **paid orders** contribute to production planning
- Cancelled or refunded orders are removed from Dye Lists

Fibermade generates **Dye Lists** that:
- aggregate required quantities by colorway and base
- combine wholesale and retail demand
- support printing and batch planning
- allow drilldown into contributing orders
- link back to Shopify orders for fulfillment

Dye Lists answer one question clearly:

> “What do I need to dye next, and how much?”

---

### Inventory Reconciliation (Explicit)
Inventory reconciliation aligns Fibermade’s inventory truth with what has already happened in the real world.

Reconciliation is:
- manual
- intentional
- auditable
- irreversible without a new explicit adjustment

#### Wholesale Orders
- Reconcile Inventory:
  - consumes the full order quantities
  - updates inventory truth
  - closes the order

#### Retail Orders (Shopify)
- Reconcile Inventory:
  - consumes the full order quantities
  - updates inventory truth
  - closes the order
- Fulfillment continues in Shopify

Inventory adjustments for any other reason (loss, damage, events, corrections) must be made explicitly.

---

## Non-Goals (Explicit)
Stage 1 does **not** attempt to:
- Provide a customer-facing website
- Handle consumer checkout
- Process payments
- Manage discounts or promotions
- Model shows, festivals, or events
- Act as a POS system
- Perform live inventory updates
- Handle shipping labels or fulfillment
- Support multi-location inventory
- Use Shopify Functions
- Solve taxes or accounting
- Act as a marketplace

Avoiding these is intentional and required for focus.

---

## Exit Criteria (Stage 1 → Stage 2)
Stage 1 is successful when:
- Fibermade is opened daily by active users
- Wholesale orders are primarily managed inside Fibermade
- Production decisions are made using Fibermade data
- Dye Lists are trusted and used for real planning
- Inventory reconciliation happens consistently
- Shopify is treated purely as a sales and fulfillment surface

Only once Fibermade clearly owns operational truth should Stage 2 be considered.

---

## Risks to Monitor
- Fibermade being treated as “nice to have”
- Wholesale continuing to live outside the system
- Inventory truth being ignored in favor of Shopify counts
- Dye Lists being advisory instead of authoritative
- Reconciliation being skipped during busy periods

Stage 1 succeeds through discipline, not completeness.

---

## Strategic Role of Stage 1
Stage 1 earns trust.

It embeds Fibermade into real workflows without migration risk.
It establishes Fibermade as the system of record for wholesale, production, and inventory.
It creates the operational confidence required to safely introduce a Fibermade-owned website, checkout, discounts, and show workflows in Stage 2.

Nothing beyond Stage 1 works if this stage does not.
