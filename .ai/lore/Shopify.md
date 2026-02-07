# Fibermade Shopify Integration

## Overview

Fibermade's Shopify integration follows a **thin connector pattern**. The Shopify app is a minimal bridge that establishes authentication and forwards data, while Fibermade remains the primary workspace where dyers manage their business.

This approach supports the strategic goal of making Shopify optional over time while providing immediate value to dyers who currently use Shopify.

## Architecture

```
┌─────────────────────────┐
│  Dyer Daily Workflow    │
│  (Fibermade Laravel/Vue)│  ← Primary interface
│                         │  ← Wholesale management
│  fibermade.app          │  ← Inventory truth
└───────────┬─────────────┘  ← Dye lists
            │
            │ Direct GraphQL API calls
            │ (using stored access token)
            ↓
┌─────────────────────────┐
│  Shopify Connector App  │  ← Minimal React app
│  (React/Remix)          │  ← OAuth handler
│                         │  ← Webhook receiver
│  Embedded in Shopify    │  ← Settings page only
└───────────┬─────────────┘
            │
            │ Webhooks & GraphQL
            ↓
┌─────────────────────────┐
│  Shopify Store          │
│  (Dyer's Shopify Admin) │
└─────────────────────────┘
```

## Two Codebases

### 1. Shopify Connector App (New - React/Remix)

**Purpose:** Minimal integration layer

**Responsibilities:**
- Shopify OAuth flow
- Single embedded settings page
- Webhook receivers (forward to Fibermade)
- API endpoint for account linking
- Token storage

**Tech Stack:**
- Built with Shopify's official template: `npm init @shopify/app@latest`
- React/Remix
- Shopify App Bridge
- Small database for storing shop credentials

**Key Files:**
```
app/
├── routes/
│   ├── app._index.jsx          # Settings page (embedded)
│   ├── api.link.jsx             # Account linking endpoint
│   ├── api.unlink.jsx           # Disconnect endpoint
│   └── webhooks/
│       ├── orders.jsx           # Forward orders to Fibermade
│       ├── products.jsx         # Forward product changes
│       └── inventory.jsx        # Forward inventory updates
├── shopify.app.toml             # App configuration & scopes
└── models/
    └── shopify-shop.server.js   # Shop credential storage
```

### 2. Fibermade (Existing - Laravel/Vue)

**Purpose:** Primary workspace for dyers

**New Responsibilities for Shopify Integration:**
- Account linking UI
- Store Shopify access tokens
- GraphQL client for direct Shopify API calls
- Product mapping interface (Shopify ↔ Fibermade)
- Webhook receivers from connector app
- Inventory sync logic

**New Routes/Controllers:**
```
routes/shopify.php:
- GET  /shopify/connect          # Landing page for linking
- POST /shopify/link             # Complete the link
- GET  /shopify/settings         # Integration settings
- GET  /shopify/products/unmapped # Products needing mapping
- POST /shopify/products/map     # Map a product

app/Http/Controllers/Shopify/
├── ConnectionController.php     # Handle account linking
├── ProductSyncController.php    # Sync & map products
├── WebhookController.php        # Receive forwarded webhooks
└── InventoryController.php      # Push inventory updates
```

## Metafield Strategy (Critical Decision)

**We use Shopify metafields as the source of truth for product mapping.**

### Why Metafields?

1. **Survives manual edits** - If dyer edits product in Shopify, mapping persists
2. **Two-way discovery** - Can find Fibermade data from Shopify or vice versa
3. **Future-proof** - Supports both Fibermade→Shopify and Shopify→Fibermade product creation

### Metafield Structure

**Product-level metafield:**
```javascript
{
  namespace: "fibermade",
  key: "colorway_id",
  value: "clr_abc123",           // Fibermade colorway ID
  type: "single_line_text_field"
}
```

**Variant-level metafield:**
```javascript
{
  namespace: "fibermade",
  key: "base_id",
  value: "base_xyz789",          // Fibermade base ID
  type: "single_line_text_field"
}
```

**Optional combined identifier:**
```javascript
{
  namespace: "fibermade",
  key: "colorway_base_id",
  value: "cb_combined123",       // Fibermade colorway_base combo ID
  type: "single_line_text_field"
}
```

### Data Flow

**When Fibermade creates/updates a product:**
1. Create or update product in Shopify via GraphQL
2. Write metafields to product and variants
3. Store Shopify IDs in Fibermade as reference

**When reading Shopify products:**
1. Fetch products with metafields included
2. Use metafield values to look up Fibermade records
3. If metafield missing → product is "unmapped"

**When dyer creates product in Shopify:**
1. Webhook fires to connector app
2. Connector forwards to Fibermade
3. Fibermade checks for metafields
4. If missing → add to "unmapped products" queue
5. Dyer maps in Fibermade UI
6. Fibermade writes metafields back to Shopify

## Connection Flow

### Scenario 1: Existing Fibermade User Adds Shopify

```
1. Dyer logs into Fibermade
   ↓
2. Settings → Integrations → Shopify → "Install Shopify App"
   ↓
3. Redirected to Shopify App Store listing
   ↓
4. Click "Install" → Shopify OAuth flow
   ↓
5. Approve scopes → Connector app installed
   ↓
6. Connector app redirects: fibermade.app/shopify/connect?shop=store.myshopify.com&state={token}
   ↓
7. Fibermade validates state, calls connector API:
   POST https://connector.fibermade.app/api/link
   Body: { fibermade_account_id, shop_domain }
   ↓
8. Connector responds with:
   { access_token, shop_domain, webhook_secret }
   ↓
9. Fibermade stores credentials
   ↓
10. Link established ✓
```

### Scenario 2: New User Installs from Shopify App Store

```
1. Find "Fibermade" in Shopify App Store
   ↓
2. Install → Shopify OAuth
   ↓
3. Connector app shows: "Connect your Fibermade account"
   ↓
4. Button redirects: fibermade.app/shopify/connect?shop={shop}
   ↓
5. User signs up or logs into Fibermade
   ↓
6. Continue from step 7 above
```

## Required Shopify Scopes

```toml
# In shopify.app.toml (connector app)
scopes = [
  # Products
  "read_products",
  "write_products",
  "read_product_listings",
  
  # Inventory
  "read_inventory",
  "write_inventory",
  
  # Orders
  "read_orders",
  "read_assigned_fulfillment_orders",
  
  # Locations (required for inventory management)
  "read_locations",
  
  # Metafields
  "read_metaobjects",
  "write_metaobjects"
]
```

## Webhook Strategy

### Webhooks to Register (in connector app)

```javascript
// On app installation, register these webhooks:
const webhooks = [
  // Products
  { topic: "products/create", address: "https://connector.app/webhooks/products" },
  { topic: "products/update", address: "https://connector.app/webhooks/products" },
  { topic: "products/delete", address: "https://connector.app/webhooks/products" },
  
  // Orders
  { topic: "orders/create", address: "https://connector.app/webhooks/orders" },
  { topic: "orders/updated", address: "https://connector.app/webhooks/orders" },
  { topic: "orders/cancelled", address: "https://connector.app/webhooks/orders" },
  
  // Inventory
  { topic: "inventory_levels/update", address: "https://connector.app/webhooks/inventory" },
  
  // App lifecycle
  { topic: "app/uninstalled", address: "https://connector.app/webhooks/uninstall" }
];
```

### Webhook Flow

```
Shopify event occurs (e.g., order created)
  ↓
Shopify POST to connector app webhook endpoint
  ↓
Connector validates HMAC signature
  ↓
Connector looks up Fibermade webhook URL for this shop
  ↓
Connector forwards payload:
  POST https://fibermade.app/api/webhooks/shopify/orders
  Headers: 
    - X-Shop-Domain: store.myshopify.com
    - X-Webhook-Secret: {shared_secret}
  Body: {original Shopify webhook payload}
  ↓
Fibermade validates secret, processes webhook
  ↓
Connector returns 200 OK to Shopify
```

## Critical GraphQL Operations

### Fetch Products with Metafields

```graphql
query GetProducts($cursor: String) {
  products(first: 50, after: $cursor) {
    edges {
      node {
        id
        title
        handle
        status
        
        # Colorway ID stored at product level
        metafield(namespace: "fibermade", key: "colorway_id") {
          value
        }
        
        variants(first: 100) {
          edges {
            node {
              id
              title
              sku
              price
              inventoryQuantity
              
              # Base ID stored at variant level
              metafield(namespace: "fibermade", key: "base_id") {
                value
              }
              
              inventoryItem {
                id
              }
            }
          }
        }
      }
    }
    pageInfo {
      hasNextPage
      endCursor
    }
  }
}
```

### Write Product with Metafields

```graphql
mutation CreateProductWithMetafields($input: ProductInput!) {
  productCreate(input: $input) {
    product {
      id
      title
      metafields(first: 5) {
        edges {
          node {
            namespace
            key
            value
          }
        }
      }
    }
    userErrors {
      field
      message
    }
  }
}

# Input structure:
{
  title: "Sunset Over Galveston",
  productType: "Yarn",
  vendor: "Bad Frog Yarn Co",
  metafields: [
    {
      namespace: "fibermade",
      key: "colorway_id",
      value: "clr_abc123",
      type: "single_line_text_field"
    }
  ],
  variants: [
    {
      title: "Fingering Weight",
      price: "24.00",
      sku: "SOG-FW-001",
      inventoryPolicy: "DENY",
      metafields: [
        {
          namespace: "fibermade",
          key: "base_id",
          value: "base_xyz789",
          type: "single_line_text_field"
        }
      ]
    }
  ]
}
```

### Update Inventory

```graphql
mutation AdjustInventory($input: InventoryAdjustQuantitiesInput!) {
  inventoryAdjustQuantities(input: $input) {
    inventoryAdjustmentGroup {
      reason
      changes {
        item {
          id
        }
        delta
      }
    }
    userErrors {
      field
      message
    }
  }
}

# Input structure:
{
  reason: "correction",  # or "received", "damaged", etc.
  name: "Fibermade reconciliation",
  changes: [
    {
      inventoryItemId: "gid://shopify/InventoryItem/43895969374370",
      locationId: "gid://shopify/Location/61627965538",
      delta: -5  # negative to decrease, positive to increase
    }
  ]
}
```

### Fetch Paid Orders for Dye Lists

```graphql
query GetPaidOrders($cursor: String, $query: String = "financial_status:paid") {
  orders(first: 50, after: $cursor, query: $query) {
    edges {
      node {
        id
        name
        createdAt
        displayFinancialStatus
        
        lineItems(first: 100) {
          edges {
            node {
              id
              title
              quantity
              variant {
                id
                sku
                product {
                  id
                }
              }
            }
          }
        }
      }
    }
    pageInfo {
      hasNextPage
      endCursor
    }
  }
}
```

## Product Mapping Challenge

**The biggest UX challenge:** Existing Shopify products don't have Fibermade metafields yet.

### Initial Sync Flow

```
1. Dyer connects Shopify account
   ↓
2. Fibermade triggers initial product sync
   ↓
3. Fetches all products via GraphQL (paginated)
   ↓
4. For each product:
   - Check for fibermade.colorway_id metafield
   - Check each variant for fibermade.base_id metafield
   - If missing → add to "unmapped products" list
   ↓
5. Show dyer: "50 products need mapping"
```

### Mapping Interface (in Fibermade)

```
Unmapped Products
─────────────────────────────────────────
┌─────────────────────────────────────┐
│ Shopify: "Sunset Over Galveston"   │
│ Variants: 3 (Fingering, DK, Worsted)│
│                                     │
│ Map to Colorway:                    │
│ ┌─────────────────────────────┐    │
│ │ Sunset Over Galveston    ▼ │    │
│ └─────────────────────────────┘    │
│                                     │
│ Variant Mappings:                   │
│ • Fingering → [Fingering Weight ▼] │
│ • DK        → [DK Weight ▼]        │
│ • Worsted   → [Worsted Weight ▼]   │
│                                     │
│ [Skip]  [Map & Sync]               │
└─────────────────────────────────────┘
```

**When "Map & Sync" clicked:**
1. Store mapping in Fibermade database
2. Write metafields to Shopify product/variants via GraphQL
3. Move product from "unmapped" to "synced"
4. Enable two-way sync for this product

### Smart Matching (Future Enhancement)

```javascript
// Auto-suggest mappings based on naming patterns

// Exact title match
if (shopifyProduct.title === fibermadeColorway.name) {
  confidence = "high";
  autoSelect = true;
}

// Fuzzy match (remove common suffixes)
const shopifyClean = shopifyProduct.title
  .replace(/- hand dyed yarn/i, '')
  .replace(/- yarn/i, '')
  .trim();
  
if (fuzzyMatch(shopifyClean, fibermadeColorway.name) > 0.8) {
  confidence = "medium";
  suggest = true;
}

// Variant matching
if (shopifyVariant.title.includes("Fingering")) {
  suggestBase = bases.find(b => b.name.includes("Fingering"));
}
```

## Data Storage

### Connector App Database

```sql
-- Shop credentials and connection status
CREATE TABLE shopify_shops (
  id UUID PRIMARY KEY,
  shop_domain VARCHAR(255) UNIQUE NOT NULL,
  access_token TEXT NOT NULL,
  scopes TEXT NOT NULL,
  fibermade_account_id UUID,  -- NULL until linked
  created_at TIMESTAMP,
  updated_at TIMESTAMP
);

-- Track webhook deliveries for debugging
CREATE TABLE webhook_deliveries (
  id UUID PRIMARY KEY,
  shop_domain VARCHAR(255),
  topic VARCHAR(100),
  payload JSONB,
  forwarded_at TIMESTAMP,
  fibermade_response_code INT
);
```

### Fibermade Database (New Tables)

```sql
-- Shopify connection per dyer account
CREATE TABLE shopify_connections (
  id UUID PRIMARY KEY,
  account_id UUID REFERENCES accounts(id),
  shop_domain VARCHAR(255) UNIQUE NOT NULL,
  access_token TEXT NOT NULL,
  webhook_secret TEXT NOT NULL,
  location_id VARCHAR(255),  -- Primary Shopify location
  synced_at TIMESTAMP,
  created_at TIMESTAMP
);

-- Product mapping (if not relying solely on metafields)
CREATE TABLE shopify_product_mappings (
  id UUID PRIMARY KEY,
  shopify_connection_id UUID REFERENCES shopify_connections(id),
  
  shopify_product_id VARCHAR(255) NOT NULL,
  shopify_variant_id VARCHAR(255) NOT NULL,
  
  colorway_id UUID REFERENCES colorways(id),
  base_id UUID REFERENCES bases(id),
  colorway_base_id UUID REFERENCES colorway_bases(id),
  
  last_synced_at TIMESTAMP,
  created_at TIMESTAMP,
  
  UNIQUE(shopify_product_id, shopify_variant_id)
);

-- Track unmapped products
CREATE TABLE shopify_unmapped_products (
  id UUID PRIMARY KEY,
  shopify_connection_id UUID REFERENCES shopify_connections(id),
  shopify_product_id VARCHAR(255) NOT NULL,
  product_data JSONB,  -- Store full product structure
  discovered_at TIMESTAMP,
  mapped_at TIMESTAMP
);
```

## Implementation Phases

### Phase 1: Connector App Foundation (Week 1-2)

**Goal:** OAuth working, can install app

- [ ] Create Shopify Partner account
- [ ] Create development store with test products
- [ ] Initialize connector app: `npm init @shopify/app@latest`
- [ ] Configure scopes in `shopify.app.toml`
- [ ] Build settings page (embedded)
  - Show shop domain
  - "Not connected to Fibermade" state
  - "Connect Fibermade Account" button
- [ ] Deploy connector app (Shopify hosts for you, or self-host)
- [ ] Test installation flow

**Deliverable:** Can install app, see settings page

### Phase 2: Account Linking (Week 3)

**Goal:** Fibermade ↔ Shopify connection established

- [ ] Build `/api/link` endpoint in connector app
- [ ] Store `fibermade_account_id` when linked
- [ ] Build `/shopify/connect` route in Fibermade
  - Handle signup/login
  - Call connector API to get token
  - Store credentials in `shopify_connections`
- [ ] Update connector settings page to show "Connected" state
- [ ] Test both connection scenarios (existing user + new user)

**Deliverable:** Can link accounts, see connection status

### Phase 3: Product Sync - Read Only (Week 4-5)

**Goal:** See Shopify products in Fibermade

- [ ] Build GraphQL client in Fibermade
- [ ] Implement `GetProducts` query with pagination
- [ ] Store products in `shopify_unmapped_products`
- [ ] Build "Unmapped Products" UI in Fibermade
- [ ] Handle product webhook in connector
  - Validate HMAC
  - Forward to Fibermade
- [ ] Fibermade processes product webhooks

**Deliverable:** Can see list of Shopify products needing mapping

### Phase 4: Product Mapping (Week 6-7)

**Goal:** Map Shopify products to Fibermade colorways/bases

- [ ] Build mapping interface
  - Colorway dropdown
  - Base dropdowns for each variant
- [ ] Implement mapping logic
  - Store in `shopify_product_mappings`
  - Write metafields to Shopify via GraphQL
- [ ] Move mapped products out of unmapped list
- [ ] Handle variants properly (multiple bases per colorway)
- [ ] Add "Skip" option for products dyer doesn't want to track

**Deliverable:** Can map a Shopify product to Fibermade catalog

### Phase 5: Order Integration (Week 8-9)

**Goal:** Shopify orders appear in Fibermade dye lists

- [ ] Implement order webhooks in connector
  - `orders/create`
  - `orders/updated` (payment status)
  - `orders/cancelled`
- [ ] Fibermade processes order webhooks
  - Look up mapped products
  - Add to dye lists
  - Reserve inventory
- [ ] Display Shopify orders in dye lists
  - Show order source (Shopify vs wholesale)
  - Link back to Shopify order
- [ ] Handle order status changes (paid → cancelled)

**Deliverable:** Shopify orders automatically added to dye lists

### Phase 6: Inventory Sync (Week 10-11)

**Goal:** Inventory reconciliation updates Shopify

- [ ] When dyer reconciles order in Fibermade:
  - Calculate inventory delta
  - Call `inventoryAdjustQuantities` mutation
  - Update Shopify inventory at primary location
- [ ] Handle inventory webhook from Shopify (optional)
  - Track external inventory changes
  - Show warning if inventory changed outside Fibermade
- [ ] Add "Sync Inventory" button in Fibermade
  - Manual full sync of all mapped products

**Deliverable:** Reconciling order in Fibermade updates Shopify inventory

### Phase 7: Polish & Beta (Week 12-14)

**Goal:** Ready for first beta users

- [ ] Error handling & retry logic
- [ ] Webhook delivery monitoring
- [ ] Better mapping UX (smart suggestions)
- [ ] Bulk operations (map multiple products)
- [ ] Disconnect/reconnect flow
- [ ] Documentation for beta users
- [ ] Analytics: track sync success rate

**Deliverable:** Ship to 2-3 beta dyers

## Testing Strategy

### Unit Tests
- Metafield reading/writing logic
- Product mapping rules
- Inventory delta calculations
- Webhook signature validation

### Integration Tests
- End-to-end OAuth flow
- Product sync with pagination
- Order webhook → dye list flow
- Inventory reconciliation → Shopify update

### Manual Testing Checklist

**Installation:**
- [ ] Install from Shopify App Store (unlisted link)
- [ ] OAuth completes successfully
- [ ] Connector settings page loads
- [ ] Can connect existing Fibermade account
- [ ] Can create new Fibermade account during connection

**Product Sync:**
- [ ] Initial sync fetches all products
- [ ] Products without metafields go to unmapped list
- [ ] Can map product to colorway
- [ ] Can map variants to bases
- [ ] Metafields written to Shopify correctly
- [ ] Creating product in Shopify triggers webhook
- [ ] New product appears in unmapped list

**Orders:**
- [ ] Creating unpaid order in Shopify → no action
- [ ] Marking order as paid → appears in Fibermade dye list
- [ ] Order line items map to correct colorway/base
- [ ] Cancelling order removes from dye list
- [ ] Fibermade shows order source (Shopify icon)

**Inventory:**
- [ ] Reconciling wholesale order updates Fibermade only
- [ ] Reconciling Shopify order updates both Fibermade and Shopify
- [ ] Inventory quantities match after sync
- [ ] Manual inventory adjustment in Shopify triggers webhook (optional)

**Edge Cases:**
- [ ] Reconnecting after token expires
- [ ] Installing on store with 100+ products
- [ ] Mapping product with 10+ variants
- [ ] Deleting mapped product in Shopify
- [ ] Uninstalling app from Shopify

## Open Questions & Decisions Needed

### Product Creation Direction

**Decision needed:** When dyer wants to add a new colorway to their catalog:

**Option A: Fibermade → Shopify (Recommended for Stage 1)**
- Dyer creates colorway+bases in Fibermade
- Fibermade creates product in Shopify with metafields
- Single source of truth: Fibermade

**Option B: Shopify → Fibermade**
- Dyer creates product in Shopify
- Maps it in Fibermade
- More flexible but requires mapping every time

**Option C: Both**
- Support both workflows
- Most flexible but most complex

**Recommendation:** Start with Option A for Stage 1 beta. Add Option B later if users demand it.

### Multi-Location Support

Shopify supports multiple inventory locations. Most small yarn dyers use single location.

**Decision:** Stage 1 assumes single location. Store `location_id` in `shopify_connections`. Phase 2+ can add multi-location support.

### Inventory Sync Frequency

**Options:**
1. **Real-time:** Every Fibermade change immediately updates Shopify
2. **Batch:** Sync every X minutes or on-demand
3. **Manual:** Dyer clicks "Sync to Shopify" when ready

**Recommendation:** Start with manual sync (option 3), add automatic sync after validating the flow works reliably.

### Shopify as Source of Truth for What?

**Fibermade owns:**
- Wholesale orders
- Production batches
- Dye lists
- Inventory truth (actual quantities)

**Shopify owns:**
- Product presentation (titles, descriptions, images)
- Retail orders (checkout, payment)
- Customer data

**Shared/Synced:**
- Inventory availability (Fibermade truth → Shopify display)
- Product/variant existence (must stay in sync)

## Deployment

### Connector App Hosting

**Option 1: Shopify Spin (Recommended for MVP)**
- Shopify's hosting platform (free tier available)
- Automatic deployment from git
- Built-in tunnel for local development
- Handles SSL, scaling

**Option 2: Self-hosted**
- Laravel Forge, Cloudways, etc.
- More control, more complexity
- Need to manage SSL, domain, scaling

**Recommendation:** Use Shopify Spin initially, migrate to self-hosted if needed later.

### Fibermade Changes

- Deploy new routes/controllers for Shopify integration
- Add database migrations for new tables
- Ensure firewall allows connector app webhook IPs
- Set up monitoring for webhook failures

## Security Considerations

1. **Webhook Validation:**
   - Always verify HMAC signature from Shopify
   - Use timing-safe comparison for secrets
   - Reject webhooks with invalid signatures

2. **Token Storage:**
   - Encrypt access tokens at rest
   - Never log tokens
   - Rotate webhook secrets periodically

3. **API Rate Limits:**
   - Shopify has rate limits (40 req/sec for GraphQL)
   - Implement exponential backoff on 429 responses
   - Batch operations where possible

4. **GDPR Compliance:**
   - Handle customer data deletion webhooks
   - Provide data export for customers
   - Document data retention policies

## Success Metrics

**Technical:**
- OAuth success rate > 95%
- Webhook delivery success > 99%
- Product sync accuracy: 100%
- Inventory sync accuracy: 100%

**User Experience:**
- Time to complete initial setup < 10 minutes
- Products mapped per hour > 20
- Dyer uses Fibermade as primary tool (not Shopify admin)

**Business:**
- 5 beta users successfully connected
- Orders flowing from Shopify → dye lists
- Inventory reconciliation working reliably
- Zero critical bugs after 2 weeks of beta

## Future Enhancements (Post-Stage 1)

- **Smart product matching** - Auto-suggest mappings based on naming
- **Bulk operations** - Map 10+ products at once
- **Product creation** - Create Shopify products from Fibermade
- **Collection sync** - Map Fibermade collections to Shopify collections
- **Image sync** - Push colorway photos to Shopify
- **Multi-location** - Support multiple inventory locations
- **Automatic inventory sync** - Real-time updates instead of manual
- **Order fulfillment** - Mark orders fulfilled in Shopify from Fibermade
- **Analytics** - Show sales data from Shopify in Fibermade

## Resources

**Shopify Documentation:**
- GraphQL Admin API: https://shopify.dev/docs/api/admin-graphql
- App Bridge: https://shopify.dev/docs/apps/tools/app-bridge
- Webhooks: https://shopify.dev/docs/apps/build/webhooks
- Metafields: https://shopify.dev/docs/apps/custom-data/metafields

**Shopify App Template:**
- Remix template: https://github.com/Shopify/shopify-app-template-remix
- CLI docs: https://shopify.dev/docs/apps/tools/cli

**Laravel Packages:**
- Shopify PHP SDK: https://github.com/Shopify/shopify-php-api
- Alternative: osiset/laravel-shopify (more Laravel-specific)

## Contact & Questions

As you work through implementation, track:
- Unexpected API behaviors
- UX friction points in mapping flow
- Beta user feedback on connection process
- Performance bottlenecks

This document will evolve as we learn from real usage.