# Task 01 — Database & Model

## Starting Prompt

> I'm working through the Shopify connect token migration at `specs/shopify-connect-token/`. Please read `specs/shopify-connect-token/overview.md` and `specs/shopify-connect-token/tasks/01-db-and-model.md`, then implement Task 01 in full. Work through the checklist at the bottom of the task file. This task touches the Laravel platform only (`platform/` directory).

## Goal

Add the `shopify_connect_token` UUID column to the `accounts` table and wire it into the `Account` model. Every account gets a token — new accounts get one on creation, existing accounts get one backfilled by the migration.

## Migration

```php
Schema::table('accounts', function (Blueprint $table) {
    $table->uuid('shopify_connect_token')->nullable()->unique()->after('type');
});
```

Add a second migration (or use `afterUp`) to backfill existing accounts:

```php
Account::whereNull('shopify_connect_token')->each(function (Account $account) {
    $account->update(['shopify_connect_token' => (string) Str::uuid()]);
});
```

Then make the column non-nullable in the same migration after backfilling:

```php
$table->uuid('shopify_connect_token')->nullable(false)->unique()->change();
```

## Model Changes

`Account`:
- Add `shopify_connect_token` to `$fillable`
- Add `generateConnectToken()` method that sets a fresh UUID and saves

```php
public function generateConnectToken(): void
{
    $this->update(['shopify_connect_token' => (string) Str::uuid()]);
}
```

## Registration Flow

Find where accounts are created during registration (likely a controller or Fortify action) and ensure `shopify_connect_token` is set. The cleanest place is the `AccountFactory` default definition and the account creation call.

In `AccountFactory::definition()`:
```php
'shopify_connect_token' => (string) Str::uuid(),
```

In account creation (registration flow): add `'shopify_connect_token' => (string) Str::uuid()` to the create call, or use a model `creating` observer.

The simplest approach is a model boot hook:

```php
protected static function booting(): void
{
    static::creating(function (Account $account) {
        if (empty($account->shopify_connect_token)) {
            $account->shopify_connect_token = (string) Str::uuid();
        }
    });
}
```

This guarantees every account gets a token regardless of where it's created.

## Checklist

- [ ] Create migration to add `shopify_connect_token` UUID column (nullable, unique)
- [ ] Backfill existing accounts in the same migration
- [ ] Make column non-nullable after backfill
- [ ] Add `shopify_connect_token` to `Account::$fillable`
- [ ] Add `creating` boot hook to auto-generate UUID on account creation
- [ ] Add `generateConnectToken()` method to `Account`
- [ ] Update `AccountFactory` definition to include `shopify_connect_token`
- [ ] Run migration and verify all accounts have a token
- [ ] Write tests: account creation auto-generates token, `generateConnectToken()` replaces token, token is unique across accounts
- [ ] Run tests and confirm passing
