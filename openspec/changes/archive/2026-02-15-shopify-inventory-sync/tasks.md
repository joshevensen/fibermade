## 1. Platform — Database

- [x] 1.1 Add migration for Inventory.last_synced_at timestamp column
- [x] 1.2 Add migration for Inventory.sync_status enum column (optional)
- [x] 1.3 Remove legacy Base→Variant ExternalIdentifiers via data migration
- [x] 1.4 Add index on external_identifiers (integration_id, external_type, external_id) for fast lookups

## 2. Platform — Services (Core Sync Logic)

- [x] 2.1 Create InventorySyncService with pushInventoryToShopify() method
- [x] 2.2 Add pushAllInventoryForColorway() method to InventorySyncService
- [x] 2.3 Add pullInventoryFromShopify() method to InventorySyncService
- [x] 2.4 Implement sync loop prevention logic (track sync source)
- [x] 2.5 Create ShopifySyncService with createProduct() method
- [x] 2.6 Add updateProduct() method to ShopifySyncService
- [x] 2.7 Add createVariant() method to ShopifySyncService
- [x] 2.8 Add updateVariant() method to ShopifySyncService
- [x] 2.9 Add deleteVariant() method to ShopifySyncService
- [x] 2.10 Add syncImages() method to ShopifySyncService
- [x] 2.11 Implement Shopify GraphQL API client methods (productCreate, productUpdate, etc.)
- [x] 2.12 Add retry logic with exponential backoff for API errors
- [x] 2.13 Add rate limit handling (429 responses)

## 3. Platform — Import Service Updates

- [x] 3.1 Update ImportService to pull actual inventory quantities from CSV (not 0)
- [x] 3.2 Modify ImportService to create Inventory→Variant ExternalIdentifiers
- [x] 3.3 Add variant_id extraction from CSV or API during import
- [x] 3.4 Implement base price conflict detection and warning logging
- [x] 3.5 Add import warnings collection and display
- [x] 3.6 Update getOrCreateBaseFromVariant() to handle deduplication
- [x] 3.7 Remove Base→Variant ExternalIdentifier creation logic

## 4. Platform — Model Observers

- [x] 4.1 Create ColorwayObserver with updated() method
- [x] 4.2 Add observer trigger for Colorway.name changes → update Shopify product title
- [x] 4.3 Add observer trigger for Colorway description/status/colors/technique changes
- [x] 4.4 Create BaseObserver with updated() method
- [x] 4.5 Add observer trigger for Base.descriptor changes → update all variants
- [x] 4.6 Add observer trigger for Base.retail_price changes → update all variants
- [x] 4.7 Add observer trigger for Base.created → create variants for all products
- [x] 4.8 Add observer trigger for Base.deleted → delete variants from all products
- [x] 4.9 Create MediaObserver for Colorway image changes
- [x] 4.10 Add observer trigger for image uploads/deletes/primary changes
- [x] 4.11 Queue observer jobs for operations affecting 10+ products
- [x] 4.12 Implement observer error handling (don't block user actions)

## 5. Platform — Controllers

- [x] 5.1 Add pushToShopify() method to InventoryController
- [x] 5.2 Add authorization check for pushToShopify (InventoryPolicy)
- [x] 5.3 Create ShopifyWebhookController for inventory_levels/update
- [x] 5.4 Add webhook signature verification to ShopifyWebhookController
- [x] 5.5 Implement webhook payload parsing and validation
- [x] 5.6 Add webhook error handling (return appropriate HTTP status codes)

## 6. Platform — Routes

- [x] 6.1 Add POST route for inventory.pushToShopify
- [x] 6.2 Add POST route for webhooks/shopify/inventory (public, no auth)
- [x] 6.3 Register webhook route in api.php

## 7. Platform — Frontend (Manual Push Button)

- [x] 7.1 Add "Push to Shopify" button to InventoryIndexPage.vue header
- [x] 7.2 Implement button click handler with loading state
- [x] 7.3 Add success notification display (variants updated count)
- [x] 7.4 Add error notification display with details
- [x] 7.5 Style button with appropriate icon and placement
- [x] 7.6 Add confirmation dialog if pushing 50+ variants

## 8. Platform — Integration Logging

- [x] 8.1 Add IntegrationLog entries for push operations (success/error)
- [x] 8.2 Add IntegrationLog entries for webhook pulls
- [x] 8.3 Add IntegrationLog entries for catalog sync operations
- [x] 8.4 Log sync source (manual_push, webhook, observer) in data column
- [x] 8.5 Add import warnings to IntegrationLog

## 9. Shopify — Webhook Registration

- [x] 9.1 Register inventory_levels/update webhook topic in Shopify app
- [x] 9.2 Configure webhook URL to point to platform webhook endpoint
- [x] 9.3 Add webhook verification in Shopify app settings
- [x] 9.4 Test webhook delivery from Shopify

## 10. Platform — Field Mapping Logic

- [x] 10.1 Implement Colorway→Product field mapping (title, description, vendor, tags, status)
- [x] 10.2 Implement Base→Variant option mapping (option1 = descriptor)
- [x] 10.3 Add Colorway.per_pan metafield creation logic
- [x] 10.4 Implement ColorwayStatus→Shopify status mapping
- [x] 10.5 Add image upload and ordering logic (is_primary first)

## 11. Platform — Conflict Resolution (Basic)

- [x] 11.1 Update Inventory.last_synced_at on successful sync
- [x] 11.2 Track sync direction (push/pull) in IntegrationLog
- [x] 11.3 Implement basic conflict detection (both changed since last sync)
- [x] 11.4 Log conflicts to IntegrationLog with warning severity
- [x] 11.5 Add conflict notification creation (deferred to future iteration)

## 12. Platform — Tests (Import)

- [x] 12.1 Test ImportService pulls actual inventory quantities
- [x] 12.2 Test Inventory→Variant ExternalIdentifier creation
- [x] 12.3 Test base deduplication across products
- [x] 12.4 Test base price conflict detection and warnings
- [x] 12.5 Test import handles missing variant_id gracefully

## 13. Platform — Tests (Manual Push)

- [x] 13.1 Test pushToShopify creates new products for colorways without external_id
- [x] 13.2 Test pushToShopify updates existing variants with correct quantities
- [x] 13.3 Test pushToShopify creates missing variants for new bases
- [x] 13.4 Test pushToShopify creates all account bases (including qty=0)
- [x] 13.5 Test authorization (only account creators can push)
- [x] 13.6 Test IntegrationLog entries created

## 14. Platform — Tests (Webhook Pull)

- [x] 14.1 Test webhook updates Fibermade inventory from Shopify
- [x] 14.2 Test webhook signature verification rejects invalid requests
- [x] 14.3 Test webhook finds correct Inventory via ExternalIdentifier
- [x] 14.4 Test webhook handles unknown variant_id gracefully
- [x] 14.5 Test webhook prevents sync loops
- [x] 14.6 Test webhook error handling (malformed payload, database errors)

## 15. Platform — Tests (Catalog Sync Observers)

- [x] 15.1 Test Colorway.name change updates Shopify product title
- [x] 15.2 Test Colorway field changes update Shopify product
- [x] 15.3 Test Base.descriptor change updates all Shopify variants
- [x] 15.4 Test Base.retail_price change updates all variants
- [x] 15.5 Test Base creation adds variants to all products
- [x] 15.6 Test Base deletion removes variants from all products
- [x] 15.7 Test image changes sync to Shopify
- [x] 15.8 Test observer queues jobs for large operations (10+ products)
- [x] 15.9 Test observer error handling doesn't block saves

## 16. Platform — Tests (Field Mapping)

- [x] 16.1 Test Colorway→Product field mapping correctness
- [x] 16.2 Test ColorwayStatus→Shopify status mapping
- [x] 16.3 Test per_pan metafield creation
- [x] 16.4 Test image ordering (is_primary first)

## 17. Documentation

- [x] 17.1 Document Inventory→Variant ExternalIdentifier structure
- [x] 17.2 Document sync philosophy (Fibermade as source of truth)
- [x] 17.3 Document webhook URL for Shopify app configuration
- [x] 17.4 Document import price conflict handling
- [x] 17.5 Add troubleshooting guide for common sync errors

## 18. Deployment & Configuration

- [x] 18.1 Add Shopify API credentials to environment configuration
- [x] 18.2 Configure webhook secret for signature verification
- [x] 18.3 Set up queue workers for observer jobs
- [x] 18.4 Add feature flag for automatic catalog sync (enable per account)
- [x] 18.5 Configure retry attempts and backoff for Shopify API calls

## 19. Monitoring & Observability

- [x] 19.1 Add logging for Shopify API rate limit usage
- [x] 19.2 Create dashboard for IntegrationLog monitoring
- [x] 19.3 Add alerts for repeated sync failures
- [x] 19.4 Track sync operation metrics (duration, error rate)
