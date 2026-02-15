## ADDED Requirements

### Requirement: Import pulls actual inventory quantities
The system SHALL pull actual inventory quantities from Shopify during initial product import, not hardcode to 0.

#### Scenario: Import CSV with inventory quantities
- **WHEN** ImportService processes Shopify products CSV
- **AND** variant row includes inventory_quantity field
- **THEN** system creates Inventory record with quantity from CSV
- **AND** does not default to 0

#### Scenario: Import from Shopify API with quantities
- **WHEN** ImportService fetches products from Shopify GraphQL API
- **THEN** system retrieves inventoryQuantity for each variant
- **AND** creates Inventory record with actual quantity

### Requirement: Import creates Inventory to Variant mapping
The system SHALL create ExternalIdentifier records linking Inventory to Shopify variant_id during import.

#### Scenario: Import creates Inventory ExternalIdentifier
- **WHEN** ImportService processes variant row
- **THEN** system creates or finds Inventory record for Colorway × Base
- **AND** creates ExternalIdentifier with identifiable_type='Inventory'
- **AND** stores variant_id as external_id with external_type='shopify_variant'

#### Scenario: Import requires variant_id
- **WHEN** ImportService processes variant without variant_id
- **THEN** system logs error indicating missing variant_id
- **AND** continues processing other variants
- **AND** does not create Inventory ExternalIdentifier for that variant

### Requirement: Import deduplicates shared bases
The system SHALL create one shared Base per unique descriptor+weight combination across all products.

#### Scenario: Multiple products with same base
- **WHEN** importing products with same base descriptor (e.g., "Fingering")
- **THEN** system creates only one Base "Fingering"
- **AND** reuses that Base for all Colorway × Base Inventory records
- **AND** creates unique Inventory→Variant ExternalIdentifiers for each product

#### Scenario: Base already exists
- **WHEN** importing variant with base descriptor matching existing Base
- **THEN** system finds existing Base
- **AND** does not create duplicate Base
- **AND** creates Inventory record using existing Base

### Requirement: Import handles base price conflicts
The system SHALL use first encountered price when same base has different prices across products, and log warnings.

#### Scenario: First price wins
- **WHEN** importing first product with base "Fingering" at $28
- **AND** later product has base "Fingering" at $32
- **THEN** system creates Base with retail_price=$28
- **AND** logs warning about price conflict
- **AND** warning includes both products and price difference

#### Scenario: Price conflict warning details
- **WHEN** base price conflict occurs during import
- **THEN** warning log includes:
  - Base descriptor and weight
  - First product name and price
  - Conflicting product name and price
  - Recommendation to review and adjust manually

### Requirement: Import removes incorrect Base ExternalIdentifiers
The system SHALL NOT create Base→Variant ExternalIdentifiers (bases are shared, variants are not).

#### Scenario: Import creates Inventory mapping only
- **WHEN** ImportService processes variant
- **THEN** system creates Inventory→Variant ExternalIdentifier
- **AND** does not create Base→Variant ExternalIdentifier

#### Scenario: Import cleans up legacy Base ExternalIdentifiers
- **WHEN** ImportService runs on account with existing Base→Variant ExternalIdentifiers
- **THEN** system logs warning about legacy mappings
- **AND** recommends manual cleanup

### Requirement: Import creates Colorway to Product mapping
The system SHALL create ExternalIdentifier records linking Colorway to Shopify product_id during import.

#### Scenario: Import creates Colorway ExternalIdentifier
- **WHEN** ImportService processes product row
- **THEN** system creates or finds Colorway for product
- **AND** creates ExternalIdentifier with identifiable_type='Colorway'
- **AND** stores product_id as external_id with external_type='shopify_product'

### Requirement: Import validation and error handling
The system SHALL validate import data and handle errors gracefully without blocking entire import.

#### Scenario: Invalid CSV row skipped
- **WHEN** CSV row has missing required fields (handle, title, or option1_value)
- **THEN** system logs warning with row number
- **AND** continues processing remaining rows
- **AND** includes skipped row count in import summary

#### Scenario: Import summary includes warnings
- **WHEN** import completes with price conflicts or validation errors
- **THEN** import result includes warnings array
- **AND** each warning specifies issue type and affected data
- **AND** UI displays warnings to user for review
