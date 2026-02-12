# Fibermade Stage 1

## Purpose

Fibermade is a commerce platform built for the fiber community, starting with yarn dyers (called creators). Stage 1 delivers two core capabilities:

1. **Shopify Bridge** -- Import a creator's existing Shopify products into Fibermade, so their catalog is populated without manual data entry. Most dyers already use Shopify for retail sales and will continue to for the foreseeable future.
2. **Wholesale Ordering** -- Give creators an affordable way to take wholesale orders from stores. Currently most dyers handle wholesale via ad hoc purchase orders, emails, and texts. Shopify's wholesale features require $2000/mo. Fibermade replaces that chaos with a structured system where creators invite stores, stores browse the catalog and place orders, and both parties manage orders in Fibermade.

## User Stories in Scope

Stories are defined in [User-Stories.md](../about/User-Stories.md). Stage 1 delivers:

**Shopify Integration:** SI-1, SI-2, SI-3, SI-4
**Catalog & Inventory:** CI-1, CI-2, CI-3, CI-4
**Wholesale Ordering:** WO-1, WO-2, WO-3, WO-4, WO-5, WO-6
**Wholesale Management:** WM-1, WM-2, WM-3, WM-4, WM-5, WM-6, WM-7
**Store Relationships:** SR-1, SR-2, SR-3, SR-4, SR-5

## Target Persona

Stage 1 is built for **Stephanie** (see [Personas.md](../about/Personas.md)): a small-batch, wholesale-selective dyer who balances creativity with reliability. She sells to a limited number of stores she trusts, uses flexible minimums, and wants clear, low-stress wholesale workflows without industrial assumptions. The wholesale system directly addresses her need to manage store relationships and orders without overcommitting.

David (production-scale, wholesale-first) also benefits immediately from Stage 1's wholesale and inventory features. James (show-first) and Laura (show-heavy) will benefit more as show features expand in later stages.

## Strategic Context

Fibermade's long-term vision (see [Vision.md](../about/Vision.md)) is to be the commerce platform built for the fiber community — starting with dyers, expanding to serve the full ecosystem.

Stage 1 deliberately builds a Shopify integration. The pragmatic reason: most dyers are already on Shopify and need it for retail sales today. The bridge app gets their catalog into Fibermade without manual data entry, which is the fastest path to delivering wholesale value. As Fibermade matures and takes over retail (storefront, checkout, payments), the Shopify dependency becomes optional.

## Core Concept

```
┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│                 │     │                 │     │                 │
│  Shopify Store  │◄───►│  Bridge App     │◄───►│  Fibermade      │
│  (Merchant)     │     │  (shopify)      │     │  (platform)     │
│                 │     │                 │     │                 │
└─────────────────┘     └─────────────────┘     └─────────────────┘
                                                       ▲
                                                       │
                                                ┌──────┴──────┐
                                                │   Stores    │
                                                │  (wholesale │
                                                │   buyers)   │
                                                └─────────────┘
```

**Shopify Bridge Flow:**
1. A creator installs the Fibermade app from Shopify App Store
2. The bridge app authenticates and stores the merchant's credentials
3. The bridge app imports products from Shopify into Fibermade
4. Product changes in either system are synced via webhooks and metafields

**Wholesale Flow:**
1. A creator invites stores to work with them on Fibermade
2. The store accepts the invite and gets a login
3. The store browses the creator's catalog and places wholesale orders
4. Both parties manage the order lifecycle in Fibermade

## Implementation

See [epics.md](epics.md) for the detailed epic breakdown. Technical architecture, data mapping, and component details live in the individual epic overview docs.

## Resolved Decisions

1. **API Authentication**: Laravel Sanctum token-based auth. The Shopify app authenticates with the platform API using Sanctum tokens.
2. **API Architecture**: The platform keeps Inertia for its frontend. A new `routes/api.php` surface is added alongside it for external clients (Shopify app first, mobile/POS and others later).
3. **Sequencing**: Shopify bridge first (most dyers already on Shopify), then wholesale ordering on top of the imported catalog.
4. **Product Mapping**: Metafields on Shopify side, ExternalIdentifier on Fibermade side. No extra mapping tables.
5. **Product Flow**: Bidirectional. Products can be created in either Shopify or Fibermade and synced to the other.
6. **Shopify Integration**: Pragmatic deviation from long-term vision. The bridge accelerates Stage 1 value delivery and becomes optional as Fibermade matures.
