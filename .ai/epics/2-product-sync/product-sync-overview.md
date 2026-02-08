# Epic 2: Product Sync

## Goal

Bidirectional product sync between Shopify and Fibermade. End state: products created in Shopify appear as Colorways/Bases in Fibermade, products created in Fibermade appear as products in Shopify, and changes in either system propagate to the other.

## Current State

- **Epic 0 complete.** Platform API has CRUD endpoints for Colorways, Bases, Collections, Inventory, and ExternalIdentifiers.
- **Epic 1 complete.** Shopify app is installed, authenticated, and linked to a Fibermade account. A `FibermadeClient` service exists for API calls. An Integration record connects the Shopify store to the Fibermade account.
- **Shopify app has GraphQL access.** The `@shopify/shopify-app-react-router` package provides authenticated Admin API access. API version is 2026-04.
- **ExternalIdentifier model ready.** Maps Shopify GIDs to Fibermade model IDs. Supports lookup by `integration_id + external_type + external_id`.
- **Media model exists.** Polymorphic media attachments with `file_path`, `is_primary`, and `metadata` fields. Colorway has a `primary_image_url` accessor.
- **No sync services exist.** No product mapping logic, no metafield handling, no webhook handlers for product events.

## What This Epic Delivers

By the end of this epic:
- A ProductSyncService that maps Shopify products to Colorways and variants to Bases (via Inventory records)
- Metafield writes to Shopify products (`fibermade.colorway_id`) and variants (`fibermade.base_id`) for Shopify-side mapping
- ExternalIdentifier records for Fibermade-side reverse lookup
- Image sync from Shopify product images to the Media table
- Collection mapping from Shopify collections to Fibermade Collections
- An initial bulk import flow that imports all existing products on first connect
- Webhook handlers for `products/create`, `products/update`, `products/delete` for real-time sync
- Fibermade → Shopify product push: creating Shopify products from Fibermade Colorways via GraphQL

## What This Epic Does NOT Do

- No inventory sync (that's Epic 9)
- No order or customer sync (that's Epic 10)
- No sync preferences UI (that's Epic 5)
- No conflict resolution strategy -- last-write-wins for now
- No bulk operations via GraphQL bulk API -- individual mutations are fine for Stage 1 volumes

## Stories

### Story 2.1: Product Mapping & ExternalIdentifier Setup

Define the mapping strategy and build the foundational mapping utilities that all sync operations will use.

- Define external_type constants: `shopify_product`, `shopify_variant`, `shopify_collection`
- Create mapping utility functions: lookup Fibermade model by Shopify GID, lookup Shopify GID by Fibermade model, check if mapping exists
- These utilities wrap the ExternalIdentifier API endpoints (`POST /api/v1/external-identifiers`, `GET /api/v1/external-identifiers?...`)
- Define the Shopify metafield namespace and keys: `fibermade.colorway_id`, `fibermade.base_id`

### Story 2.2: Shopify → Fibermade Product Import

Build the core product import flow: read a Shopify product and create corresponding Colorway + Base + Inventory records in Fibermade.

- Create a `ProductSyncService` in the shopify app
- Map Shopify product fields to Colorway fields (title → name, body_html → description, status → status)
- Map Shopify variant fields to Base fields (title → descriptor, sku → code, price → retail_price, weight → weight)
- For each variant, create an Inventory record linking the Colorway and Base
- Write metafields back to the Shopify product/variants (`fibermade.colorway_id`, `fibermade.base_id`)
- Create ExternalIdentifier records for each mapping
- Handle product images: download from Shopify, upload to Fibermade Media via the platform (or store URLs)
- Log sync operations to IntegrationLog via the API

### Story 2.3: Initial Bulk Import

When a merchant first connects, import all their existing Shopify products into Fibermade.

- On first connect (after Epic 1 account linking), trigger a full product import
- Fetch all products from Shopify using GraphQL pagination
- Run each product through the ProductSyncService from Story 2.2
- Track progress and surface errors
- Handle large catalogs gracefully (queue processing, progress indication)
- Mark the initial import as complete so it doesn't re-run

### Story 2.4: Shopify Product Webhooks

Handle real-time product changes from Shopify via webhooks.

- Register webhook subscriptions: `products/create`, `products/update`, `products/delete`
- `products/create`: run the product through ProductSyncService (same as import)
- `products/update`: find existing Colorway via ExternalIdentifier, update fields, sync new/changed variants
- `products/delete`: find existing Colorway via ExternalIdentifier, mark as retired (soft-delete or status change)
- Webhook handlers must be idempotent (same webhook delivered twice produces same result)
- Validate webhook HMAC signatures (already handled by Shopify app framework)

### Story 2.5: Fibermade → Shopify Product Push

Create Shopify products from Fibermade Colorways. This enables the bidirectional flow.

- When a Colorway is created/updated in Fibermade without a Shopify mapping, push it to Shopify
- Create Shopify product via GraphQL `productCreate` mutation with metafields included
- Create variants for each Base that has Inventory with this Colorway
- Create ExternalIdentifier records for the new Shopify product/variants
- This flow will be triggered from the embedded app UI (e.g., a "Push to Shopify" action) -- not automatic in Stage 1

### Story 2.6: Collection Sync

Map Shopify collections to Fibermade Collections.

- Fetch Shopify custom collections and smart collections
- Create corresponding Fibermade Collection records via the API
- Map collection membership (which products are in which collections) to the colorway-collection relationship
- Create ExternalIdentifier records for collection mappings
- Handle collection webhooks: `collections/create`, `collections/update`, `collections/delete`
