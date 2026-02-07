# Fibermade Stage 1

## Purpose

Fibermade is a production planning and wholesale ordering platform for yarn dyers (called creators). Stage 1 delivers two core capabilities:

1. **Shopify Bridge** -- Import a creator's existing Shopify products into Fibermade, so their catalog is populated without manual data entry. Most dyers already use Shopify for retail sales and will continue to for the foreseeable future.
2. **Wholesale Ordering** -- Give creators an affordable way to take wholesale orders from stores. Currently most dyers handle wholesale via ad hoc purchase orders, emails, and texts. Shopify's wholesale features require $2000/mo. Fibermade replaces that chaos with a structured system where creators invite stores, stores browse the catalog and place orders, and both parties manage orders in Fibermade.

See [Features.md](../lore/Features.md) for the full product vision beyond Stage 1.

## Target Persona

Stage 1 is built for **Stephanie** (see [Personas.md](../lore/Personas.md)): a small-batch, wholesale-selective dyer who balances creativity with reliability. She sells to a limited number of stores she trusts, uses flexible minimums, and wants clear, low-stress wholesale workflows without industrial assumptions. The wholesale system directly addresses her need to manage store relationships and orders without overcommitting.

David (production-scale, wholesale-first) also benefits immediately from Stage 1's wholesale and inventory features. James (show-first) and Laura (show-heavy) will benefit more as show features expand in later stages.

## Strategic Context

Fibermade's long-term strategy (see [Strategy.md](../lore/Strategy.md)) positions it as the operational system of record -- owning catalog, inventory truth, orders, and production. The strategy favors migration over integration and avoids "sync illusions."

Stage 1 deliberately deviates from this by building a Shopify integration. The pragmatic reason: most dyers are already on Shopify and need it for retail sales today. The bridge app gets their catalog into Fibermade without manual data entry, which is the fastest path to delivering wholesale value. As Fibermade matures and takes over retail (storefront, checkout, payments), the Shopify dependency becomes optional -- exactly as the strategy intends.

## Core Concept

```
┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│                 │     │                 │     │                 │
│  Shopify Store  │◄───►│  Bridge App     │◄───►│  Fibermade      │
│  (Merchant)     │     │  (shopify)  │     │  (platform)      │
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

## What Gets Built

### Shopify Bridge (shopify)

#### Product Sync (Shopify ↔ Fibermade)
- Bidirectional product flow using metafields for mapping
- Import Shopify products as Colorways, variants as Bases (via Inventory records)
- Create Shopify products from Fibermade Colorways
- Sync product images to Media table
- Map collections to Fibermade Collections

#### Inventory Sync (Bidirectional)
- Push Fibermade inventory levels to Shopify variant availability
- Prevent overselling by keeping quantities in sync
- Real-time updates when inventory changes in either system

#### Order Import (Shopify → Fibermade)
- Pull paid Shopify orders into the Fibermade Orders system
- Map Shopify customers to Fibermade Customers
- Link line items to Colorway + Base combinations

#### Customer Sync (Shopify → Fibermade)
- Import Shopify customers for order history tracking

### Wholesale System (platform)

#### Store-Facing
- Store login and authentication
- Browse creator's catalog (Colorways and Bases) with wholesale pricing
- Build and submit wholesale orders
- View order history and status

#### Creator-Facing
- View incoming wholesale orders
- Process and fulfill orders
- Manage store relationships and invites

## Architecture

### Platform Architecture

The platform keeps its existing Inertia (Laravel + Vue) stack. A new API layer is added alongside it using Laravel Sanctum for token-based auth. The shopify is the first API consumer, but the API is designed as platform infrastructure for future clients (mobile/POS app, admin system, merchant websites).

```
┌──────────────┐  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐
│  Web App     │  │  Shopify     │  │  Mobile /    │  │  Merchant    │
│  (Inertia)   │  │  Bridge App  │  │  POS App     │  │  Websites    │
└──────┬───────┘  └──────┬───────┘  └──────┬───────┘  └──────┬───────┘
       │                 │                 │                 │
       │  (server-side)  │                 │                 │
       ▼                 ▼                 ▼                 ▼
┌─────────────────────────────────────────────────────────────────────┐
│                         Laravel (platform)                            │
│                                                                     │
│  routes/web.php  → Inertia controllers (existing, unchanged)        │
│  routes/api.php  → Platform API (Sanctum auth, versioned)           │
└─────────────────────────────────────────────────────────────────────┘
```

### Shopify Authentication Flow
```
1. Merchant clicks "Install" on Shopify App Store
2. Shopify redirects to bridge app OAuth flow
3. Bridge app receives access token
4. Bridge app creates/updates Integration record via Fibermade API
5. Merchant is redirected to Fibermade dashboard
```

### Data Flow Architecture
```
┌──────────────────────────────────────────────────────────────────┐
│                        Bridge App (shopify)                  │
├──────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌─────────────────┐              ┌─────────────────────────┐   │
│  │  Shopify Admin  │              │  Fibermade API          │   │
│  │  GraphQL API    │              │  (routes/api.php)       │   │
│  └────────┬────────┘              └────────────┬────────────┘   │
│           │                                    │                 │
│           ▼                                    ▼                 │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │                    Sync Services                         │    │
│  │  - ProductSyncService                                    │    │
│  │  - InventorySyncService                                  │    │
│  │  - OrderSyncService                                      │    │
│  │  - CustomerSyncService                                   │    │
│  └─────────────────────────────────────────────────────────┘    │
│                              │                                   │
│                              ▼                                   │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │              Webhook Handlers (Real-time)                │    │
│  │  - products/create, products/update, products/delete     │    │
│  │  - orders/create, orders/paid, orders/fulfilled          │    │
│  │  - inventory_levels/update                               │    │
│  │  - customers/create, customers/update                    │    │
│  │  - app/uninstalled                                       │    │
│  └─────────────────────────────────────────────────────────┘    │
│                                                                  │
└──────────────────────────────────────────────────────────────────┘
```

## Key Components to Build

### 1. Fibermade API Client
Create a service in the shopify that communicates with the platform:
- Authenticate with Sanctum tokens
- CRUD operations for Colorways, Bases, Inventory, Orders, Customers
- Handle rate limiting and retries

### 2. Sync Services
Build services for each entity type:

| Service | Direction | Purpose |
|---------|-----------|---------|
| ProductSyncService | Shopify ↔ Fibermade | Sync products as Colorways/Bases |
| InventorySyncService | Bidirectional | Keep stock levels in sync |
| OrderSyncService | Shopify → Fibermade | Import orders for production |
| CustomerSyncService | Shopify → Fibermade | Import customer data |
| CollectionSyncService | Shopify → Fibermade | Organize products |

### 3. Webhook Handlers
Real-time event processing:
- `products/create` - Create Colorway in Fibermade
- `products/update` - Update Colorway details
- `products/delete` - Mark Colorway as archived
- `orders/paid` - Import order for production planning
- `inventory_levels/update` - Sync inventory changes
- `app/uninstalled` - Clean up Integration record

### 4. Embedded App UI
The app UI within Shopify Admin should provide:
- Connection status to Fibermade account
- Manual sync triggers
- Sync history and logs
- Settings for sync preferences (e.g., which products to sync)

## Data Mapping

### Product Mapping Strategy

Products are mapped bidirectionally using two complementary mechanisms:

**Shopify side -- Metafields:**
Shopify products store Fibermade IDs as metafields. This enables bidirectional product flow and survives manual edits in Shopify.

```
Product metafield:  fibermade.colorway_id = "clr_abc123"
Variant metafield:  fibermade.base_id = "base_xyz789"
```

**Fibermade side -- ExternalIdentifier:**
The existing `ExternalIdentifier` model stores Shopify GIDs for reverse lookup without calling the Shopify API.

```
external_type: shopify_product | shopify_variant | shopify_order | shopify_customer
external_id: The Shopify GID (e.g., "gid://shopify/Product/123")
identifiable_type: Colorway | Inventory | Order | Customer
identifiable_id: The Fibermade model ID
integration_id: Links to the specific Shopify store connection
```

No extra mapping tables are needed. Metafields + ExternalIdentifier cover both directions.

### Bidirectional Product Flow

**Shopify → Fibermade:** Product created/updated in Shopify triggers webhook. Bridge app creates/updates Colorway + Bases in Fibermade via API. Writes `fibermade.colorway_id` and `fibermade.base_id` metafields back to Shopify. Creates ExternalIdentifier records in Fibermade.

**Fibermade → Shopify:** Creator creates/updates Colorway in Fibermade. Bridge app creates/updates Shopify product via GraphQL with metafields included. Creates ExternalIdentifier records in Fibermade.

### Entity Mapping

| Shopify Entity | Fibermade Entity | Notes |
|----------------|------------------|-------|
| Product | Colorway | Title, description, images |
| Variant | Inventory (Base link) | SKU, price, weight |
| Collection | Collection | Smart or manual collections |
| Order | Order (type: retail) | Line items, totals |
| LineItem | OrderItem | Links to Colorway + Base |
| Customer | Customer | Email, name, addresses |
| Inventory Level | Inventory.quantity | Stock tracking |

## Integration with Existing Web-App Infrastructure

The platform already has the foundation:

| Component | Status | Purpose |
|-----------|--------|---------|
| Integration model | Exists | Store Shopify credentials |
| ExternalIdentifier model | Exists | Map Shopify IDs to local models |
| IntegrationLog model | Exists | Track sync operations |
| ImportService | Exists | Handle import errors |
| Colorway/Base/Order models | Exists | Target models for sync |
| Store/Invite models | Exists | Wholesale store relationships |
| Customer model | Exists | Wholesale and retail customers |

## Implementation

See [epics.md](epics.md) for the detailed epic breakdown.

## Technical Considerations

### Rate Limiting
- Shopify Admin API: ~40 requests/second (burst)
- Use GraphQL bulk operations for large syncs
- Queue webhook processing to handle spikes

### Data Consistency
- Use transactions for multi-model updates
- Implement idempotent webhook handlers
- Store sync timestamps for conflict resolution

### Security
- Encrypt Shopify access tokens
- Validate webhook signatures
- Use HTTPS for all Fibermade API calls

### Error Handling
- Log all sync operations to IntegrationLog
- Implement exponential backoff for retries
- Surface errors in embedded app UI

## Resolved Decisions

1. **API Authentication**: Laravel Sanctum token-based auth. The shopify authenticates with the platform API using Sanctum tokens.
2. **API Architecture**: The platform keeps Inertia for its frontend. A new `routes/api.php` surface is added alongside it for external clients (shopify first, mobile/POS and others later).
3. **Sequencing**: Shopify bridge first (most dyers already on Shopify), then wholesale ordering on top of the imported catalog.
4. **Product Mapping**: Metafields on Shopify side, ExternalIdentifier on Fibermade side. No extra mapping tables.
5. **Product Flow**: Bidirectional. Products can be created in either Shopify or Fibermade and synced to the other.
6. **Shopify Integration**: Pragmatic deviation from long-term "migration not integration" strategy. The bridge accelerates Stage 1 value delivery and becomes optional as Fibermade matures.

## Open Questions

1. **Account Linking**: How does a Shopify merchant link to their Fibermade account? (Email match, manual code, automatic?)
2. **Sync Preferences**: Should merchants choose which products to sync, or sync everything?
3. **Conflict Resolution**: When inventory differs, which source wins?
4. **Multi-Store**: Can one Fibermade account connect multiple Shopify stores?
