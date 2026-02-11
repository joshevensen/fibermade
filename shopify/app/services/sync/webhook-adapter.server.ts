import type { ShopifyProduct, ShopifyProductStatus, ShopifyVariant, ShopifyWeightUnit } from "./types";

const REST_STATUS_MAP: Record<string, ShopifyProductStatus> = {
  active: "ACTIVE",
  draft: "DRAFT",
  archived: "ARCHIVED",
};

const REST_WEIGHT_UNIT_MAP: Record<string, ShopifyWeightUnit> = {
  g: "GRAMS",
  kg: "KILOGRAMS",
  oz: "OUNCES",
  lb: "POUNDS",
};

function toProductGid(id: unknown): string {
  if (id == null || id === "") return "";
  return `gid://shopify/Product/${id}`;
}

function toVariantGid(id: unknown): string {
  if (id == null || id === "") return "";
  return `gid://shopify/ProductVariant/${id}`;
}

function mapRestStatus(status: unknown): ShopifyProductStatus {
  if (typeof status !== "string") return "ACTIVE";
  const normalized = status.toLowerCase();
  return REST_STATUS_MAP[normalized] ?? "ACTIVE";
}

function mapRestWeightUnit(unit: unknown): ShopifyWeightUnit | null {
  if (unit == null || typeof unit !== "string") return null;
  const normalized = unit.toLowerCase();
  return REST_WEIGHT_UNIT_MAP[normalized] ?? null;
}

function convertRestVariant(raw: Record<string, unknown>): ShopifyVariant {
  const id = toVariantGid(raw.id);
  const weightUnit = mapRestWeightUnit(raw.weight_unit);
  return {
    id,
    title: typeof raw.title === "string" ? raw.title : "Default Title",
    sku: raw.sku != null && raw.sku !== "" ? String(raw.sku) : null,
    price: typeof raw.price === "string" ? raw.price : String(raw.price ?? ""),
    weight: typeof raw.weight === "number" ? raw.weight : null,
    weightUnit: weightUnit ?? undefined,
  };
}

function convertRestVariants(variants: unknown): ShopifyVariant[] {
  if (!Array.isArray(variants)) return [];
  return variants.map((v) =>
    convertRestVariant(typeof v === "object" && v != null ? (v as Record<string, unknown>) : {})
  );
}

function getFeaturedImage(images: unknown): { url: string } | null {
  if (!Array.isArray(images) || images.length === 0) return null;
  const first = images[0];
  if (first == null || typeof first !== "object" || !("src" in first)) return null;
  const src = (first as { src?: unknown }).src;
  return typeof src === "string" && src !== "" ? { url: src } : null;
}

/**
 * Converts a Shopify REST webhook payload to the ShopifyProduct type used by ProductSyncService.
 */
export function restProductToShopifyProduct(payload: Record<string, unknown>): ShopifyProduct {
  const id = toProductGid(payload.id);
  const variants = convertRestVariants(payload.variants);
  return {
    id,
    title: typeof payload.title === "string" ? payload.title : "",
    descriptionHtml: payload.body_html != null ? String(payload.body_html) : null,
    status: mapRestStatus(payload.status),
    handle: payload.handle != null && payload.handle !== "" ? String(payload.handle) : null,
    featuredImage: getFeaturedImage(payload.images),
    variants: {
      edges: variants.map((node) => ({ node })),
    },
  };
}
