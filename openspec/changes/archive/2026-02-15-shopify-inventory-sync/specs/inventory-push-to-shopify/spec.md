## ADDED Requirements

### Requirement: Manual inventory push button
The system SHALL provide a "Push to Shopify" button at the top of the inventory index page that syncs all Fibermade inventory quantities to Shopify.

#### Scenario: Button displays on inventory index page
- **WHEN** creator views the inventory index page
- **THEN** a "Push to Shopify" button is visible at the top of the page

#### Scenario: Successful push
- **WHEN** creator clicks "Push to Shopify" button
- **THEN** system syncs all inventory quantities to Shopify
- **AND** displays success message indicating number of variants updated

#### Scenario: Push with Shopify API error
- **WHEN** creator clicks "Push to Shopify" button
- **AND** Shopify API returns an error
- **THEN** system displays error message with details
- **AND** logs error to IntegrationLog

### Requirement: Push updates existing variants
The system SHALL update Shopify variant inventory quantities for colorways that already have Shopify products.

#### Scenario: Push existing colorway inventory
- **WHEN** creator pushes inventory for colorway with existing Shopify product
- **THEN** system finds Inventory→Variant ExternalIdentifiers
- **AND** calls Shopify inventorySetQuantities mutation for each variant
- **AND** updates variant quantity to match Fibermade inventory

### Requirement: Push creates missing variants
The system SHALL create Shopify variants for bases that don't have corresponding variants in existing products.

#### Scenario: Push detects missing variant
- **WHEN** creator pushes inventory for colorway with Shopify product
- **AND** new base was added after initial sync
- **THEN** system detects missing variant for that base
- **AND** creates variant in Shopify with base descriptor as option1
- **AND** creates Inventory→Variant ExternalIdentifier
- **AND** sets inventory quantity from Fibermade

### Requirement: Push creates new products
The system SHALL create complete Shopify products with all variants when pushing colorways that don't have Shopify products yet.

#### Scenario: Push new colorway to Shopify
- **WHEN** creator pushes inventory for colorway without Shopify product
- **THEN** system creates Shopify product with colorway fields
- **AND** creates variants for ALL account bases (including qty=0)
- **AND** creates Colorway→Product ExternalIdentifier
- **AND** creates Inventory→Variant ExternalIdentifiers for all variants
- **AND** syncs primary image to Shopify

#### Scenario: Push includes all bases
- **WHEN** creator pushes new colorway with only some bases having inventory > 0
- **THEN** system creates variants for ALL account bases
- **AND** sets quantities from Fibermade (including 0 for bases without inventory)

### Requirement: Authorization for inventory push
The system SHALL restrict inventory push to authorized creators only.

#### Scenario: Authorized creator can push
- **WHEN** authenticated creator with account_id clicks "Push to Shopify"
- **AND** creator has permission to manage inventory
- **THEN** system executes the push operation

#### Scenario: Unauthorized user cannot push
- **WHEN** unauthenticated user attempts to push inventory
- **THEN** system returns 401 Unauthorized error

### Requirement: Push logs integration events
The system SHALL log all push operations to IntegrationLog for audit and debugging.

#### Scenario: Successful push logged
- **WHEN** push operation completes successfully
- **THEN** system creates IntegrationLog entry with status "success"
- **AND** logs number of variants updated/created

#### Scenario: Failed push logged
- **WHEN** push operation fails
- **THEN** system creates IntegrationLog entry with status "error"
- **AND** logs error message and affected colorways
