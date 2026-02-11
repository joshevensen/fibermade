import type { FibermadeClient } from "../fibermade-client.server";
import type {
  BaseData,
  CreateColorwayPayload,
  CreateBasePayload,
} from "../fibermade-client.types";
import { EXTERNAL_TYPES, IDENTIFIABLE_TYPES } from "./constants";
import { createMapping, findFibermadeIdByShopifyGid, mappingExists } from "./mapping.server";
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
    private readonly shopDomain: string
  ) {}

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

    const colorwayPayload = mapProductToColorwayPayload(product);
    const colorway = await this.client.createColorway(colorwayPayload);
    const colorwayId = colorway.id;

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
        console.error(
          `ProductSyncService: failed to sync variant ${variant.id}:`,
          err instanceof Error ? err.message : String(err)
        );
      }
    }

    return {
      colorwayId,
      bases,
      inventoryRecords,
    };
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
