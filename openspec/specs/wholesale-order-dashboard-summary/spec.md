## ADDED Requirements

### Requirement: Display Active Orders Grouped by Status
The system SHALL display active wholesale orders grouped by their current status.

#### Scenario: Creator views dashboard with orders in multiple statuses
- **WHEN** creator has orders in pending, accepted, fulfilled, and delivered statuses
- **THEN** system displays orders grouped under each status heading

#### Scenario: Creator has no orders in a status
- **WHEN** creator has no orders in a particular status
- **THEN** system displays empty state for that status group

### Requirement: Display Pending Orders
The system SHALL display all wholesale orders with status "pending" that are awaiting creator acceptance.

#### Scenario: Creator has pending orders
- **WHEN** creator views dashboard with pending orders
- **THEN** system displays all pending orders under "Pending" heading

#### Scenario: No pending orders
- **WHEN** creator has no pending orders
- **THEN** system displays empty state message under "Pending" heading

### Requirement: Display Accepted Orders
The system SHALL display all wholesale orders with status "accepted" that have not yet been fulfilled.

#### Scenario: Creator has accepted orders
- **WHEN** creator views dashboard with accepted orders
- **THEN** system displays all accepted orders under "Accepted" heading

### Requirement: Display Fulfilled Orders
The system SHALL display all wholesale orders with status "fulfilled" that have not yet been delivered.

#### Scenario: Creator has fulfilled orders
- **WHEN** creator views dashboard with fulfilled orders
- **THEN** system displays all fulfilled orders under "Fulfilled" heading

### Requirement: Display Recently Delivered Orders
The system SHALL display wholesale orders with status "delivered" that were delivered within the last 30 days.

#### Scenario: Creator has recently delivered orders
- **WHEN** creator views dashboard with orders delivered in the last 30 days
- **THEN** system displays those orders under "Delivered" heading

#### Scenario: Delivered order is older than 30 days
- **WHEN** creator has an order delivered more than 30 days ago
- **THEN** system does not display that order on the dashboard

### Requirement: Display Order Details
The system SHALL display essential order information for each order in the summary.

#### Scenario: Order appears in summary
- **WHEN** creator views an order in the dashboard summary
- **THEN** system displays: store name, order number, total amount, and order date

### Requirement: Navigation to Order Detail
The system SHALL provide navigation from each order in the summary to its full order detail page.

#### Scenario: Creator clicks on an order
- **WHEN** creator clicks on any order in the summary
- **THEN** system navigates to the full order detail page for that order

### Requirement: Order Chronological Sorting
The system SHALL display orders within each status group sorted by creation date, newest first.

#### Scenario: Multiple orders in same status
- **WHEN** creator views orders grouped by status
- **THEN** system displays orders within each group sorted by creation date in descending order

### Requirement: Exclude Cancelled Orders
The system SHALL exclude cancelled orders from the dashboard summary.

#### Scenario: Creator has cancelled orders
- **WHEN** creator views the dashboard
- **THEN** system does not display any orders with status "cancelled"

### Requirement: Eager Load Related Data
The system SHALL load order relationships (store, orderable) efficiently to prevent N+1 queries.

#### Scenario: Dashboard loads with multiple orders
- **WHEN** creator views dashboard with multiple orders
- **THEN** system loads all order data including store and orderable information in a single query set
