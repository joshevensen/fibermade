## ADDED Requirements

### Requirement: Shopify inventory webhook handler
The system SHALL handle Shopify `inventory_levels/update` webhook events to pull inventory changes into Fibermade.

#### Scenario: Valid webhook received
- **WHEN** Shopify sends inventory_levels/update webhook
- **AND** webhook signature is valid
- **THEN** system processes the webhook
- **AND** updates corresponding Fibermade inventory quantity

#### Scenario: Invalid webhook signature
- **WHEN** Shopify sends inventory_levels/update webhook
- **AND** webhook signature is invalid
- **THEN** system rejects the request with 401 Unauthorized
- **AND** logs security warning to IntegrationLog

### Requirement: Webhook updates Fibermade inventory
The system SHALL update Fibermade Inventory quantities when Shopify variant inventory changes.

#### Scenario: Inventory decreased by sale
- **WHEN** Shopify webhook indicates variant inventory decreased
- **THEN** system finds Inventory via Variant ExternalIdentifier
- **AND** updates Inventory.quantity to match Shopify quantity
- **AND** logs the update to IntegrationLog

#### Scenario: Inventory manually adjusted in Shopify
- **WHEN** dyer manually adjusts variant inventory in Shopify admin
- **AND** Shopify sends webhook with new quantity
- **THEN** system updates Fibermade inventory to match
- **AND** logs the manual adjustment

### Requirement: Webhook identifies correct inventory
The system SHALL use ExternalIdentifier mapping to find the correct Fibermade Inventory record for a Shopify variant.

#### Scenario: Webhook with valid variant_id
- **WHEN** webhook contains variant_id
- **THEN** system queries ExternalIdentifier with external_type='shopify_variant'
- **AND** finds corresponding Inventory record
- **AND** updates that Inventory.quantity

#### Scenario: Webhook with unknown variant_id
- **WHEN** webhook contains variant_id with no ExternalIdentifier
- **THEN** system logs warning to IntegrationLog
- **AND** does not update any inventory
- **AND** continues processing without error

### Requirement: Webhook prevents sync loops
The system SHALL prevent infinite sync loops where webhook triggers push which triggers webhook.

#### Scenario: Update from webhook does not trigger push
- **WHEN** webhook updates Fibermade inventory
- **THEN** system marks update as originating from Shopify
- **AND** does not trigger reverse sync back to Shopify
- **AND** logs update source in IntegrationLog

### Requirement: Webhook handles concurrent updates
The system SHALL handle concurrent webhook events without data loss or race conditions.

#### Scenario: Multiple webhooks for same variant
- **WHEN** multiple webhooks arrive for same variant in quick succession
- **THEN** system processes them in order received
- **AND** final Inventory.quantity matches last webhook value
- **AND** logs all updates to IntegrationLog

### Requirement: Webhook error handling
The system SHALL handle webhook processing errors gracefully without blocking Shopify.

#### Scenario: Database error during webhook processing
- **WHEN** webhook processing encounters database error
- **THEN** system logs error to IntegrationLog
- **AND** returns 500 status to Shopify
- **AND** Shopify retries webhook per their retry policy

#### Scenario: Webhook with invalid payload
- **WHEN** webhook payload is malformed or missing required fields
- **THEN** system logs error with payload details
- **AND** returns 400 Bad Request
- **AND** does not retry processing
