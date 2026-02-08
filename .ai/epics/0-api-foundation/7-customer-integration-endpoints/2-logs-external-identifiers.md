status: pending

# Story 0.7: Prompt 2 -- Integration Logs & ExternalIdentifier Endpoints

## Context

Prompt 1 created CRUD endpoints for Customers and Integrations. Two supporting resources remain: Integration Logs (read-only, for viewing sync history) and External Identifiers (create + lookup, used by sync services to map internal models to external system IDs like Shopify product/order IDs). These are the final endpoints in the API Foundation epic.

## Goal

Create a read-only endpoint for integration logs nested under integrations, and create/lookup endpoints for external identifiers. After this prompt, the full API Foundation (Epic 0) is complete -- all planned endpoints are in place.

## Non-Goals

- Do not create update or delete endpoints for integration logs (they're an append-only audit trail)
- Do not create update or delete endpoints for external identifiers (they're managed by sync services, not manually edited)
- Do not add pagination to logs (keep it simple -- return recent logs, optionally limited)
- Do not create new FormRequests for external identifiers -- create an API-specific one since none exists in the web app

## Constraints

- Integration logs endpoint: `GET /api/v1/integrations/{integration}/logs` -- nested under integrations, read-only
- Logs should be ordered by `created_at` descending (newest first) and limited to a reasonable default (e.g., 50, with `?limit=` parameter)
- Verify the parent integration belongs to the user's account before returning logs
- External identifier endpoints:
  - `POST /api/v1/external-identifiers` -- create a mapping (validates integration_id, identifiable_type, identifiable_id, external_type, external_id, data)
  - `GET /api/v1/external-identifiers` -- lookup by query params `?integration_id=&external_type=&external_id=` or `?integration_id=&identifiable_type=&identifiable_id=`
- External identifier authorization: verify the referenced integration belongs to the user's account
- Create a `StoreExternalIdentifierRequest` FormRequest for the POST endpoint since none exists
- Use `IntegrationLogResource` and `ExternalIdentifierResource` from Story 0.3
- ExternalIdentifier model has query scopes `forIntegration()` and `ofType()` -- use them in the lookup endpoint

## Acceptance Criteria

- [ ] `GET /api/v1/integrations/{integration}/logs` returns logs for the specified integration, newest first
- [ ] `GET /api/v1/integrations/{integration}/logs?limit=10` limits the number of logs returned
- [ ] Accessing logs for another account's integration returns 403
- [ ] `POST /api/v1/external-identifiers` creates a mapping with validated data
- [ ] `POST` rejects duplicate external IDs (unique constraint on `[integration_id, external_type, external_id]`)
- [ ] `GET /api/v1/external-identifiers?integration_id=1&external_type=product&external_id=123` returns matching identifier(s)
- [ ] `GET /api/v1/external-identifiers?integration_id=1&identifiable_type=App\Models\Colorway&identifiable_id=5` returns matching identifier(s)
- [ ] External identifier endpoints verify the referenced integration belongs to the user's account
- [ ] Tests cover all endpoints, authorization, validation, and unique constraint handling
- [ ] All existing tests still pass

---

## Tech Analysis

- **No web controller exists for integration logs or external identifiers** -- these are API-only endpoints. There's no existing pattern to mirror, but the controller structure follows the same conventions.
- **IntegrationLog has no policy** -- authorization is handled indirectly by verifying the parent integration belongs to the user's account. The controller should load the integration, check account ownership, then query its logs.
- **ExternalIdentifier has no policy** -- authorization is also through the parent integration. The `POST` endpoint should verify `integration_id` belongs to the user's account. The `GET` endpoint should also verify.
- **ExternalIdentifier has two unique constraints**: `[integration_id, external_type, external_id]` prevents duplicate external IDs, and `[integration_id, identifiable_type, identifiable_id, external_type]` prevents one internal model from having multiple external IDs of the same type per integration. The `POST` endpoint should handle unique constraint violations gracefully with a 422 response.
- **ExternalIdentifier query scopes**: `forIntegration(Integration $integration)` and `ofType(string $externalType)` are defined on the model and should be used in the lookup endpoint for clean query building.
- **`identifiable_type` uses full class names** (e.g., `App\Models\Colorway`). The lookup endpoint should accept these as query parameters. Consider also accepting short names (e.g., `colorway`) and mapping them to full class names for a friendlier API.
- **No `StoreExternalIdentifierRequest` exists** -- needs to be created. Validate: `integration_id` (required, exists), `identifiable_type` (required, string), `identifiable_id` (required, integer), `external_type` (required, string), `external_id` (required, string), `data` (nullable, array). Authorization: verify the integration belongs to the user's account.

## References

- `platform/app/Models/IntegrationLog.php` -- fields, casts (IntegrationLogStatus enum, array metadata, datetime synced_at), belongs to integration
- `platform/app/Models/ExternalIdentifier.php` -- fields, casts (array data), query scopes (forIntegration, ofType), unique constraints
- `platform/app/Http/Resources/Api/V1/IntegrationLogResource.php` -- serialization (created in Story 0.3)
- `platform/app/Http/Resources/Api/V1/ExternalIdentifierResource.php` -- serialization (created in Story 0.3)
- `platform/app/Http/Controllers/Api/V1/IntegrationController.php` -- parent controller, account ownership check pattern (created in Prompt 1)
- `platform/app/Policies/IntegrationPolicy.php` -- used to verify integration ownership

## Files

- Create `platform/app/Http/Controllers/Api/V1/IntegrationLogController.php` -- read-only nested endpoint for logs
- Create `platform/app/Http/Controllers/Api/V1/ExternalIdentifierController.php` -- create and lookup endpoints
- Create `platform/app/Http/Requests/StoreExternalIdentifierRequest.php` -- validation for external identifier creation
- Modify `platform/routes/api.php` -- add log route nested under integrations, add external-identifier routes
- Create `platform/tests/Feature/Api/V1/IntegrationLogControllerTest.php` -- tests for log listing, authorization, limit parameter
- Create `platform/tests/Feature/Api/V1/ExternalIdentifierControllerTest.php` -- tests for create, lookup, unique constraint, authorization
