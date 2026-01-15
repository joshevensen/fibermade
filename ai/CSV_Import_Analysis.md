# CSV Import Analysis

This document analyzes the CSV export files from Shopify and compares them to the Fibermade database structure. It identifies gaps, provides import recommendations, and outlines a strategy for populating the database with Shopify data.

**Generated:** 2025-01-27
**CSV Files Analyzed:**
- `orders_export_1.csv` (530 orders, 532 lines)
- `customers_export.csv` (234 customers, 236 lines)
- `products_export_1.csv` (481 products/variants, 484 lines)
- `inventory_export_1.csv` (404 inventory entries, 406 lines)
- `discounts_export_1.csv` (8 discount codes, 10 lines)

---

## 1. Missing in Database (From CSV Data)

### 1.1 Discount Codes/Campaigns
**CSV Has:** `discounts_export_1.csv` with discount codes, values, types, requirements, usage limits, status, dates
- **Database Has:** Only `orders.discount_amount` (numeric field)
- **Missing:** 
  - Discount code/campaign tracking
  - Discount type (percentage, fixed_amount, free shipping)
  - Discount requirements (minimum purchase, product-based, etc.)
  - Discount usage limits and tracking
  - Discount status and date ranges
- **Impact:** Cannot track which discount codes were used, cannot recreate discount campaigns
- **Recommendation:** 
  - For Stage 1: Store discount code name in `orders.notes` or create `ExternalIdentifier` entries
  - For Stage 2+: Consider creating a `Discount` model if discount management becomes important

### 1.2 Tax Details
**CSV Has:** Multiple tax lines per order (`Tax 1 Name`, `Tax 1 Value`, `Tax 2 Name`, etc.) - up to 5 tax rates
- **Database Has:** Only `orders.tax_amount` (single numeric total)
- **Missing:** Tax breakdown by tax type/jurisdiction
- **Impact:** Cannot see tax composition (state, city, transit taxes, etc.)
- **Recommendation:** Store tax breakdown in JSON field on Order or as separate `OrderTax` model if needed for reporting

### 1.3 Payment Details
**CSV Has:** `Payment Method`, `Payment Reference`, `Payment ID`, `Payment Terms Name`, `Payment References`
- **Database Has:** No payment tracking fields
- **Missing:** Payment method, payment references/IDs, payment terms
- **Impact:** Cannot track how orders were paid
- **Recommendation:** Store in `orders.notes` or add JSON field for payment metadata if needed

### 1.4 Shipping Details
**CSV Has:** `Shipping Method`
- **Database Has:** Only `orders.shipping_amount`
- **Missing:** Shipping method name (e.g., "Economy", "Standard")
- **Impact:** Cannot track shipping methods used
- **Recommendation:** Store in `orders.notes` or add `shipping_method` field if needed for reporting

### 1.5 Order Cancellation/Refunds
**CSV Has:** `Cancelled at`, `Refunded Amount`
- **Database Has:** Order status enum (which could include cancelled)
- **Missing:** Explicit cancellation timestamp, refund amount tracking
- **Impact:** Cannot track when orders were cancelled or refund amounts
- **Recommendation:** 
  - Use `orders.status` for cancelled state
  - Add `refunded_amount` field if refund tracking is needed
  - Store cancellation date in `orders.notes` or add `cancelled_at` field

### 1.6 Order Metadata
**CSV Has:** `Employee`, `Location`, `Device ID`, `Tags`, `Risk Level`, `Source`
- **Database Has:** `created_by` (user), `notes`
- **Missing:** Employee name, location name, device ID, order tags, risk level, order source (web, pos, shopify_draft_order, etc.)
- **Impact:** Cannot track order origin, risk assessment, or employee attribution
- **Recommendation:** Store in `orders.notes` or add JSON metadata field if needed for reporting

### 1.7 Customer Marketing Preferences
**CSV Has:** `Accepts Email Marketing`, `Accepts SMS Marketing`, `Tags`
- **Database Has:** Basic customer contact info only
- **Missing:** Email/SMS marketing preferences, customer tags
- **Impact:** Cannot track marketing preferences or customer segmentation
- **Recommendation:** Add `accepts_email_marketing` and `accepts_sms_marketing` boolean fields, store tags in JSON or separate table

### 1.8 Customer Financial Data
**CSV Has:** `Total Spent`, `Total Orders`, `Tax Exempt`
- **Database Has:** No aggregated customer data
- **Missing:** Customer lifetime value, order count, tax exemption status
- **Impact:** Cannot see customer purchase history summary
- **Recommendation:** These can be calculated from orders, but if frequently accessed, consider adding to customer model or using cached values

### 1.9 Product/Variant Details
**CSV Has:** Variant-level SKUs, barcodes, grams, compare-at prices, inventory policy, fulfillment service, cost per item, variant images, variant weights
- **Database Has:** Base and Colorway models, but variant-specific data would be in `ExternalIdentifier` or not tracked
- **Missing:** SKU tracking (could use ExternalIdentifier), barcodes, compare-at prices, variant images, cost per item
- **Impact:** Limited variant-level data tracking
- **Recommendation:** Use `ExternalIdentifier` to store Shopify variant IDs, store additional metadata in `data` JSON field

### 1.10 Line Item Discounts
**CSV Has:** `Lineitem discount` (per line item)
- **Database Has:** Only order-level `discount_amount`
- **Missing:** Per-line-item discount tracking
- **Impact:** Cannot see which items were discounted individually
- **Recommendation:** Store in `order_items.unit_price` (already reflects discount) or add `line_discount` field if needed

### 1.11 Gift Cards & Custom Sales
**CSV Has:** Gift card items, custom sale items (non-product line items)
- **Database Has:** Only product-based order items (colorway + base)
- **Missing:** Ability to track non-product line items
- **Impact:** Cannot import gift card purchases or custom sale items
- **Recommendation:** 
  - Skip gift cards and custom sales (not relevant for production planning)
  - Or create special "service" colorway/base entries for tracking

### 1.12 Vendor Field
**CSV Has:** `Vendor` field on orders (appears to be "Bad Frog Yarn Co" or empty)
- **Database Has:** Stores model (for wholesale), but no vendor field on orders
- **Missing:** Vendor tracking on orders
- **Impact:** Cannot distinguish vendor-specific orders
- **Recommendation:** This appears to be the store/account name - can be ignored or stored in notes

---

## 2. Missing in CSVs (From Database)

### 2.1 Production-Focused Data
**Database Has:** Colorway recipe, technique, per_pan, dye relationships, base fiber composition
- **CSV Has:** None of this production data
- **Impact:** Production data must be manually entered or imported from other sources
- **Note:** This is expected - Shopify doesn't track production recipes

### 2.2 Collections
**Database Has:** Collections model for grouping colorways
- **CSV Has:** No collection information
- **Impact:** Collections must be created manually or mapped from Shopify tags/metafields
- **Recommendation:** Map Shopify product tags to collections if needed

### 2.3 Dyes
**Database Has:** Dyes model with manufacturer, bleed status, preferences
- **CSV Has:** No dye information
- **Impact:** Dye data must be entered separately
- **Note:** This is expected - Shopify doesn't track dye recipes

### 2.4 Stores (Wholesale)
**Database Has:** Stores model for wholesale customers
- **CSV Has:** No store/wholesale data (only retail customers)
- **Impact:** Wholesale stores must be created separately
- **Note:** This is expected for Stage 1 - Shopify handles retail, Fibermade handles wholesale

### 2.5 Shows
**Database Has:** Shows model for events/shows
- **CSV Has:** No show information
- **Impact:** Shows must be created separately
- **Note:** This is expected - Shopify doesn't track shows

### 2.6 Account Structure
**Database Has:** Multi-account structure
- **CSV Has:** Single account data (Bad Frog Yarn Co)
- **Impact:** Must map CSV data to correct account
- **Recommendation:** Import all data under the appropriate account ID

### 2.7 Base Cost & Fiber Composition
**Database Has:** Base cost, retail_price, fiber percentages (wool, nylon, alpaca, etc.)
- **CSV Has:** Only variant price and cost per item (which is base cost, not fiber composition)
- **Impact:** Base cost and fiber data must be set manually or imported separately
- **Recommendation:** Use `Cost per item` from CSV for base cost if available

### 2.8 Order Type Differentiation
**Database Has:** Order type enum (wholesale, retail, show)
- **CSV Has:** All orders are retail (from Shopify)
- **Impact:** All imported orders should be marked as `OrderType::Retail`
- **Recommendation:** Set `type` to `OrderType::Retail` for all imported orders

---

## 3. Import Strategy & Recommendations

### 3.1 Import Order

1. **Integration Record** - Create/identify Shopify integration for account
2. **Bases** - Import bases from product variants (Option1 Value = Base name)
3. **Colorways** - Import colorways from product titles/handles
4. **Inventory** - Import inventory levels (map to colorway + base)
5. **Customers** - Import customers
6. **Orders** - Import orders (depends on customers, colorways, bases)
7. **Order Items** - Import order items (depends on orders, colorways, bases)
8. **External Identifiers** - Create mappings throughout import process

### 3.2 Key Mapping Strategies

#### Products → Colorways & Bases
- **Colorway:** Extract from product `Title` or `Handle` (e.g., "Fruitcake", "Campfire Stories")
- **Base:** Extract from `Option1 Value` (e.g., "Lily Pad - Fingering", "Hopper Sock")
- **Base Descriptor:** Parse base name (e.g., "Lily Pad - Fingering" → descriptor: "Lily Pad", weight: "Fingering")
- **Base Code:** Auto-generate from descriptor (e.g., "Lily Pad" → "LP")

#### Variants → Bases
- Each variant represents a Base
- `Option1 Value` contains base identifier
- `Variant Price` = `Base.retail_price`
- `Cost per item` = `Base.cost` (if available)
- `Variant SKU` → Store in `ExternalIdentifier` (external_type: 'shopify_variant')

#### Inventory Mapping
- CSV format: `Handle` (colorway), `Option1 Value` (base), `Available` quantity
- Map to: `Inventory` (account_id, colorway_id, base_id, quantity)

#### Order Mapping
- Shopify Order → Fibermade Order
- `Financial Status` → `Order.status` (paid = fulfilled/processing)
- `Fulfillment Status` → `Order.status` (fulfilled = completed)
- `Created at` → `Order.order_date`
- `Subtotal` → `Order.subtotal_amount`
- `Shipping` → `Order.shipping_amount`
- `Discount Amount` → `Order.discount_amount`
- `Taxes` → `Order.tax_amount`
- `Total` → `Order.total_amount`
- `Email` → Find/create Customer, set `orderable_type` = 'Customer', `orderable_id` = customer.id
- `Id` (Shopify order ID) → Store in `ExternalIdentifier` (external_type: 'shopify_order')

#### Order Item Mapping
- CSV line items → OrderItem records
- `Lineitem name` → Parse to find Colorway + Base (e.g., "Fruitcake - Lily Pad - Fingering")
- `Lineitem quantity` → `OrderItem.quantity`
- `Lineitem price` → `OrderItem.unit_price`
- Calculate `OrderItem.line_total` = quantity × unit_price

#### Customer Mapping
- Shopify Customer → Fibermade Customer
- `Email` → `Customer.email` (primary identifier)
- `First Name` + `Last Name` → `Customer.name`
- `Default Address Address1` → `Customer.address_line1`
- `Default Address Address2` → `Customer.address_line2`
- `Default Address City` → `Customer.city`
- `Default Address Province Code` → `Customer.state_region`
- `Default Address Country Code` → `Customer.country_code`
- `Default Address Zip` → `Customer.postal_code`
- `Default Address Phone` → `Customer.phone`
- `Customer ID` (Shopify) → Store in `ExternalIdentifier` (external_type: 'shopify_customer')

### 3.3 Data Transformation Requirements

#### Base Descriptor Parsing
- Parse `Option1 Value` (e.g., "Lily Pad - Fingering") into:
  - `Base.descriptor` = "Lily Pad"
  - `Base.weight` = "Fingering" (map to Weight enum)
- Generate `Base.code` from descriptor initials
- Map weight strings to Weight enum values

#### Colorway Name Extraction
- Use product `Title` or `Handle` as colorway name
- Clean up names (remove special characters if needed)
- Handle duplicate names (add suffix or merge)

#### Address Normalization
- Standardize address formats
- Handle missing/empty fields
- Validate country codes

#### Date/Time Parsing
- Parse Shopify datetime format: "2025-12-31 17:42:16 -0600"
- Convert to UTC or local timezone as needed
- Handle missing dates gracefully

### 3.4 Handling Missing/Invalid Data

#### Missing Customers
- If order has email but no customer exists, create customer record
- If order has no email, skip customer association (or create anonymous customer)

#### Missing Colorways/Bases
- Log warnings for missing colorways/bases
- Option 1: Skip order items that can't be mapped
- Option 2: Create placeholder colorways/bases
- Option 3: Fail import with detailed error report

#### Negative Inventory
- CSV shows negative "Available" values (backorders)
- Decision: Import as-is (negative quantities) or set to 0?
- Recommendation: Import as-is to reflect true inventory state

#### Duplicate Handling
- Check for existing records using `ExternalIdentifier`
- Update existing records vs. create new ones?
- Recommendation: Use upsert logic based on external IDs

### 3.5 Performance Considerations

#### Batch Processing
- Process CSV in chunks (e.g., 100 rows at a time)
- Use database transactions for atomicity
- Use queued jobs for large imports

#### Database Queries
- Eager load relationships to avoid N+1 queries
- Use `upsert()` for bulk inserts/updates
- Create indexes on lookup fields (email, external IDs)

#### Memory Management
- Stream CSV files instead of loading entirely into memory
- Use generators for large datasets
- Clear caches between batches

### 3.6 Error Handling & Logging

#### Validation Errors
- Validate data before import
- Collect all errors and report at end
- Create detailed error log file

#### Missing Relationships
- Log missing colorway/base mappings
- Create report of unmapped items
- Allow manual mapping for edge cases

#### Integration Logging
- Use `IntegrationLog` model to track import progress
- Log successes, failures, warnings
- Store metadata about import run

### 3.7 Recommended Import Process

1. **Pre-import Validation**
   - Validate CSV file format
   - Check for required columns
   - Validate account exists
   - Check integration exists/created

2. **Base Import** (from products CSV)
   - Extract unique bases from `Option1 Value`
   - Parse descriptor and weight
   - Create/update Base records
   - Create ExternalIdentifier mappings

3. **Colorway Import** (from products CSV)
   - Extract unique colorways from product titles/handles
   - Create/update Colorway records
   - Create ExternalIdentifier mappings (product handle → colorway)

4. **Inventory Import** (from inventory CSV)
   - Map colorway + base combinations
   - Create/update Inventory records
   - Handle negative quantities appropriately

5. **Customer Import** (from customers CSV)
   - Create/update Customer records
   - Create ExternalIdentifier mappings
   - Handle duplicate emails (merge or skip)

6. **Order Import** (from orders CSV)
   - Group order lines by order ID/Name
   - Create Order records
   - Create OrderItem records
   - Create ExternalIdentifier mappings
   - Link to customers

7. **Post-import Verification**
   - Verify record counts match expectations
   - Check for orphaned records
   - Validate relationships
   - Generate import report

### 3.8 Code Structure Recommendations

#### Import Command
Create Artisan command: `php artisan import:shopify-csv {account_id} {file_path}`

#### Service Classes
- `ShopifyCsvImporter` - Main import orchestrator
- `ShopifyBaseImporter` - Base import logic
- `ShopifyColorwayImporter` - Colorway import logic
- `ShopifyInventoryImporter` - Inventory import logic
- `ShopifyCustomerImporter` - Customer import logic
- `ShopifyOrderImporter` - Order import logic

#### Parsers
- `BaseDescriptorParser` - Parse base names into descriptor + weight
- `ColorwayNameExtractor` - Extract colorway names from product data
- `OrderLineItemParser` - Parse order line items into colorway + base

#### Mappers
- `ShopifyToFibermadeMapper` - Map Shopify data to Fibermade models
- `ExternalIdentifierMapper` - Create external identifier mappings

---

## 4. Additional Considerations

### 4.1 Account Scoping
- All imported data must be scoped to a specific account
- Ensure account_id is set on all records
- Validate account exists before import

### 4.2 Integration Tracking
- Create/identify Shopify integration record
- Use integration for all ExternalIdentifier entries
- Track import runs in IntegrationLog

### 4.3 Data Integrity
- Maintain referential integrity (orders → customers, order_items → colorways/bases)
- Handle soft deletes appropriately
- Preserve created_at/updated_at from CSV if needed

### 4.4 Idempotency
- Import should be idempotent (running twice should not duplicate data)
- Use ExternalIdentifier to check for existing records
- Use upsert logic based on external IDs

### 4.5 Testing Strategy
- Test with small CSV sample first
- Test edge cases (missing data, duplicates, invalid formats)
- Test rollback scenarios
- Verify data integrity after import

### 4.6 Stage 1 Constraints
- Focus on production planning needs (orders, inventory, colorways, bases)
- Skip discount code management (Stage 1 non-goal)
- Skip customer marketing preferences (Stage 1 non-goal)
- Keep it simple - import what's needed for Dye Lists and inventory truth

---

## 5. Quick Reference: CSV → Database Mapping

### Products CSV → Colorways & Bases
| CSV Field | Database Field | Model |
|-----------|---------------|-------|
| `Title` or `Handle` | `name` | Colorway |
| `Option1 Value` | `descriptor` + `weight` | Base |
| `Variant Price` | `retail_price` | Base |
| `Cost per item` | `cost` | Base |
| `Handle` | ExternalIdentifier (external_type: 'shopify_product') | ExternalIdentifier |
| `Variant SKU` | ExternalIdentifier (external_type: 'shopify_variant') | ExternalIdentifier |

### Inventory CSV → Inventory
| CSV Field | Database Field | Model |
|-----------|---------------|-------|
| `Handle` | Find Colorway by name | Colorway |
| `Option1 Value` | Find Base by descriptor+weight | Base |
| `Available` | `quantity` | Inventory |

### Customers CSV → Customers
| CSV Field | Database Field | Model |
|-----------|---------------|-------|
| `Email` | `email` | Customer |
| `First Name` + `Last Name` | `name` | Customer |
| `Default Address Address1` | `address_line1` | Customer |
| `Default Address Address2` | `address_line2` | Customer |
| `Default Address City` | `city` | Customer |
| `Default Address Province Code` | `state_region` | Customer |
| `Default Address Country Code` | `country_code` | Customer |
| `Default Address Zip` | `postal_code` | Customer |
| `Default Address Phone` | `phone` | Customer |
| `Customer ID` | ExternalIdentifier (external_type: 'shopify_customer') | ExternalIdentifier |

### Orders CSV → Orders & OrderItems
| CSV Field | Database Field | Model |
|-----------|---------------|-------|
| `Created at` | `order_date` | Order |
| `Subtotal` | `subtotal_amount` | Order |
| `Shipping` | `shipping_amount` | Order |
| `Discount Amount` | `discount_amount` | Order |
| `Taxes` | `tax_amount` | Order |
| `Total` | `total_amount` | Order |
| `Email` | Find Customer, set `orderable_type`='Customer', `orderable_id` | Order |
| `Financial Status` + `Fulfillment Status` | `status` (map to OrderStatus enum) | Order |
| `Id` | ExternalIdentifier (external_type: 'shopify_order') | ExternalIdentifier |
| `Lineitem name` | Parse to find Colorway + Base | OrderItem |
| `Lineitem quantity` | `quantity` | OrderItem |
| `Lineitem price` | `unit_price` | OrderItem |
| `Lineitem quantity` × `Lineitem price` | `line_total` | OrderItem |

---

## 6. Next Steps

1. **Review this analysis** - Validate assumptions and mappings
2. **Create import command structure** - Set up Artisan command and service classes
3. **Build parsers** - Create parsers for base descriptors, colorway names, order line items
4. **Implement base/colorway import** - Start with simpler data (bases, colorways, inventory)
5. **Implement customer import** - Test customer import logic
6. **Implement order import** - Most complex, depends on previous imports
7. **Add error handling** - Comprehensive error reporting and logging
8. **Test with sample data** - Validate with small CSV sample
9. **Full import** - Run full import with error monitoring
10. **Verification** - Verify data integrity and relationships
