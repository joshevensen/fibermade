## ADDED Requirements

### Requirement: Display Pending Orders Count
The system SHALL display the count of wholesale orders awaiting creator acceptance.

#### Scenario: Creator has pending orders
- **WHEN** creator views the dashboard
- **THEN** system displays the count of orders with status "pending" in the Needs Attention card

#### Scenario: Creator has no pending orders
- **WHEN** creator has zero pending orders
- **THEN** system displays "0" or omits the pending orders section from Needs Attention card

### Requirement: Display Pending Store Invites Count
The system SHALL display the count of store relationship invitations awaiting creator response.

#### Scenario: Creator has pending store invites
- **WHEN** creator views the dashboard
- **THEN** system displays the count of store relationships with status "invited" in the Needs Attention card

#### Scenario: Creator has no pending invites
- **WHEN** creator has zero pending store invites
- **THEN** system displays "0" or omits the pending invites section from Needs Attention card

### Requirement: Empty State Display
The system SHALL display an empty state when no items need attention.

#### Scenario: Nothing needs attention
- **WHEN** creator has zero pending orders and zero pending store invites
- **THEN** system displays "Nothing needs attention" message with positive visual indicator

### Requirement: Badge Display for Counts
The system SHALL display count badges next to each attention item type.

#### Scenario: Pending items exist
- **WHEN** creator has pending orders or invites
- **THEN** system displays count badges showing the number of pending items

#### Scenario: Badge with high count
- **WHEN** count exceeds single digits
- **THEN** system displays the full count (e.g., "15" not "9+")

### Requirement: Navigation to Pending Orders
The system SHALL provide navigation from the pending orders count to a filtered view of pending orders.

#### Scenario: Creator clicks pending orders count
- **WHEN** creator clicks on the pending orders section in Needs Attention card
- **THEN** system navigates to the wholesale orders page filtered to show only pending orders

### Requirement: Navigation to Store Relationships
The system SHALL provide navigation from the pending store invites count to the store relationships page.

#### Scenario: Creator clicks pending invites count
- **WHEN** creator clicks on the pending store invites section in Needs Attention card
- **THEN** system navigates to the store relationships page showing pending invitations

### Requirement: Real-time Count Accuracy
The system SHALL display accurate counts based on current database state at page load.

#### Scenario: Dashboard loads after order status change
- **WHEN** creator navigates to dashboard after accepting an order
- **THEN** system displays updated pending order count reflecting the status change

### Requirement: Visual Priority
The system SHALL use visual styling to indicate urgency of pending items.

#### Scenario: Pending items exist
- **WHEN** creator views Needs Attention card with pending items
- **THEN** system uses visual indicators (badges, color) to draw attention to actionable items
