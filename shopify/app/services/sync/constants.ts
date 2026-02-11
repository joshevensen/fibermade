/**
 * Constants for sync: external identifier types and Shopify metafield keys.
 * Tree-shakeable; no server-only dependencies.
 */

export const EXTERNAL_TYPES = {
  SHOPIFY_PRODUCT: "shopify_product",
  SHOPIFY_VARIANT: "shopify_variant",
  SHOPIFY_COLLECTION: "shopify_collection",
} as const;

export const IDENTIFIABLE_TYPES = {
  COLORWAY: "App\\Models\\Colorway",
  BASE: "App\\Models\\Base",
  INVENTORY: "App\\Models\\Inventory",
  COLLECTION: "App\\Models\\Collection",
} as const;

export const METAFIELD_NAMESPACE = "fibermade";

export const METAFIELD_KEYS = {
  COLORWAY_ID: "colorway_id",
  BASE_ID: "base_id",
} as const;
