status: pending

# Story 0.6: Prompt 1 -- Order CRUD Endpoints

## Context

Stories 0.4-0.5 created API controllers for catalog and inventory resources. Orders are next -- they're needed for both wholesale management and Shopify order import (Epic 1+). One important difference: `OrderPolicy` currently disables create, update, and delete (returns `false` with TODO comments). The web app disabled these during Stage 1, but the API needs them enabled for external clients to manage orders.

## Goal

Re-enable the `OrderPolicy` write actions and create an Order API controller with full CRUD endpoints. After this prompt, `GET/POST /api/v1/orders` and `GET/PATCH/DELETE /api/v1/orders/{order}` work end-to-end.

## Non-Goals

- Do not create OrderItem endpoints (that's Prompt 2)
- Do not add order total recalculation logic to the API controller -- that will be handled when OrderItems are created/updated in Prompt 2
- Do not modify the web `OrderController` or its routes
- Do not change `StoreOrderRequest` or `UpdateOrderRequest` validation rules

## Constraints

- Follow the API controller pattern from Story 0.4
- Re-enable `OrderPolicy` by uncommenting the original logic for `create`, `update`, `delete`, `restore`, and `forceDelete` -- the commented-out code on lines 34, 46, 58, 70, 82 has the correct logic
- Index should eager-load `['orderItems', 'orderable']`
- Show should eager-load `['orderItems.colorway', 'orderItems.base', 'orderable']` for detailed view
- Store must set `account_id`, `created_by` from authenticated user, and resolve `orderable_type` based on the `type` field following the web controller pattern: wholesale → `Store::class`, retail → `Customer::class`, show → `Show::class`
- Update must set `updated_by` from authenticated user
- Reuse `StoreOrderRequest` and `UpdateOrderRequest` for validation
- Use `OrderResource` from Story 0.3

## Acceptance Criteria

- [ ] `OrderPolicy` create/update/delete/restore/forceDelete methods return standard account-scoped logic (no longer `return false`)
- [ ] `GET /api/v1/orders` returns paginated orders scoped to user's account
- [ ] `POST /api/v1/orders` creates an order with `account_id`, `created_by`, and resolved `orderable_type`
- [ ] `GET /api/v1/orders/{order}` returns order with items and orderable loaded
- [ ] `PATCH /api/v1/orders/{order}` updates order and sets `updated_by`
- [ ] `DELETE /api/v1/orders/{order}` soft-deletes the order
- [ ] Tests cover auth, authorization, validation, and CRUD
- [ ] All existing tests still pass (especially any existing order-related tests)

---

## Tech Analysis

- **`OrderPolicy` needs to be re-enabled.** Lines 31-36 (create), 43-48 (update), 55-60 (delete), 67-72 (restore), 79-84 (forceDelete) all `return false` with the original logic commented out above. Uncomment the original logic and remove the `return false` lines. The web controller routes are disabled via the policy -- re-enabling the policy ALSO enables the web routes. This is acceptable since the web routes were only disabled because the feature wasn't ready, and now it is.
- **`orderable_type` resolution** is a key piece of logic in the web `OrderController::store()`. The `type` field determines which model the order belongs to: `OrderType::Wholesale` → `Store::class`, `OrderType::Retail` → `Customer::class`, `OrderType::Show` → `Show::class`. The API controller needs this same mapping.
- **`StoreOrderRequest` validation** is complex: `orderable_id` uses conditional exists rules based on `type`. The wholesale type expects a Store ID, retail expects a Customer ID, show expects a Show ID. This is already handled in the FormRequest -- the API controller just passes through validated data.
- **Order has many decimal fields** -- all handled by the model's casts and the `OrderResource`. No special formatting needed in the controller.
## References

- `platform/app/Policies/OrderPolicy.php` -- re-enable write actions (uncomment lines 34, 46, 58, 70, 82)
- `platform/app/Http/Controllers/OrderController.php` -- web controller: orderable_type resolution in store(), update pattern with updated_by, eager loading
- `platform/app/Http/Requests/StoreOrderRequest.php` -- complex validation with conditional orderable_id rules
- `platform/app/Http/Requests/UpdateOrderRequest.php` -- update validation
- `platform/app/Http/Controllers/Api/V1/ColorwayController.php` -- API controller pattern to follow
- `platform/app/Http/Resources/Api/V1/OrderResource.php` -- serialization (created in Story 0.3)
- `platform/app/Enums/OrderType.php` -- Wholesale, Retail, Show
- `platform/app/Enums/OrderStatus.php` -- Draft, Open, Closed, Cancelled

## Files

- Modify `platform/app/Policies/OrderPolicy.php` -- uncomment original logic for create, update, delete, restore, forceDelete
- Create `platform/app/Http/Controllers/Api/V1/OrderController.php` -- CRUD with orderable_type resolution
- Modify `platform/routes/api.php` -- add apiResource for orders
- Create `platform/tests/Feature/Api/V1/OrderControllerTest.php` -- tests for CRUD, orderable resolution, policy re-enablement
