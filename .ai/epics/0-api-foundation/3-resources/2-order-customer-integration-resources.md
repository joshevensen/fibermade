status: done

# Story 0.3: Prompt 2 -- Order, Customer, Integration & ExternalIdentifier Resources

## Context

Prompt 1 created API Resources for the catalog models: `ColorwayResource`, `BaseResource`, `CollectionResource`, and `InventoryResource` in `app/Http/Resources/Api/V1/`. The pattern is established -- Resources extend `JsonResource`, use `$this->whenLoaded()` for conditional relationships, serialize enums as string values, and format decimals as strings. The remaining models that need API endpoints still lack Resources.

## Goal

Create API Resources for Order (with nested OrderItem), Customer, Integration (with IntegrationLog), and ExternalIdentifier. After this prompt, every model that needs an API endpoint in Stories 0.4-0.7 has a corresponding Resource, and the full serialization layer is complete.

## Non-Goals

- Do not create API controllers or routes (those are Stories 0.6-0.7)
- Do not modify the catalog Resources from Prompt 1
- Do not add new fields or relationships to the models
- Do not serialize sensitive fields from Integration (`credentials` must be excluded)

## Constraints

- Resources go in `app/Http/Resources/Api/V1/` matching the namespace from Prompt 1
- Follow the same patterns established in Prompt 1: `whenLoaded()` for relationships, enums as string values, decimals as strings, `id`/`created_at`/`updated_at` on every Resource
- `OrderResource` should nest `OrderItemResource` when `orderItems` is loaded -- this is the primary nested resource pattern in the API
- `OrderItemResource` should nest `ColorwayResource` and `BaseResource` when loaded, reusing the catalog Resources from Prompt 1
- `IntegrationResource` must NOT include the `credentials` field -- this contains encrypted API keys and secrets
- `ExternalIdentifierResource` should include the `identifiable_type` and `identifiable_id` for polymorphic resolution but not nest the full identifiable model
- `IntegrationLogResource` should include `loggable_type` and `loggable_id` but not nest the full loggable model
- Order's `orderable` polymorphic relationship (Show, Store, Customer) should serialize as `orderable_type` and `orderable_id` flat fields, plus conditionally nest the related model when loaded
- Use Pest syntax for tests

## Acceptance Criteria

- [ ] `OrderResource.php` serializes: id, type, status, order_date, subtotal_amount, shipping_amount, discount_amount, tax_amount, total_amount, refunded_amount, payment_method, source, notes, orderable_type, orderable_id, created_at, updated_at, and conditionally: order_items, orderable
- [ ] `OrderItemResource.php` serializes: id, order_id, colorway_id, base_id, quantity, unit_price, line_total, created_at, updated_at, and conditionally: colorway, base
- [ ] `CustomerResource.php` serializes: id, name, email, phone, address fields, notes, created_at, updated_at
- [ ] `IntegrationResource.php` serializes: id, type, settings, active, created_at, updated_at, and conditionally: logs. Does NOT include `credentials`
- [ ] `IntegrationLogResource.php` serializes: id, integration_id, loggable_type, loggable_id, status, message, metadata, synced_at, created_at, updated_at
- [ ] `ExternalIdentifierResource.php` serializes: id, integration_id, identifiable_type, identifiable_id, external_type, external_id, data, created_at, updated_at
- [ ] Tests verify each Resource produces the expected JSON structure
- [ ] Tests verify `IntegrationResource` excludes the `credentials` field
- [ ] Tests verify nested relationships (OrderItems inside Order, Logs inside Integration)
- [ ] All existing tests still pass (`php artisan test`)

---

## Tech Analysis

- **Order has many decimal fields** (`subtotal_amount`, `shipping_amount`, `discount_amount`, `tax_amount`, `total_amount`, `refunded_amount`) all cast as `decimal:2`. Apply the same string formatting from Prompt 1's BaseResource.
- **Order's `orderable` is polymorphic** (`MorphTo`) -- it can be a Show, Store, or Customer. The Resource should include `orderable_type` and `orderable_id` as flat fields for type discrimination. When the relationship is loaded, nest it conditionally. Since the orderable could be different types, use `$this->whenLoaded('orderable')` and let Laravel's JSON serialization handle it (or use a simple `$this->orderable?->toArray()` since we don't have Resources for Show/Store and they aren't API-exposed models).
- **Order's `taxes` field** is cast as `array` (JSON column). Serialize it as-is -- it's already a structured array.
- **Order's `cancelled_at`** is a datetime. Include it in the Resource -- it's meaningful for API consumers to know when an order was cancelled.
- **OrderItem reuses catalog Resources** -- `ColorwayResource` and `BaseResource` from Prompt 1. This validates the resource layering approach.
- **Integration `credentials`** is stored as encrypted text in the database. This field must be explicitly excluded from the Resource. The `settings` field (cast as `array`) is safe to include -- it contains non-sensitive configuration.
- **IntegrationLog and ExternalIdentifier have polymorphic relationships** (`loggable` and `identifiable`). For the API, include the type and ID as flat fields for reference, but don't nest the full model -- consumers can resolve these via other endpoints if needed.
- **Customer has no casts** -- all fields are simple strings/text. Straightforward Resource.
- **ExternalIdentifier has query scopes** (`forIntegration`, `ofType`) on the model. These are useful for controllers later but don't affect the Resource.

## References

- `platform/app/Models/Order.php` -- fields, casts (OrderType, OrderStatus enums, decimal:2 amounts, date, array for taxes), MorphTo orderable, HasMany orderItems
- `platform/app/Models/OrderItem.php` -- fields, casts (decimal:2 for pricing), BelongsTo order/colorway/base
- `platform/app/Models/Customer.php` -- fields (all strings), BelongsTo account, MorphMany orders/externalIdentifiers
- `platform/app/Models/Integration.php` -- fields, casts (IntegrationType enum, array settings, boolean active), encrypted credentials to exclude
- `platform/app/Models/IntegrationLog.php` -- fields, casts (IntegrationLogStatus enum, array metadata, datetime synced_at), MorphTo loggable
- `platform/app/Models/ExternalIdentifier.php` -- fields, casts (array data), MorphTo identifiable, query scopes
- `platform/app/Http/Resources/Api/V1/ColorwayResource.php` -- pattern to follow (created in Prompt 1)
- `platform/app/Http/Resources/Api/V1/BaseResource.php` -- decimal formatting pattern to reuse (created in Prompt 1)
- `platform/app/Enums/OrderType.php` -- Wholesale, Retail, Show
- `platform/app/Enums/OrderStatus.php` -- Draft, Open, Closed, Cancelled
- `platform/app/Enums/IntegrationType.php` -- Shopify
- `platform/app/Enums/IntegrationLogStatus.php` -- Success, Error, Warning

## Files

- Create `platform/app/Http/Resources/Api/V1/OrderResource.php` -- serializes Order with conditional orderItems and orderable
- Create `platform/app/Http/Resources/Api/V1/OrderItemResource.php` -- serializes OrderItem with conditional colorway and base
- Create `platform/app/Http/Resources/Api/V1/CustomerResource.php` -- serializes Customer with address fields
- Create `platform/app/Http/Resources/Api/V1/IntegrationResource.php` -- serializes Integration WITHOUT credentials, with conditional logs
- Create `platform/app/Http/Resources/Api/V1/IntegrationLogResource.php` -- serializes IntegrationLog with polymorphic type/id
- Create `platform/app/Http/Resources/Api/V1/ExternalIdentifierResource.php` -- serializes ExternalIdentifier with polymorphic type/id
- Create `platform/tests/Feature/Api/Resources/OrderResourceTest.php` -- tests for Order and OrderItem Resources (nesting, decimal formatting)
- Create `platform/tests/Feature/Api/Resources/CustomerIntegrationResourceTest.php` -- tests for Customer, Integration (credentials exclusion), IntegrationLog, ExternalIdentifier Resources
