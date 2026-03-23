# Task 01 — PaymentStatus Enum + Migration

## Starting Prompt

> I'm working through the Shopify orders spec at `specs/shopify-orders/`. Please read `specs/shopify-orders/overview.md` and `specs/shopify-orders/tasks/01-payment-status-migration.md`, then implement this task. Work through the checklist before marking done. Don't start Task 02.

---

## Goal

Add a `PaymentStatus` enum and wire it into the `Order` model. Also add `title` and `variant_title` columns to `order_items` so unmatched Shopify line items can still be displayed meaningfully.

---

## What to Build

### 1. `PaymentStatus` enum

New file: `app/Enums/PaymentStatus.php`

```php
enum PaymentStatus: string
{
    case Paid = 'paid';
    case Unpaid = 'unpaid';
    case PartiallyRefunded = 'partially_refunded';
    case Refunded = 'refunded';
}
```

### 2. Migration — `orders` table

Add `payment_status` column (nullable string, after `status`):

```php
$table->string('payment_status')->nullable()->after('status');
```

Nullable so existing wholesale/show orders are unaffected.

### 3. Migration — `order_items` table

Add `title` and `variant_title` columns:

```php
$table->string('title')->nullable()->after('base_id');
$table->string('variant_title')->nullable()->after('title');
```

These store the Shopify product title and variant title for line items that don't match a Fibermade Colorway/Base.

### 4. Update `Order` model

- Add `payment_status` to `$fillable`
- Add cast: `'payment_status' => PaymentStatus::class`
- Add `@property` PHPDoc for `payment_status`

### 5. Update `OrderItem` model

- Add `title` and `variant_title` to `$fillable`
- Add `@property` PHPDoc for both fields

---

## Files to Touch

| File | Change |
|---|---|
| `app/Enums/PaymentStatus.php` | New enum |
| `database/migrations/xxxx_add_payment_status_to_orders_table.php` | New migration |
| `database/migrations/xxxx_add_title_to_order_items_table.php` | New migration |
| `app/Models/Order.php` | Add `payment_status` to fillable + cast |
| `app/Models/OrderItem.php` | Add `title`, `variant_title` to fillable |

---

## Tests

- `PaymentStatus` enum has correct string values for all four cases
- `Order` model casts `payment_status` to `PaymentStatus` enum
- `payment_status` is nullable on the orders table (existing orders unaffected)
- `order_items` table accepts `title` and `variant_title`

---

## Checklist

- [ ] Create `app/Enums/PaymentStatus.php`
- [ ] Create migration for `payment_status` on `orders`
- [ ] Create migration for `title`, `variant_title` on `order_items`
- [ ] Update `Order` model — fillable + cast + PHPDoc
- [ ] Update `OrderItem` model — fillable + PHPDoc
- [ ] Run migrations: `php artisan migrate`
- [ ] Write tests
- [ ] Run `php artisan test --compact` — all passing
- [ ] Run `vendor/bin/pint --dirty`
