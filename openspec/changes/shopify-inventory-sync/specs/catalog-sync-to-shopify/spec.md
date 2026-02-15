## ADDED Requirements

### Requirement: Auto-sync colorway name changes
The system SHALL automatically update Shopify product title when Colorway name changes in Fibermade.

#### Scenario: Colorway renamed
- **WHEN** creator updates Colorway.name
- **THEN** system triggers Colorway observer
- **AND** finds Shopify product via ExternalIdentifier
- **AND** updates product title via Shopify GraphQL
- **AND** logs sync operation to IntegrationLog

#### Scenario: Colorway without Shopify product
- **WHEN** creator updates Colorway.name
- **AND** colorway has no shopify_product ExternalIdentifier
- **THEN** system does not attempt Shopify sync
- **AND** logs skip reason to IntegrationLog

### Requirement: Auto-sync colorway field changes
The system SHALL automatically update Shopify product fields when Colorway description, status, colors, or technique changes.

#### Scenario: Description updated
- **WHEN** creator updates Colorway.description
- **THEN** system updates Shopify product descriptionHtml

#### Scenario: Status changed
- **WHEN** creator changes Colorway.status
- **THEN** system maps status to Shopify (Active→ACTIVE, Retired→ARCHIVED, Idea→DRAFT)
- **AND** updates Shopify product status

#### Scenario: Colors updated
- **WHEN** creator modifies Colorway.colors array
- **THEN** system updates Shopify product tags
- **AND** includes both colors and technique in tags

#### Scenario: Technique changed
- **WHEN** creator updates Colorway.technique
- **THEN** system includes new technique in Shopify product tags

### Requirement: Auto-sync colorway images
The system SHALL automatically sync Colorway images to Shopify when images are added, removed, or primary image changes.

#### Scenario: Primary image added
- **WHEN** creator uploads image with is_primary=true
- **THEN** system uploads image to Shopify
- **AND** sets it as product's primary image
- **AND** creates Media ExternalIdentifier linking to Shopify image_id

#### Scenario: Primary image changed
- **WHEN** creator marks different image as primary
- **THEN** system reorders Shopify product images
- **AND** new primary image appears first

#### Scenario: Additional images added
- **WHEN** creator uploads non-primary images
- **THEN** system uploads to Shopify in order
- **AND** primary image remains first

#### Scenario: Image removed
- **WHEN** creator deletes Colorway image
- **THEN** system removes corresponding Shopify image
- **AND** deletes Media ExternalIdentifier

### Requirement: Auto-sync base changes to all products
The system SHALL automatically update ALL Shopify products when Base descriptor or retail_price changes.

#### Scenario: Base descriptor renamed
- **WHEN** creator updates Base.descriptor (e.g., "Fingering" → "Sock")
- **THEN** system triggers Base observer
- **AND** finds all Shopify products (via Colorways with shopify_product external_id)
- **AND** updates variant option1 for every product using that base
- **AND** logs bulk update to IntegrationLog

#### Scenario: Base price changed
- **WHEN** creator updates Base.retail_price
- **THEN** system updates variant price for all Shopify products
- **AND** logs number of variants updated

### Requirement: Auto-create variants when base added
The system SHALL automatically create Shopify variants for ALL existing products when new Base is added to account.

#### Scenario: New base added to account
- **WHEN** creator creates new Base
- **THEN** system triggers Base observer
- **AND** finds all Colorways with shopify_product external_id
- **AND** for each colorway:
  - Creates Shopify variant with new base descriptor
  - Sets quantity to 0
  - Creates Inventory record if not exists
  - Creates Inventory→Variant ExternalIdentifier
- **AND** logs bulk variant creation

#### Scenario: Base creation progress feedback
- **WHEN** new base triggers bulk variant creation
- **AND** account has many colorways (50+)
- **THEN** system queues job for background processing
- **AND** displays progress notification to creator
- **AND** notifies when complete

### Requirement: Auto-delete variants when base deleted
The system SHALL automatically delete Shopify variants from ALL products when Base is deleted from account.

#### Scenario: Base deleted
- **WHEN** creator deletes Base
- **THEN** system triggers Base observer
- **AND** finds all Inventory records using that base
- **AND** for each inventory:
  - Finds Shopify variant via ExternalIdentifier
  - Deletes variant from Shopify
  - Deletes Inventory→Variant ExternalIdentifier
  - Soft-deletes Inventory record
- **AND** logs bulk deletion

#### Scenario: Base deletion with recent sales
- **WHEN** creator deletes Base
- **AND** some products have recent sales of that variant
- **THEN** system still deletes variants
- **AND** logs warning about sales history
- **AND** Shopify retains order history

### Requirement: Observer error handling
The system SHALL handle observer sync failures gracefully without blocking user actions.

#### Scenario: Shopify API error during sync
- **WHEN** Colorway observer triggers Shopify update
- **AND** Shopify API returns error
- **THEN** system logs error to IntegrationLog
- **AND** queues retry job
- **AND** allows user action to complete (doesn't block save)

#### Scenario: Observer retry logic
- **WHEN** observer sync fails
- **THEN** system retries up to 3 times with exponential backoff
- **AND** after max retries, creates notification for account owner
- **AND** logs final failure state

### Requirement: Queue observers for large operations
The system SHALL queue observer jobs for operations affecting multiple products to avoid blocking requests.

#### Scenario: Base change affects many products
- **WHEN** Base update would affect > 10 products
- **THEN** system queues background job
- **AND** returns immediately to user
- **AND** displays "Syncing to Shopify..." notification

#### Scenario: Queued job completion
- **WHEN** queued observer job completes
- **THEN** system creates notification with results
- **AND** logs completion to IntegrationLog
- **AND** notification includes success/error count
