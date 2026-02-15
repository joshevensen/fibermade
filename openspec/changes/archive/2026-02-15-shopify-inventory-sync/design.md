## Context

Fibermade manages colorways and bases as separate, shared entities (flat structure), while Shopify uses a hierarchical product→variant model. This architectural mismatch creates complexity in bidirectional sync:

- **Fibermade**: Colorways and Bases exist independently; Inventory tracks each Colorway × Base combination
- **Shopify**: Each Product owns its variants; variants are not shared between products

Current state:
- ImportService creates Bases and Inventory records but hardcodes quantity to 0
- No ExternalIdentifier mapping between Inventory and Shopify variants
- No automatic catalog sync (manual Shopify edits diverge from Fibermade)
- Base→Variant ExternalIdentifiers exist but are incorrect (bases are shared, variants are not)

## Goals / Non-Goals

**Goals:**
- Enable bidirectional inventory sync (Fibermade ⇄ Shopify)
- Pull actual inventory quantities during initial import
- Keep Shopify catalog automatically synchronized with Fibermade changes
- Handle structural mismatch: shared Bases → unique Shopify variants per product
- Implement Fibermade-as-source-of-truth philosophy for catalog management

**Non-Goals:**
- Real-time sync (manual push + webhook-based pull is sufficient)
- Shopify Collections sync (out of scope for this change)
- SKU management (not used by dyers)
- Bi-directional catalog sync (Shopify→Fibermade for products; only inventory pulls back)

## Decisions

### Decision 1: Inventory → Variant Mapping (Not Base → Variant)

**Choice**: Create ExternalIdentifier records linking Inventory (Colorway × Base) → Shopify variant_id

**Rationale**: 
- Shopify variants are unique per product (not shared)
- Fibermade Bases are shared across all colorways
- Mapping Base→Variant breaks when same base name exists in multiple products
- Inventory record represents the unique combination that maps 1:1 with a Shopify variant

**Alternatives considered**:
- Base → Variant mapping: Fails because bases are shared (one Base "Fingering", many Shopify "Fingering" variants)
- Name matching: Brittle, user error-prone, fails with typos or renaming

**Implementation**:
```
ExternalIdentifier:
  identifiable_type: 'Inventory'
  identifiable_id: inventory.id
  external_type: 'shopify_variant'
  external_id: shopify_variant_id
```

### Decision 2: Create Variants for ALL Bases (Including qty=0)

**Choice**: When pushing colorway to Shopify, create variants for every base in account (even if inventory = 0)

**Rationale**:
- Maintains complete product structure from the start
- Matches account's base offerings
- Avoids creating incomplete products that confuse customers
- Simplifies future inventory updates (variant already exists)

**Alternatives considered**:
- Only create variants with qty > 0: Would require later variant creation, complicates sync logic
- Let user choose: Adds unnecessary complexity

### Decision 3: Immediate Catalog Sync via Model Observers

**Choice**: Use Laravel model observers to immediately sync catalog changes to Shopify

**Triggers**:
- Colorway observer: name, description, status, colors, technique changes → update Shopify product
- Base observer: descriptor, retail_price changes → update all Shopify variants using that base
- Base observer: creation → add variant to ALL Shopify products
- Base observer: deletion → delete variants from ALL Shopify products
- Media observer: image changes → sync images (is_primary first)

**Rationale**:
- Fibermade is source of truth - Shopify must stay synchronized
- Immediate updates prevent divergence
- Simpler than batch jobs or manual triggers

**Alternatives considered**:
- Lazy sync on next manual push: Allows temporary divergence, confusing for users
- Background queue job: Adds delay, more complex error handling
- Manual sync button for catalog: Extra work for users, defeats "source of truth" principle

**Risks**: Observer failures could block user actions → Mitigation: Use queued observers with retry logic

### Decision 4: Import Price Conflict Strategy

**Choice**: Use first encountered price for shared base, log warnings for conflicts

**Rationale**:
- Shopify allows per-product variant pricing, Fibermade uses shared base pricing
- First-wins is predictable and simple
- Warnings allow manual review and adjustment
- Better than silently overwriting or averaging prices

**Example conflict**:
```
Product "Mountain Mist", variant "Fingering": $28
Product "Ocean Breeze", variant "Fingering": $32

Result: Base "Fingering" = $28 (first), warning logged for $32 difference
```

**Alternatives considered**:
- Median/average price: Hides the conflict, may not match any actual product
- Prompt during import: Slows down import, poor UX for batch operations
- Reject import: Too strict, blocks valid use cases

### Decision 5: Shopify Product/Variant Field Mapping

**Colorway → Product**:
- title → name
- descriptionHtml → description (convert to HTML if needed)
- productType → "Yarn" (hardcoded)
- vendor → Account.name
- tags → colors + technique
- status → map ColorwayStatus (Active→ACTIVE, Retired→ARCHIVED, Idea→DRAFT)
- images → Media (is_primary first)

**Base → Variant Options**:
- option1 → descriptor (e.g., "Fingering", "Worsted")
- price → retail_price
- inventoryQuantity → Inventory.quantity

**Metafields (Fibermade namespace)**:
- per_pan → Store for future reference

**Choice**: Omit recipe, notes, technique from metafields (per_pan only)

**Rationale**: These are internal production data, not needed in Shopify

### Decision 6: Service Layer Architecture

**InventorySyncService**: Push/pull inventory quantities
- pushInventoryToShopify(Inventory $inventory): Update specific variant
- pushAllInventoryForColorway(Colorway $colorway): Sync all variants for one product
- pullInventoryFromShopify(string $variantId): Update from webhook

**ShopifySyncService**: Shopify API mutations
- createProduct(Colorway $colorway): Create product + variants
- updateProduct(Colorway $colorway): Update product fields
- deleteProduct(Colorway $colorway): Archive/delete product
- createVariant(Colorway $colorway, Base $base): Add missing variant
- updateVariant(Inventory $inventory, Base $base): Update variant pricing/options
- deleteVariant(Inventory $inventory): Remove variant
- syncImages(Colorway $colorway): Upload/update product images

**Choice**: Separate services for inventory vs catalog operations

**Rationale**: 
- Clear separation of concerns
- Inventory operations are high-frequency (manual + webhooks)
- Catalog operations are lower-frequency (model observers)
- Easier testing and maintenance

### Decision 7: New Base Rollout Strategy

**Choice**: When new Base added, immediately create variants for ALL existing Shopify products

**Rationale**:
- Maintains product completeness
- Matches account's base offerings
- Prevents confusion (missing options)

**Implementation**: Base observer triggers ShopifySyncService to:
1. Find all Colorways with shopify_product external_id
2. For each, create variant for new Base (qty=0)
3. Create Inventory and ExternalIdentifier records

**Alternative considered**: Lazy creation on next manual push → Rejected (temporary incompleteness)

## Risks / Trade-offs

**Risk**: Observer sync failures block user actions
→ **Mitigation**: Queue observer jobs, implement retry logic, fail gracefully with notifications

**Risk**: High-frequency Base changes cause API rate limits (e.g., changing descriptor updates all variants)
→ **Mitigation**: Debounce updates, batch operations where possible, add rate limit monitoring

**Risk**: Import price conflicts create incorrect base pricing
→ **Mitigation**: Prominent warning display, allow manual adjustment post-import, document expected behavior

**Risk**: Large catalogs (100+ colorways, 10+ bases) = 1000+ API calls on Base changes
→ **Mitigation**: Queue jobs, progress indicators, consider batch API endpoints if Shopify adds support

**Risk**: Sync loop if Shopify webhook triggers Fibermade update which triggers Shopify update
→ **Mitigation**: Track sync source in IntegrationLog, skip reverse sync for changes originating from webhooks

**Risk**: Incomplete CSV data (missing variant_id) breaks Inventory→Variant mapping
→ **Mitigation**: Fall back to Shopify API fetch if CSV incomplete, validate before import

**Trade-off**: Immediate observer syncs increase API usage vs. batch efficiency
→ **Accepted**: User experience (immediate feedback) prioritized over API efficiency

**Trade-off**: Creating variants for all bases (even qty=0) inflates Shopify product complexity
→ **Accepted**: Complete product structure more valuable than minimal variant count

## Migration Plan

**Phase 1: Import fixes (non-breaking)**
1. Deploy ImportService changes
2. Existing imports unaffected (no ExternalIdentifiers yet)
3. New imports create Inventory→Variant links

**Phase 2: Manual inventory sync**
1. Deploy InventorySyncService + UI button
2. Test on single account
3. Roll out to all accounts

**Phase 3: Webhook pull (read-only)**
1. Register webhook in Shopify app
2. Deploy webhook controller
3. Test with manual Shopify inventory changes
4. Monitor for sync loops

**Phase 4: Automatic catalog sync (observers)**
1. Deploy ShopifySyncService + observers
2. Enable observers for beta accounts first
3. Monitor API usage and error rates
4. Roll out to all accounts

**Rollback strategy**:
- Phase 1-2: Disable features via feature flag, no data loss
- Phase 3-4: Disable observers via config, Shopify stays in last synced state

**Data migration**: None required (ExternalIdentifiers are additive)

## Open Questions

1. **Should Collections sync automatically too?**
   - Out of scope for this change, but architecture supports it
   - Consider for future iteration

2. **How to handle Shopify manual edits to products/variants?**
   - Current design: Fibermade overwrites on next sync
   - Alternative: Detect conflicts, prompt user
   - Decision: Defer to implementation, start with overwrite strategy

3. **Shopify API rate limits for large catalogs?**
   - Need to test with real-world catalog sizes
   - May need adaptive batching or throttling

4. **Should we sync variant weights (Base.weight)?**
   - Shopify has weight field for shipping
   - Not critical for MVP, consider for future
