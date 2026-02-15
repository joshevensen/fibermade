## ADDED Requirements

### Requirement: Fibermade wins on manual push
The system SHALL treat Fibermade as source of truth when creator manually pushes inventory, overwriting Shopify quantities.

#### Scenario: Manual push overwrites Shopify
- **WHEN** creator manually pushes inventory to Shopify
- **AND** Shopify has different quantity for variant
- **THEN** system overwrites Shopify quantity with Fibermade quantity
- **AND** logs the overwrite to IntegrationLog

### Requirement: Shopify wins on webhook pull
The system SHALL accept Shopify inventory updates from webhooks without conflict detection.

#### Scenario: Webhook updates Fibermade
- **WHEN** Shopify sends inventory webhook
- **THEN** system updates Fibermade quantity to match Shopify
- **AND** does not check for conflicts
- **AND** logs update source as "webhook"

### Requirement: Detect conflicting updates
The system SHALL detect when both systems have changed inventory for same variant since last sync.

#### Scenario: Both systems changed quantity
- **WHEN** Fibermade quantity changed locally
- **AND** Shopify webhook arrives with different quantity
- **AND** both changes occurred after last sync
- **THEN** system identifies this as potential conflict
- **AND** logs both values to IntegrationLog

### Requirement: Track last sync timestamp
The system SHALL track last successful sync timestamp for each Inventory record to enable conflict detection.

#### Scenario: Sync updates timestamp
- **WHEN** inventory successfully syncs (push or pull)
- **THEN** system updates Inventory.last_synced_at timestamp
- **AND** records sync direction (push/pull) in IntegrationLog

#### Scenario: Conflict within sync window
- **WHEN** Fibermade quantity updated after last_synced_at
- **AND** webhook arrives within 60 seconds of last sync
- **THEN** system considers it likely same update (not conflict)
- **AND** accepts webhook value without warning

#### Scenario: Conflict outside sync window
- **WHEN** Fibermade quantity updated > 5 minutes after last sync
- **AND** webhook arrives with different value
- **THEN** system flags potential conflict
- **AND** logs warning for manual review

### Requirement: Prevent sync loops
The system SHALL prevent infinite loops where push triggers webhook which triggers push.

#### Scenario: Push does not process resulting webhook
- **WHEN** system pushes inventory to Shopify
- **AND** Shopify sends webhook for that change
- **THEN** system recognizes update originated from push
- **AND** does not trigger another push
- **AND** logs webhook as "echo" in IntegrationLog

#### Scenario: Sync source tracking
- **WHEN** any sync operation occurs
- **THEN** system records sync source (manual_push, webhook, observer)
- **AND** includes source in IntegrationLog
- **AND** uses source to prevent loop triggers

### Requirement: Conflict notification
The system SHALL notify creators when genuine conflicts are detected that require manual review.

#### Scenario: Conflict creates notification
- **WHEN** system detects genuine conflict (both systems changed)
- **THEN** creates notification for account owner
- **AND** notification includes:
  - Colorway and Base names
  - Fibermade quantity
  - Shopify quantity
  - Last sync timestamp
- **AND** links to inventory page for resolution

#### Scenario: Auto-resolve conflicts when possible
- **WHEN** conflict detected
- **AND** one change is very recent (< 1 minute)
- **AND** other change is older (> 1 hour)
- **THEN** system accepts recent change as authoritative
- **AND** logs auto-resolution decision

### Requirement: Conflict resolution UI
The system SHALL provide UI for creators to manually resolve detected conflicts.

#### Scenario: View conflict details
- **WHEN** creator views conflicting Inventory record
- **THEN** UI displays:
  - Current Fibermade quantity
  - Last Shopify quantity from webhook
  - Timestamps of both changes
  - Sync history from IntegrationLog

#### Scenario: Choose winning value
- **WHEN** creator selects Fibermade value as correct
- **THEN** system pushes quantity to Shopify
- **AND** marks conflict as resolved

#### Scenario: Choose Shopify value
- **WHEN** creator selects Shopify value as correct
- **THEN** system updates Fibermade quantity
- **AND** marks conflict as resolved
- **AND** does not trigger reverse sync
