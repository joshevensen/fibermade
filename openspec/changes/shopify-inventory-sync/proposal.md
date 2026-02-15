**Sub-project**: both

## Why

Shopify's inventory management doesn't provide the production-aware tracking that independent dyers need. Fibermade maintains the truth about physical yarn quantities for Colorway + Base combinations, but these quantities need to sync bidirectionally with Shopify to keep shops accurate and prevent overselling. This change enables dyers to push their Fibermade inventory to Shopify and receive updates when Shopify inventory changes (e.g., sales, manual adjustments).

**Sync Philosophy**: Fibermade is the source of truth for catalog management (Colorways, Bases, Collections). Dyers manage these fully in Fibermade, and Fibermade aggressively keeps Shopify synchronized. The only expected changes originating from Shopify are inventory quantity updates from retail orders, though we must defensively handle manual Shopify edits.

## What Changes

**Initial Import from Shopify**:
- Update ImportService to pull actual inventory quantities from Shopify (currently hardcoded to 0)
- Create Inventory → Variant ExternalIdentifiers (not Base → Variant) to enable bidirectional sync
- Implement base deduplication: multiple Shopify products with "Fingering" variants → one shared "Fingering" Base in Fibermade
- Handle base price conflicts: when same base descriptor has different prices across products, use first encountered price and log warnings for manual review
- Require variant_id from CSV or Shopify API to properly map Inventory records to specific Shopify variants

**Manual Inventory Sync**:
- Add "Push to Shopify" button at the top of inventory index page to sync Fibermade quantities to Shopify
- Update InventoryController to support manual push action
- Update InventoryIndexPage.vue to include push button and sync status feedback

**Automatic Catalog Sync**:
- When new Base added to account: immediately create variants for all Shopify products
- When Base deleted: delete all variants using that base from all Shopify products
- When Base descriptor/price changed: update all Shopify variants using that base
- When Colorway name changed: update Shopify product title
- When Colorway fields change (description, status, colors, technique): update Shopify product fields
- When Colorway images change: sync images to Shopify (is_primary determines primary image)

**Sync Infrastructure**:
- Create InventorySyncService to handle bidirectional sync logic
- Add Shopify webhook handler for `inventory_levels/update` events to pull changes from Shopify into Fibermade
- Implement conflict resolution strategy (Fibermade is source of truth for manual pushes, Shopify updates are accepted unless they conflict with recent Fibermade changes)
- Add sync loop prevention (changes originating from sync don't trigger reverse sync)
- Map Inventory records to Shopify variant IDs via ExternalIdentifier (external_type: `shopify_variant`)
- Add integration logs for sync operations (success, failure, conflicts)

**Shopify Product/Variant Structure**:
- One Shopify product per Colorway (1:1 mapping via ExternalIdentifier)
- One Shopify variant per Inventory record (Colorway × Base combination)
- Create variants for ALL account bases (even if quantity=0) to maintain complete product structure
- Detect and create missing variants when pushing (handles bases added after initial sync)
- Map Colorway fields → Shopify product fields (title, description, tags, status, images)
- Store per_pan as Shopify metafield for future reference
- Use Base retail_price for variant pricing

## Capabilities

### New Capabilities
- `inventory-push-to-shopify`: Manual push of Fibermade inventory quantities to Shopify variants
- `inventory-pull-from-shopify`: Automated pull of Shopify inventory updates via webhooks into Fibermade
- `inventory-import-from-shopify`: Pull inventory quantities during initial product import from Shopify
- `inventory-sync-conflict-resolution`: Strategy for handling conflicts when both systems have changed
- `catalog-sync-to-shopify`: Automatic sync of Colorways, Bases, and Collections to Shopify products/variants
- `shopify-product-variant-management`: Create/update/delete Shopify products and variants from Fibermade changes

### Modified Capabilities
<!-- No existing capabilities are being modified -->

## Impact

**Platform (Laravel)**:
- `InventoryController`: Add `pushToShopify()` method for manual sync action
- `ImportService`: 
  - Pull actual inventory quantities from Shopify (not 0)
  - Create Inventory → Variant ExternalIdentifiers (change from Base → Variant)
  - Implement base deduplication across products
  - Detect and warn on base price conflicts
  - Require variant_id for proper Inventory mapping
- New `InventorySyncService`: Core sync logic for push/pull operations
- New `ShopifySyncService`: Handle Shopify API mutations (create/update/delete products, variants, images)
- New `ShopifyWebhookController`: Handle `inventory_levels/update` webhook
- `Inventory` model: May need methods for sync state tracking
- `Colorway` model: Add observer to trigger Shopify sync on changes (name, description, status, colors, technique)
- `Base` model: Add observer to trigger Shopify sync on changes (descriptor, retail_price) and handle creation/deletion
- `Media` model: Add observer to trigger image sync when colorway images change
- `ExternalIdentifier`: 
  - Map Inventory → Shopify variant IDs (unique per Colorway × Base combination)
  - Map Colorway → Shopify product IDs
  - Remove Base → Variant mapping (incorrect for shared bases)
- `IntegrationLog`: Track sync operations, price conflicts, and outcomes
- Routes: Add POST route for manual push action, webhook endpoint

**Shopify App (TypeScript/React)**:
- May need Shopify app extension to register webhooks
- API integration for GraphQL inventory mutations

**Database**:
- Possibly new columns on `inventories` table for sync metadata (last_synced_at, sync_status)
- ExternalIdentifier records linking:
  - Colorway → Shopify product ID (1:1 mapping)
  - Inventory (Colorway × Base) → Shopify variant ID (1:1 mapping, unique per product)
  - Note: Base → Variant mapping should NOT exist (bases are shared across products)
- Need to handle Inventory record creation for all Colorway × Base combinations when new Base is added
- Import warnings table or log for price conflicts and other data integrity issues

**Dependencies**:
- Shopify GraphQL Admin API for:
  - Inventory mutations (inventorySetQuantities)
  - Product mutations (productCreate, productUpdate, productDelete)
  - Variant mutations (productVariantCreate, productVariantUpdate, productVariantDelete)
  - Image mutations (productImageCreate, productImageUpdate)
  - Metafield mutations (for per_pan storage)
- Webhook signature verification
- Laravel Model Observers for triggering automatic syncs
