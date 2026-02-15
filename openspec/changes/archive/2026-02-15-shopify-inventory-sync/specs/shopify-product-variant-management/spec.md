## ADDED Requirements

### Requirement: Create Shopify product from Colorway
The system SHALL create complete Shopify products with all required fields when new Colorway is pushed.

#### Scenario: Create product with all fields
- **WHEN** system creates Shopify product for Colorway
- **THEN** product includes:
  - title from Colorway.name
  - descriptionHtml from Colorway.description
  - productType = "Yarn"
  - vendor from Account.name
  - tags from Colorway.colors + Colorway.technique
  - status mapped from Colorway.status
- **AND** creates Colorway→Product ExternalIdentifier

#### Scenario: Create product with images
- **WHEN** creating Shopify product
- **AND** Colorway has images
- **THEN** system uploads primary image first
- **AND** uploads additional images in order
- **AND** creates Media→Image ExternalIdentifiers

#### Scenario: Create product with metafields
- **WHEN** creating Shopify product
- **THEN** system creates metafield:
  - namespace: "fibermade"
  - key: "per_pan"
  - value: Colorway.per_pan
  - type: "number_integer"

### Requirement: Create variants for all account bases
The system SHALL create Shopify variants for every Base in the account when creating a new product.

#### Scenario: Create all variants
- **WHEN** creating Shopify product
- **THEN** system finds all Bases for account
- **AND** creates variant for each Base:
  - option1 = Base.descriptor
  - price = Base.retail_price
  - inventoryQuantity = Inventory.quantity (or 0 if not exists)
- **AND** creates Inventory→Variant ExternalIdentifiers

#### Scenario: Create variants including zero quantity
- **WHEN** creating product variants
- **AND** some bases have no inventory (quantity = 0)
- **THEN** system still creates variants for those bases
- **AND** sets inventoryQuantity = 0

### Requirement: Update Shopify product fields
The system SHALL update specific Shopify product fields without recreating the product.

#### Scenario: Update product title
- **WHEN** Colorway.name changes
- **THEN** system calls Shopify productUpdate mutation
- **AND** updates only title field
- **AND** preserves other product fields

#### Scenario: Update product description
- **WHEN** Colorway.description changes
- **THEN** system updates descriptionHtml
- **AND** converts plaintext to HTML if needed

#### Scenario: Update product tags
- **WHEN** Colorway.colors or technique changes
- **THEN** system rebuilds tags array
- **AND** updates product tags

#### Scenario: Update product status
- **WHEN** Colorway.status changes
- **THEN** system maps to Shopify status
- **AND** updates product status
- **AND** affects product visibility in Shopify storefront

### Requirement: Update Shopify variant fields
The system SHALL update variant-specific fields when Base or Inventory changes.

#### Scenario: Update variant price
- **WHEN** Base.retail_price changes
- **THEN** system finds all variants using that base
- **AND** updates price for each variant
- **AND** logs bulk price update

#### Scenario: Update variant option
- **WHEN** Base.descriptor changes
- **THEN** system finds all variants using that base
- **AND** updates option1 value for each variant

#### Scenario: Update variant inventory
- **WHEN** Inventory.quantity changes
- **THEN** system calls inventorySetQuantities mutation
- **AND** updates inventoryQuantity for specific variant

### Requirement: Delete Shopify variants
The system SHALL remove variants from Shopify products when Bases are deleted.

#### Scenario: Delete single variant
- **WHEN** Base is deleted
- **AND** product has variant using that base
- **THEN** system calls productVariantDelete mutation
- **AND** removes variant from product
- **AND** deletes Inventory→Variant ExternalIdentifier

#### Scenario: Cannot delete last variant
- **WHEN** attempting to delete last variant in product
- **THEN** Shopify API returns error
- **AND** system logs error
- **AND** suggests archiving product instead

### Requirement: Delete Shopify products
The system SHALL archive or delete Shopify products when Colorways are deleted or retired.

#### Scenario: Colorway retired
- **WHEN** Colorway.status changes to Retired
- **THEN** system updates Shopify product status to ARCHIVED
- **AND** product hidden from storefront
- **AND** product data retained in Shopify

#### Scenario: Colorway deleted (soft delete)
- **WHEN** Colorway is soft-deleted in Fibermade
- **THEN** system archives Shopify product (not delete)
- **AND** maintains ExternalIdentifier for potential restore

### Requirement: Handle Shopify API errors
The system SHALL handle Shopify GraphQL API errors gracefully with appropriate retry logic.

#### Scenario: Rate limit error
- **WHEN** Shopify returns rate limit error (429)
- **THEN** system waits for rate limit reset time
- **AND** retries operation
- **AND** logs rate limit hit

#### Scenario: Invalid product data
- **WHEN** Shopify returns validation error
- **THEN** system logs error with details
- **AND** creates notification for creator
- **AND** does not retry (requires data fix)

#### Scenario: Network timeout
- **WHEN** Shopify API request times out
- **THEN** system retries up to 3 times
- **AND** uses exponential backoff
- **AND** logs timeout to IntegrationLog

#### Scenario: Product not found
- **WHEN** attempting to update product
- **AND** Shopify returns product not found
- **THEN** system logs error
- **AND** removes ExternalIdentifier (orphaned)
- **AND** suggests recreating product on next push

### Requirement: Batch operations for efficiency
The system SHALL batch multiple variant operations into single API calls when possible.

#### Scenario: Bulk variant creation
- **WHEN** creating product with 10+ variants
- **THEN** system uses productCreate with variants array
- **AND** creates all variants in single API call
- **AND** reduces API usage

#### Scenario: Bulk inventory updates
- **WHEN** updating inventory for multiple variants
- **THEN** system batches inventorySetQuantities calls
- **AND** groups by inventory location
- **AND** reduces API requests

### Requirement: Sync operation logging
The system SHALL log all product and variant operations to IntegrationLog for debugging and audit.

#### Scenario: Log successful product creation
- **WHEN** product created successfully
- **THEN** IntegrationLog includes:
  - operation: "product_create"
  - status: "success"
  - shopify_product_id
  - colorway_id
  - variant_count
  - timestamp

#### Scenario: Log failed variant update
- **WHEN** variant update fails
- **THEN** IntegrationLog includes:
  - operation: "variant_update"
  - status: "error"
  - error_message
  - shopify_variant_id
  - inventory_id
  - retry_count
