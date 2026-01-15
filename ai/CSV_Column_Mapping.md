# CSV Column to Database Field Mapping

This document provides a comprehensive mapping of all CSV column headers to database fields. Columns are organized by CSV file.

**Legend:**
- ✅ **Direct Map** - Direct field mapping
- 🔄 **Transform** - Requires transformation/parsing
- 📝 **Notes/JSON** - Store in notes field or JSON metadata
- 🔗 **External ID** - Store in ExternalIdentifier table
- ❌ **Skip** - Field not needed for Stage 1 / database doesn't support
- ⚠️ **Manual** - Requires manual handling or separate import

---

## 1. customers_export.csv

| CSV Column | Database Field | Model | Type | Notes |
|-----------|---------------|-------|------|-------|
| `Customer ID` | `external_id` | ExternalIdentifier | 🔗 | external_type: 'shopify_customer' |
| `First Name` | `name` (concat with Last Name) | Customer | 🔄 | Combine with Last Name: "First Last" |
| `Last Name` | `name` (concat with First Name) | Customer | 🔄 | Combine with First Name: "First Last" |
| `Email` | `email` | Customer | ✅ | Primary identifier for matching |
| `Accepts Email Marketing` | - | - | ❌ | Stage 1 non-goal (marketing preferences) |
| `Default Address Company` | - | - | 📝 | Store in notes if needed |
| `Default Address Address1` | `address_line1` | Customer | ✅ | Direct mapping |
| `Default Address Address2` | `address_line2` | Customer | ✅ | Direct mapping |
| `Default Address City` | `city` | Customer | ✅ | Direct mapping |
| `Default Address Province Code` | `state_region` | Customer | ✅ | Direct mapping (e.g., "TX", "CA") |
| `Default Address Country Code` | `country_code` | Customer | ✅ | Direct mapping (e.g., "US") |
| `Default Address Zip` | `postal_code` | Customer | ✅ | Direct mapping |
| `Default Address Phone` | `phone` | Customer | ✅ | Direct mapping |
| `Phone` | `phone` (if Default Address Phone empty) | Customer | 🔄 | Fallback if Default Address Phone is empty |
| `Accepts SMS Marketing` | - | - | ❌ | Stage 1 non-goal (marketing preferences) |
| `Total Spent` | - | - | ❌ | Can be calculated from orders |
| `Total Orders` | - | - | ❌ | Can be calculated from orders |
| `Note` | `notes` | Customer | ✅ | Direct mapping |
| `Tax Exempt` | - | - | 📝 | Store in notes if needed for future |
| `Tags` | - | - | 📝 | Store in notes or JSON if needed |

---

## 2. discounts_export_1.csv

| CSV Column | Database Field | Model | Type | Notes |
|-----------|---------------|-------|------|-------|
| `Name` | - | - | 📝 | Store discount code name in orders.notes or skip (Stage 1 non-goal) |
| `Value` | - | - | ❌ | Not stored (discount amounts stored on orders) |
| `Value Type` | - | - | ❌ | Not stored |
| `Type` | - | - | ❌ | Not stored |
| `Discount Class` | - | - | ❌ | Not stored |
| `Minimum Purchase Requirements` | - | - | ❌ | Not stored |
| `Combines with Order Discounts` | - | - | ❌ | Not stored |
| `Combines with Product Discounts` | - | - | ❌ | Not stored |
| `Combines with Shipping Discounts` | - | - | ❌ | Not stored |
| `Customer Selection` | - | - | ❌ | Not stored |
| `Context` | - | - | ❌ | Not stored |
| `Times Used In Total` | - | - | ❌ | Not stored |
| `Applies Once Per Customer` | - | - | ❌ | Not stored |
| `Usage Limit Per Code` | - | - | ❌ | Not stored |
| `Status` | - | - | ❌ | Not stored |
| `Start` | - | - | ❌ | Not stored |
| `End` | - | - | ❌ | Not stored |

**Note:** Discount codes are Stage 1 non-goals. Discount amounts are stored on `orders.discount_amount`, but discount code details are not tracked.

---

## 3. inventory_export_1.csv

| CSV Column | Database Field | Model | Type | Notes |
|-----------|---------------|-------|------|-------|
| `Handle` | Find Colorway by name/handle | Colorway | 🔄 | Parse handle to find matching Colorway |
| `Title` | - | - | 🔄 | Used to find/create Colorway if Handle doesn't match |
| `Option1 Name` | - | - | ❌ | Always "Base" - can ignore |
| `Option1 Value` | Find Base by descriptor+weight | Base | 🔄 | Parse "Lily Pad - Fingering" → descriptor: "Lily Pad", weight: "Fingering" |
| `Option2 Name` | - | - | ❌ | Not used in this data |
| `Option2 Value` | - | - | ❌ | Not used in this data |
| `Option3 Name` | - | - | ❌ | Not used in this data |
| `Option3 Value` | - | - | ❌ | Not used in this data |
| `SKU` | `external_id` | ExternalIdentifier | 🔗 | external_type: 'shopify_variant_sku' (optional) |
| `HS Code` | - | - | ❌ | Customs code - not needed |
| `COO` | - | - | ❌ | Country of origin - not needed |
| `Location` | - | - | 📝 | Store in notes if needed (e.g., "Studio") |
| `Bin name` | - | - | ❌ | Not tracked |
| `Incoming (not editable)` | - | - | ❌ | Not tracked |
| `Unavailable (not editable)` | - | - | ❌ | Not tracked |
| `Committed (not editable)` | - | - | ❌ | Not tracked |
| `Available (not editable)` | `quantity` | Inventory | ✅ | **Primary field** - map to inventory.quantity |
| `On hand (current)` | - | - | ❌ | Use "Available" instead |
| `On hand (new)` | - | - | ❌ | Use "Available" instead |

---

## 4. orders_export_1.csv

### Order-Level Fields

| CSV Column | Database Field | Model | Type | Notes |
|-----------|---------------|-------|------|-------|
| `Name` | - | - | 📝 | Order name (e.g., "#1318") - can store in notes or skip |
| `Email` | Find Customer, set `orderable_type`='Customer', `orderable_id` | Order | 🔄 | Look up customer by email, set polymorphic relation |
| `Financial Status` | `status` (partial) | Order | 🔄 | Map: "paid" → OrderStatus::Paid, "partially_refunded" → OrderStatus::PartiallyRefunded |
| `Paid at` | - | - | 📝 | Store in notes or skip (date tracking not in schema) |
| `Fulfillment Status` | `status` (partial) | Order | 🔄 | Combine with Financial Status: "fulfilled" → OrderStatus::Fulfilled |
| `Fulfilled at` | - | - | 📝 | Store in notes or skip |
| `Accepts Marketing` | - | - | ❌ | Not needed (customer-level data) |
| `Currency` | - | - | 📝 | Store in notes if needed (assume USD) |
| `Subtotal` | `subtotal_amount` | Order | ✅ | Direct mapping |
| `Shipping` | `shipping_amount` | Order | ✅ | Direct mapping |
| `Taxes` | `tax_amount` | Order | ✅ | **Total taxes** - sum of all tax values |
| `Total` | `total_amount` | Order | ✅ | Direct mapping |
| `Discount Code` | - | - | 📝 | Store in notes (Stage 1 non-goal) |
| `Discount Amount` | `discount_amount` | Order | ✅ | Direct mapping |
| `Shipping Method` | - | - | 📝 | Store in notes (e.g., "Economy") |
| `Created at` | `order_date` | Order | 🔄 | Parse datetime, extract date |
| `Notes` | `notes` | Order | ✅ | Direct mapping |
| `Note Attributes` | - | - | 📝 | Append to notes if needed |
| `Cancelled at` | - | - | 📝 | Store in notes or set status to cancelled |
| `Payment Method` | - | - | 📝 | Store in notes (e.g., "Shopify Payments", "Cash") |
| `Payment Reference` | - | - | 📝 | Store in notes |
| `Refunded Amount` | - | - | 📝 | Store in notes (refund tracking not in schema) |
| `Vendor` | - | - | 📝 | Usually account name - can ignore or store in notes |
| `Outstanding Balance` | - | - | ❌ | Not needed (should be 0 for paid orders) |
| `Employee` | - | - | 📝 | Store in notes (employee attribution not in schema) |
| `Location` | - | - | 📝 | Store in notes (e.g., "Studio") |
| `Device ID` | - | - | ❌ | Not needed |
| `Id` | `external_id` | ExternalIdentifier | 🔗 | external_type: 'shopify_order' |
| `Tags` | - | - | 📝 | Store in notes if needed |
| `Risk Level` | - | - | 📝 | Store in notes (e.g., "Low") |
| `Source` | - | - | 📝 | Store in notes (e.g., "web", "pos", "shopify_draft_order") |
| `Phone` | - | - | ❌ | Customer-level data, not needed on order |
| `Receipt Number` | - | - | 📝 | Store in notes if needed |
| `Duties` | - | - | ❌ | Not tracked |
| `Billing Province Name` | - | - | ❌ | Use Billing Province code instead |
| `Shipping Province Name` | - | - | ❌ | Use Shipping Province code instead |
| `Payment ID` | - | - | 📝 | Store in notes or JSON metadata |
| `Payment Terms Name` | - | - | 📝 | Store in notes (wholesale terms not in schema) |
| `Next Payment Due At` | - | - | 📝 | Store in notes if needed |
| `Payment References` | - | - | 📝 | Store in notes |

### Billing Address Fields

| CSV Column | Database Field | Model | Type | Notes |
|-----------|---------------|-------|------|-------|
| `Billing Name` | - | - | ❌ | Customer name stored on Customer model |
| `Billing Street` | - | - | ❌ | Use Billing Address1 |
| `Billing Address1` | - | - | 📝 | Store in notes if billing differs from shipping |
| `Billing Address2` | - | - | 📝 | Store in notes if billing differs from shipping |
| `Billing Company` | - | - | 📝 | Store in notes if needed |
| `Billing City` | - | - | 📝 | Store in notes if billing differs from shipping |
| `Billing Zip` | - | - | 📝 | Store in notes if billing differs from shipping |
| `Billing Province` | - | - | 📝 | Store in notes if billing differs from shipping |
| `Billing Country` | - | - | 📝 | Store in notes if billing differs from shipping |
| `Billing Phone` | - | - | 📝 | Store in notes if billing differs from shipping |

**Note:** Billing address is typically same as shipping for retail orders. Customer address is primary source of truth.

### Shipping Address Fields

| CSV Column | Database Field | Model | Type | Notes |
|-----------|---------------|-------|------|-------|
| `Shipping Name` | - | - | ❌ | Customer name stored on Customer model |
| `Shipping Street` | - | - | ❌ | Use Shipping Address1 |
| `Shipping Address1` | - | - | ❌ | Update Customer.address_line1 if different |
| `Shipping Address2` | - | - | ❌ | Update Customer.address_line2 if different |
| `Shipping Company` | - | - | 📝 | Store in Customer.notes if needed |
| `Shipping City` | - | - | ❌ | Update Customer.city if different |
| `Shipping Zip` | - | - | ❌ | Update Customer.postal_code if different |
| `Shipping Province` | - | - | ❌ | Update Customer.state_region if different |
| `Shipping Country` | - | - | ❌ | Update Customer.country_code if different |
| `Shipping Phone` | - | - | ❌ | Update Customer.phone if different |

**Note:** Shipping address updates Customer record. Order doesn't store separate shipping address.

### Tax Detail Fields

| CSV Column | Database Field | Model | Type | Notes |
|-----------|---------------|-------|------|-------|
| `Tax 1 Name` | - | - | 📝 | Store tax breakdown in notes or JSON if needed |
| `Tax 1 Value` | Sum to `tax_amount` | Order | 🔄 | Add all tax values (Tax 1-5) to get total |
| `Tax 2 Name` | - | - | 📝 | Store tax breakdown in notes or JSON if needed |
| `Tax 2 Value` | Sum to `tax_amount` | Order | 🔄 | Add all tax values (Tax 1-5) to get total |
| `Tax 3 Name` | - | - | 📝 | Store tax breakdown in notes or JSON if needed |
| `Tax 3 Value` | Sum to `tax_amount` | Order | 🔄 | Add all tax values (Tax 1-5) to get total |
| `Tax 4 Name` | - | - | 📝 | Store tax breakdown in notes or JSON if needed |
| `Tax 4 Value` | Sum to `tax_amount` | Order | 🔄 | Add all tax values (Tax 1-5) to get total |
| `Tax 5 Name` | - | - | 📝 | Store tax breakdown in notes or JSON if needed |
| `Tax 5 Value` | Sum to `tax_amount` | Order | 🔄 | Add all tax values (Tax 1-5) to get total |

**Note:** Sum all tax values (Tax 1 Value + Tax 2 Value + ... + Tax 5 Value) to populate `orders.tax_amount`.

### Line Item Fields

| CSV Column | Database Field | Model | Type | Notes |
|-----------|---------------|-------|------|-------|
| `Lineitem quantity` | `quantity` | OrderItem | ✅ | Direct mapping |
| `Lineitem name` | Parse to find Colorway + Base | OrderItem | 🔄 | Parse "Colorway - Base" format (e.g., "Fruitcake - Lily Pad - Fingering") |
| `Lineitem price` | `unit_price` | OrderItem | ✅ | Direct mapping |
| `Lineitem compare at price` | - | - | ❌ | Not tracked (original price before discount) |
| `Lineitem sku` | - | - | 📝 | Store in ExternalIdentifier if needed (external_type: 'shopify_line_item_sku') |
| `Lineitem requires shipping` | - | - | ❌ | Not needed (all items ship) |
| `Lineitem taxable` | - | - | ❌ | Not needed (tax already calculated) |
| `Lineitem fulfillment status` | - | - | 📝 | Store in notes if needed |
| `Lineitem discount` | - | - | 📝 | Discount already reflected in unit_price, store in notes if needed |

**Note:** Each row in orders CSV represents a line item. Group rows by order Name/Id to create one Order record with multiple OrderItem records.

---

## 5. products_export_1.csv

### Product-Level Fields

| CSV Column | Database Field | Model | Type | Notes |
|-----------|---------------|-------|------|-------|
| `Handle` | `external_id` | ExternalIdentifier | 🔗 | external_type: 'shopify_product' (product handle) |
| `Title` | `name` | Colorway | ✅ | Product title becomes Colorway name |
| `Body (HTML)` | `description` | Colorway | 🔄 | Clean HTML, store as text |
| `Vendor` | - | - | ❌ | Usually account name - can ignore |
| `Product Category` | - | - | ❌ | Not tracked |
| `Type` | - | - | 📝 | Store in notes if needed (e.g., "100g Skein") |
| `Tags` | - | - | 📝 | Store in notes or use to create Collections |
| `Published` | `status` | Colorway | 🔄 | Map: true → ColorwayStatus::Active, false → ColorwayStatus::Retired |
| `SEO Title` | - | - | ❌ | Not needed |
| `SEO Description` | - | - | ❌ | Not needed |
| `Color (product.metafields.shopify.color-pattern)` | `colors` (JSON) | Colorway | 🔄 | Parse colors from metafield, map to Color enum array |
| `Fabric (product.metafields.shopify.fabric)` | - | - | 📝 | Store in notes if needed |
| `Complementary products` | - | - | ❌ | Not tracked |
| `Related products` | - | - | ❌ | Not tracked |
| `Related products settings` | - | - | ❌ | Not tracked |
| `Search product boosts` | - | - | ❌ | Not tracked |
| `Image Src` | - | - | ⚠️ | Store in Media model (separate import step) |
| `Image Position` | - | - | ⚠️ | Store in Media model (separate import step) |
| `Image Alt Text` | - | - | ⚠️ | Store in Media model (separate import step) |
| `Gift Card` | - | - | ❌ | Not tracked (gift cards not products) |

### Variant-Level Fields

| CSV Column | Database Field | Model | Type | Notes |
|-----------|---------------|-------|------|-------|
| `Option1 Name` | - | - | ❌ | Usually "Base" - can ignore |
| `Option1 Value` | Parse to `descriptor` + `weight` | Base | 🔄 | Parse "Lily Pad - Fingering" → descriptor: "Lily Pad", weight: "Fingering" |
| `Option1 Linked To` | - | - | ❌ | Not used |
| `Option2 Name` | - | - | ❌ | Not used in this data |
| `Option2 Value` | - | - | ❌ | Not used in this data |
| `Option2 Linked To` | - | - | ❌ | Not used |
| `Option3 Name` | - | - | ❌ | Not used in this data |
| `Option3 Value` | - | - | ❌ | Not used in this data |
| `Option3 Linked To` | - | - | ❌ | Not used |
| `Variant SKU` | `external_id` | ExternalIdentifier | 🔗 | external_type: 'shopify_variant' |
| `Variant Grams` | - | - | 📝 | Store in notes if needed (weight in grams) |
| `Variant Inventory Tracker` | - | - | ❌ | Not needed (always "shopify") |
| `Variant Inventory Qty` | - | - | ❌ | Use inventory CSV instead |
| `Variant Inventory Policy` | - | - | ❌ | Not needed (always "continue") |
| `Variant Fulfillment Service` | - | - | ❌ | Not needed (always "manual") |
| `Variant Price` | `retail_price` | Base | ✅ | Direct mapping |
| `Variant Compare At Price` | - | - | ❌ | Not tracked (original price) |
| `Variant Requires Shipping` | - | - | ❌ | Not needed (all items ship) |
| `Variant Taxable` | - | - | ❌ | Not needed (tax handled at order level) |
| `Unit Price Total Measure` | - | - | ❌ | Not tracked |
| `Unit Price Total Measure Unit` | - | - | ❌ | Not tracked |
| `Unit Price Base Measure` | - | - | ❌ | Not tracked |
| `Unit Price Base Measure Unit` | - | - | ❌ | Not tracked |
| `Variant Barcode` | - | - | 📝 | Store in ExternalIdentifier data JSON if needed |
| `Variant Image` | - | - | ⚠️ | Store in Media model (separate import step) |
| `Variant Weight Unit` | - | - | ❌ | Not needed (weight tracked via Weight enum) |
| `Variant Tax Code` | - | - | ❌ | Not needed |
| `Cost per item` | `cost` | Base | ✅ | Direct mapping (base cost) |
| `Status` | `status` | Base | 🔄 | Map: "active" → BaseStatus::Active, "archived" → BaseStatus::Retired |

**Note:** Each row represents a variant. Multiple rows with same Handle = same product (Colorway) with different variants (Bases).

---

## Summary by Type

### Direct Mappings (✅)
- **Customer:** Email, address fields (address_line1, address_line2, city, state_region, postal_code, country_code, phone), notes
- **Order:** subtotal_amount, shipping_amount, discount_amount, tax_amount, total_amount, notes, order_date
- **OrderItem:** quantity, unit_price, line_total (calculated)
- **Base:** retail_price, cost, status
- **Colorway:** name (from Title), description (from Body HTML), status (from Published)

### Transformations Required (🔄)
- **Customer name:** Combine First Name + Last Name
- **Order status:** Map Financial Status + Fulfillment Status → OrderStatus enum
- **Order date:** Parse datetime, extract date
- **Base descriptor/weight:** Parse "Lily Pad - Fingering" → descriptor + weight enum
- **Colorway name:** Extract from product Title/Handle
- **Order items:** Parse "Colorway - Base" format to find Colorway and Base
- **Tax amount:** Sum Tax 1-5 values
- **Customer lookup:** Find Customer by email for order.orderable relationship
- **Colorway status:** Map Published boolean → ColorwayStatus enum
- **Base status:** Map Status string → BaseStatus enum
- **Colorway colors:** Parse metafield to Color enum array

### External Identifier Mappings (🔗)
- **Customer ID** → ExternalIdentifier (external_type: 'shopify_customer')
- **Order Id** → ExternalIdentifier (external_type: 'shopify_order')
- **Product Handle** → ExternalIdentifier (external_type: 'shopify_product')
- **Variant SKU** → ExternalIdentifier (external_type: 'shopify_variant')
- **Inventory SKU** (optional) → ExternalIdentifier (external_type: 'shopify_variant_sku')
- **Lineitem SKU** (optional) → ExternalIdentifier (external_type: 'shopify_line_item_sku')

### Notes/JSON Storage (📝)
- Discount codes, shipping methods, payment methods, employee, location, source, risk level, tags
- Tax breakdown details (if needed)
- Billing address (if different from shipping)
- Variant metadata (barcode, grams, etc.)

### Skip (❌)
- Marketing preferences (Stage 1 non-goal)
- Discount code details (Stage 1 non-goal)
- SEO fields
- Fulfillment service details
- Compare-at prices
- Gift card products
- Product recommendations/related products
- Customer aggregates (Total Spent, Total Orders - can calculate)

### Manual/Separate Import (⚠️)
- Product images → Media model (separate import process)
- Variant images → Media model (separate import process)

---

## Import Order Recommendations

1. **Integration** - Create/identify Shopify integration
2. **Bases** - Import from products CSV (Option1 Value)
3. **Colorways** - Import from products CSV (Title/Handle)
4. **Inventory** - Import from inventory CSV (after Bases/Colorways exist)
5. **Customers** - Import from customers CSV
6. **Orders** - Import from orders CSV (after Customers exist)
7. **OrderItems** - Import from orders CSV line items (after Orders, Colorways, Bases exist)
8. **ExternalIdentifiers** - Create throughout import process
9. **Media** - Import images separately (after Colorways exist)
