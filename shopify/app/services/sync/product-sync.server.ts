import type { FibermadeClient } from "../fibermade-client.server";
import type {
  BaseData,
  CreateColorwayPayload,
  CreateBasePayload,
} from "../fibermade-client.types";
import { EXTERNAL_TYPES, IDENTIFIABLE_TYPES } from "./constants";
import type { ShopifyGraphqlRunner } from "./metafields.server";
import { setProductAndVariantMetafields } from "./metafields.server";
import {
  createMapping,
  findFibermadeIdByShopifyGid,
  findShopifyGidByFibermadeId,
  mappingExists,
} from "./mapping.server";
import type { ProductSyncResult, ShopifyProduct, ShopifyVariant } from "./types";
import { getVariants } from "./types";

const DEFAULT_TITLE = "Default Title";
const PER_PAGE_BASES = 100;

const STATUS_MAP: Record<string, string> = {
  ACTIVE: "active",
  DRAFT: "idea",
  ARCHIVED: "retired",
};

/**
 * Extracts the numeric ID from a Shopify GID (e.g. gid://shopify/Product/123 -> "123").
 */
export function parseNumericIdFromGid(gid: string): string {
  return gid.split("/").pop() ?? "";
}

/**
 * Maps a Shopify product to a Colorway create payload.
 */
export function mapProductToColorwayPayload(product: ShopifyProduct): CreateColorwayPayload {
  const status = product.status in STATUS_MAP ? STATUS_MAP[product.status] : "active";
  return {
    name: product.title?.trim() || "Untitled",
    description: product.descriptionHtml?.trim() || null,
    per_pan: 1,
    status,
  };
}

/**
 * Maps a Shopify variant to a Base create payload.
 * Uses product title when variant title is "Default Title".
 */
export function mapVariantToBasePayload(
  variant: ShopifyVariant,
  productTitle: string
): CreateBasePayload {
  const descriptor =
    variant.title?.trim() === DEFAULT_TITLE ? productTitle?.trim() || "Untitled" : variant.title?.trim() || "Untitled";
  const priceStr = variant.price?.trim();
  const retail_price =
    priceStr !== undefined && priceStr !== "" ? parseFloat(priceStr) : undefined;
  return {
    descriptor,
    status: "active",
    retail_price: retail_price ?? undefined,
    weight: null,
  };
}

/**
 * Finds an existing Base by descriptor (exact match first, then normalized).
 * Optionally matches retail_price (compares as number; BaseData.retail_price is string from API).
 */
export function findExistingBase(
  bases: BaseData[],
  descriptor: string,
  retailPrice?: number | null
): BaseData | null {
  const trimmed = descriptor.trim();
  const exact = bases.find(
    (b) =>
      b.descriptor === trimmed &&
      (retailPrice == null ||
        (b.retail_price != null && parseFloat(b.retail_price) === retailPrice))
  );
  if (exact) return exact;

  const normalized = trimmed.replace(/\s+/g, "").toLowerCase();
  for (const b of bases) {
    const candidate = (b.descriptor ?? "").replace(/\s+/g, "").toLowerCase();
    if (candidate === normalized) {
      if (
        retailPrice == null ||
        (b.retail_price != null && parseFloat(b.retail_price) === retailPrice)
      ) {
        return b;
      }
    }
  }
  return null;
}

async function fetchAllBases(client: FibermadeClient): Promise<BaseData[]> {
  const all: BaseData[] = [];
  let page = 1;
  let hasMore = true;
  while (hasMore) {
    const res = await client.listBases({ page, per_page: PER_PAGE_BASES });
    const data = res.data ?? [];
    all.push(...data);
    const total = res.meta?.total ?? 0;
    hasMore = all.length < total;
    page += 1;
  }
  return all;
}

export class ProductSyncService {
  constructor(
    private readonly client: FibermadeClient,
    private readonly integrationId: number,
    private readonly shopDomain: string,
    private readonly shopifyGraphql?: ShopifyGraphqlRunner
  ) {}

  async updateProduct(product: ShopifyProduct): Promise<void> {
    const result = await findFibermadeIdByShopifyGid(
      this.client,
      this.integrationId,
      EXTERNAL_TYPES.SHOPIFY_PRODUCT,
      product.id
    );
    if (!result || result.identifiableType !== IDENTIFIABLE_TYPES.COLORWAY) {
      await this.importProduct(product);
      return;
    }

    const colorwayId = result.identifiableId;
    await this.client.updateColorway(
      colorwayId,
      mapProductToColorwayPayload(product)
    );

    const colorway = await this.client.getColorway(colorwayId);
    const inventories = colorway.inventories ?? [];
    const payloadVariantIds = new Set(
      getVariants(product).map((v) => v.id)
    );
    const candidateBases = await fetchAllBases(this.client);
    const variantMetafieldInputs: { variantGid: string; baseId: number }[] = [];
    const productNumericIdForVariant = parseNumericIdFromGid(product.id);

    for (const inv of inventories) {
      const variantGid = await findShopifyGidByFibermadeId(
        this.client,
        this.integrationId,
        IDENTIFIABLE_TYPES.INVENTORY,
        inv.id,
        EXTERNAL_TYPES.SHOPIFY_VARIANT
      );
      if (variantGid && !payloadVariantIds.has(variantGid)) {
        await this.client.updateBase(inv.base_id, { status: "retired" });
      }
    }

    for (const variant of getVariants(product)) {
      const variantResult = await findFibermadeIdByShopifyGid(
        this.client,
        this.integrationId,
        EXTERNAL_TYPES.SHOPIFY_VARIANT,
        variant.id
      );
      if (
        variantResult &&
        variantResult.identifiableType === IDENTIFIABLE_TYPES.INVENTORY
      ) {
        const inventory = await this.client.getInventory(
          variantResult.identifiableId
        );
        const basePayload = mapVariantToBasePayload(
          variant,
          product.title ?? ""
        );
        const retailPrice =
          variant.price?.trim() !== ""
            ? parseFloat(variant.price)
            : undefined;
        await this.client.updateBase(inventory.base_id, {
          descriptor: basePayload.descriptor,
          retail_price: basePayload.retail_price ?? undefined,
        });
      } else {
        try {
          const basePayload = mapVariantToBasePayload(
            variant,
            product.title ?? ""
          );
          const retailPrice =
            variant.price?.trim() !== ""
              ? parseFloat(variant.price)
              : undefined;
          let baseId: number;
          const existing = findExistingBase(
            candidateBases,
            basePayload.descriptor,
            retailPrice ?? null
          );
          if (existing) {
            baseId = existing.id;
          } else {
            const base = await this.client.createBase(basePayload);
            baseId = base.id;
            candidateBases.push(base);
          }
          variantMetafieldInputs.push({ variantGid: variant.id, baseId });

          const inventory = await this.client.createInventory({
            colorway_id: colorwayId,
            base_id: baseId,
            quantity: 0,
          });

          const variantNumericId = parseNumericIdFromGid(variant.id);
          await createMapping(
            this.client,
            this.integrationId,
            IDENTIFIABLE_TYPES.INVENTORY,
            inventory.id,
            EXTERNAL_TYPES.SHOPIFY_VARIANT,
            variant.id,
            {
              admin_url: `https://${this.shopDomain}/admin/products/${productNumericIdForVariant}/variants/${variantNumericId}`,
            }
          );
        } catch (err) {
          console.error(
            `ProductSyncService.updateProduct: failed to sync variant ${variant.id}:`,
            err instanceof Error ? err.message : String(err)
          );
        }
      }
    }

    if (this.shopifyGraphql && variantMetafieldInputs.length > 0) {
      await setProductAndVariantMetafields(
        this.shopifyGraphql,
        product.id,
        colorwayId,
        variantMetafieldInputs
      );
    }

    const productName = product.title?.trim() || "Untitled";
    const message = `Updated Shopify product '${productName}' (Colorway #${colorwayId})`;
    await this.logIntegration(
      product.id,
      "success",
      message,
      { shopify_gid: product.id },
      colorwayId
    );
  }

  async importProduct(product: ShopifyProduct): Promise<ProductSyncResult> {
    const exists = await mappingExists(
      this.client,
      this.integrationId,
      EXTERNAL_TYPES.SHOPIFY_PRODUCT,
      product.id
    );
    if (exists) {
      return this.buildSkippedResult(product.id);
    }

    try {
      return await this.runImport(product);
    } catch (err) {
      await this.logIntegration(
        product.id,
        "error",
        err instanceof Error ? err.message : String(err),
        { shopify_gid: product.id }
      );
      throw err;
    }
  }

  private async runImport(product: ShopifyProduct): Promise<ProductSyncResult> {
    const colorwayPayload = mapProductToColorwayPayload(product);
    const colorway = await this.client.createColorway(colorwayPayload);
    const colorwayId = colorway.id;

    const primaryImageUrl = product.featuredImage?.url ?? null;
    if (primaryImageUrl) {
      // TODO: Create Media record when platform exposes a Media create endpoint (file_path = CDN URL, metadata = { source: "shopify", original_url }).
    }

    const productNumericId = parseNumericIdFromGid(product.id);
    await createMapping(
      this.client,
      this.integrationId,
      IDENTIFIABLE_TYPES.COLORWAY,
      colorwayId,
      EXTERNAL_TYPES.SHOPIFY_PRODUCT,
      product.id,
      {
        admin_url: `https://${this.shopDomain}/admin/products/${productNumericId}`,
        shopify_handle: product.handle ?? undefined,
      }
    );

    const candidateBases = await fetchAllBases(this.client);
    const variants = getVariants(product);
    const bases: { id: number }[] = [];
    const inventoryRecords: { id: number; base_id: number }[] = [];
    const variantMetafieldInputs: { variantGid: string; baseId: number }[] = [];
    let variantFailures = 0;

    const productNumericIdForVariant = parseNumericIdFromGid(product.id);
    for (const variant of variants) {
      try {
        const basePayload = mapVariantToBasePayload(variant, product.title ?? "");
        const retailPrice =
          variant.price?.trim() !== ""
            ? parseFloat(variant.price)
            : undefined;
        let baseId: number;
        const existing = findExistingBase(
          candidateBases,
          basePayload.descriptor,
          retailPrice ?? null
        );
        if (existing) {
          baseId = existing.id;
        } else {
          const base = await this.client.createBase(basePayload);
          baseId = base.id;
          candidateBases.push(base);
        }
        bases.push({ id: baseId });
        variantMetafieldInputs.push({ variantGid: variant.id, baseId });

        const inventory = await this.client.createInventory({
          colorway_id: colorwayId,
          base_id: baseId,
          quantity: 0,
        });
        inventoryRecords.push({
          id: inventory.id,
          base_id: baseId,
        });

        const variantNumericId = parseNumericIdFromGid(variant.id);
        await createMapping(
          this.client,
          this.integrationId,
          IDENTIFIABLE_TYPES.INVENTORY,
          inventory.id,
          EXTERNAL_TYPES.SHOPIFY_VARIANT,
          variant.id,
          {
            admin_url: `https://${this.shopDomain}/admin/products/${productNumericIdForVariant}/variants/${variantNumericId}`,
          }
        );
      } catch (err) {
        variantFailures += 1;
        console.error(
          `ProductSyncService: failed to sync variant ${variant.id}:`,
          err instanceof Error ? err.message : String(err)
        );
      }
    }

    if (this.shopifyGraphql && variantMetafieldInputs.length > 0) {
      await setProductAndVariantMetafields(
        this.shopifyGraphql,
        product.id,
        colorwayId,
        variantMetafieldInputs
      );
    }

    const status =
      variantFailures > 0 && bases.length > 0
        ? "warning"
        : variantFailures > 0
          ? "error"
          : "success";
    const productName = product.title?.trim() || "Untitled";
    const message =
      status === "success"
        ? `Imported Shopify product '${productName}' as Colorway #${colorwayId} with ${variants.length} variant(s)`
        : status === "warning"
          ? `Partial import: ${variantFailures} variant(s) failed for '${productName}' (Colorway #${colorwayId})`
          : `Import failed for '${productName}': ${variantFailures} variant(s) failed`;
    const metadata: Record<string, unknown> = {
      shopify_gid: product.id,
      variant_count: variants.length,
      bases_created: bases.map((b) => b.id),
      inventory_created: inventoryRecords.map((r) => r.id),
    };
    if (primaryImageUrl) {
      metadata.primary_image_url = primaryImageUrl;
    }
    await this.logIntegration(
      product.id,
      status,
      message,
      metadata,
      colorwayId
    );

    return {
      colorwayId,
      bases,
      inventoryRecords,
    };
  }

  private async logIntegration(
    _productGid: string,
    status: "success" | "error" | "warning",
    message: string,
    metadata: Record<string, unknown>,
    loggableId?: number
  ): Promise<void> {
    const colorwayId = loggableId ?? 0;
    await this.client.createIntegrationLog(this.integrationId, {
      loggable_type: IDENTIFIABLE_TYPES.COLORWAY,
      loggable_id: colorwayId,
      status,
      message,
      metadata,
      synced_at: new Date().toISOString(),
    });
  }

  private async buildSkippedResult(productGid: string): Promise<ProductSyncResult> {
    const result = await findFibermadeIdByShopifyGid(
      this.client,
      this.integrationId,
      EXTERNAL_TYPES.SHOPIFY_PRODUCT,
      productGid
    );
    if (!result || result.identifiableType !== IDENTIFIABLE_TYPES.COLORWAY) {
      throw new Error(
        `ProductSyncService: expected Colorway mapping for ${productGid} but got ${result?.identifiableType ?? "null"}`
      );
    }
    const colorway = await this.client.getColorway(result.identifiableId);
    const inventories = colorway.inventories ?? [];
    const bases = inventories.map((inv) => ({ id: inv.base_id }));
    const inventoryRecords = inventories.map((inv) => ({
      id: inv.id,
      base_id: inv.base_id,
    }));
    return {
      colorwayId: colorway.id,
      bases,
      inventoryRecords,
      skipped: true,
    };
  }
}
