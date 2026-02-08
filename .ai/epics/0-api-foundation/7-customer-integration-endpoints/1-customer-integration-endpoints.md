status: pending

# Story 0.7: Prompt 1 -- Customer & Integration CRUD Endpoints

## Context

Stories 0.4-0.6 created API endpoints for catalog, inventory, and order resources. Customer and Integration are the final two CRUD resources. Like `OrderPolicy`, `CustomerPolicy` currently disables write actions (create, update, delete return `false`) because customer management wasn't needed in the web app's Stage 1. The API needs them enabled for Shopify customer import (Epic 7). Integrations are needed immediately for Epic 1 (Shopify app linking).

## Goal

Re-enable `CustomerPolicy` write actions and create API controllers for Customer and Integration with full CRUD. After this prompt, all primary resources in the platform have API endpoints.

## Non-Goals

- Do not create integration log or external identifier endpoints (that's Prompt 2)
- Do not add customer search/lookup endpoints beyond basic CRUD
- Do not modify the web controllers or routes
- Do not change FormRequest validation rules

## Constraints

- Follow the API controller pattern from Story 0.4
- Re-enable `CustomerPolicy` by uncommenting the original logic for `create`, `update`, `delete`, `restore`, `forceDelete` -- the commented-out code on lines 34, 46, 58, 70, 82 has the correct logic
- Customer index: no special eager loading needed (simple model), no filtering required for now
- Customer show: eager-load `['orders']` for customer detail view
- Customer store: must set `account_id` from authenticated user. Note that `StoreCustomerRequest` has `account_id` as a required field -- for the API, merge it from the authenticated user before validation (similar to the OrderItem pattern)
- Integration index: support `?type=` filter (currently only `shopify` but designed for future types) and `?active=` filter (boolean)
- Integration show: eager-load `['logs']` for detail view (latest logs)
- Integration store: must set `account_id` from authenticated user
- Integration `credentials` field is stored encrypted -- the controller should accept it in the store/update request but `IntegrationResource` (Story 0.3) already excludes it from responses
- Reuse existing FormRequests and Resources

## Acceptance Criteria

- [ ] `CustomerPolicy` create/update/delete/restore/forceDelete methods return standard account-scoped logic
- [ ] `GET/POST /api/v1/customers` and `GET/PATCH/DELETE /api/v1/customers/{customer}` work with auth, authorization, validation
- [ ] `GET /api/v1/customers/{customer}` includes orders relationship
- [ ] `GET/POST /api/v1/integrations` and `GET/PATCH/DELETE /api/v1/integrations/{integration}` work with auth, authorization, validation
- [ ] `GET /api/v1/integrations?type=shopify` filters by type
- [ ] `GET /api/v1/integrations?active=true` filters by active status
- [ ] Integration responses never include `credentials`
- [ ] Tests cover auth, authorization, validation, CRUD for both resources
- [ ] Tests verify credentials are excluded from integration responses
- [ ] All existing tests still pass

---

## Tech Analysis

- **`CustomerPolicy` needs the same treatment as `OrderPolicy`** from Story 0.6. Lines 31-36 (create), 43-48 (update), 55-60 (delete), 67-72 (restore), 79-84 (forceDelete) all return false. Uncomment the standard logic.
- **`StoreCustomerRequest` requires `account_id`** as a validated field (`required, exists:accounts,id`). The web controller presumably passes this from the form. For the API, merge `account_id` from `$request->user()->account_id` before validation -- same pattern as OrderItem's `order_id` merge.
- **Integration `credentials`** field is sensitive (encrypted API keys/secrets). The `StoreIntegrationRequest` has it as `required, string`. The API accepts it for creation but `IntegrationResource` excludes it from responses. This is the correct pattern -- write-only field.
- **Customer has no special casts or complex fields** -- all strings. Straightforward CRUD.
- **Integration filtering**: `type` is an IntegrationType enum (currently only `shopify`), `active` is a boolean. Both are simple where clauses.
- **Both models use SoftDeletes** -- DELETE soft-deletes.

## References

- `platform/app/Policies/CustomerPolicy.php` -- re-enable write actions (uncomment lines 34, 46, 58, 70, 82)
- `platform/app/Http/Controllers/CustomerController.php` -- web controller: eager loading patterns, store with account_id
- `platform/app/Http/Controllers/IntegrationController.php` -- web controller: store pattern
- `platform/app/Http/Requests/StoreCustomerRequest.php` -- validation (account_id required, needs merge)
- `platform/app/Http/Requests/StoreIntegrationRequest.php` -- validation (credentials required)
- `platform/app/Http/Resources/Api/V1/CustomerResource.php` -- serialization (created in Story 0.3)
- `platform/app/Http/Resources/Api/V1/IntegrationResource.php` -- serialization, credentials excluded (created in Story 0.3)
- `platform/app/Http/Controllers/Api/V1/ColorwayController.php` -- API controller pattern to follow

## Files

- Modify `platform/app/Policies/CustomerPolicy.php` -- uncomment original logic for write actions
- Create `platform/app/Http/Controllers/Api/V1/CustomerController.php` -- CRUD
- Create `platform/app/Http/Controllers/Api/V1/IntegrationController.php` -- CRUD with type/active filtering
- Modify `platform/routes/api.php` -- add apiResource routes for customers and integrations
- Create `platform/tests/Feature/Api/V1/CustomerControllerTest.php` -- CRUD tests
- Create `platform/tests/Feature/Api/V1/IntegrationControllerTest.php` -- CRUD tests, credentials exclusion verification
