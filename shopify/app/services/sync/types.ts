/**
 * Types for product sync: Shopify GraphQL product/variant shapes and sync results.
 */

export type ShopifyProductStatus = "ACTIVE" | "DRAFT" | "ARCHIVED";

export type ShopifyWeightUnit = "GRAMS" | "KILOGRAMS" | "OUNCES" | "POUNDS";

export interface ShopifyVariant {
  id: string;
  title: string;
  sku?: string | null;
  price: string;
  weight?: number | null;
  weightUnit?: ShopifyWeightUnit | null;
}

export interface ShopifyProductVariantEdge {
  node: ShopifyVariant;
}

export interface ShopifyProduct {
  id: string;
  title: string;
  descriptionHtml?: string | null;
  status: ShopifyProductStatus;
  handle?: string | null;
  featuredImage?: { url: string } | null;
  variants?: {
    edges?: ShopifyProductVariantEdge[];
  };
}

export interface ProductSyncResultBase {
  colorwayId: number;
  bases: { id: number }[];
  inventoryRecords: { id: number; base_id: number }[];
}

export interface ProductSyncResultCreated extends ProductSyncResultBase {
  skipped?: false;
}

export interface ProductSyncResultSkipped extends ProductSyncResultBase {
  skipped: true;
}

export type ProductSyncResult = ProductSyncResultCreated | ProductSyncResultSkipped;

/** Result shape for pushing a Fibermade Colorway to Shopify. */
export interface ProductPushResult {
  shopifyProductGid: string;
  colorwayId: number;
  variantMappings: { variantGid: string; inventoryId: number }[];
  skipped?: boolean;
}

/** Progress/result shape for bulk import; stored in DB as JSON string and returned from runImport. */
export interface BulkImportProgress {
  total: number;
  imported: number;
  failed: number;
  errors?: Array<{ productId?: string; message: string }>;
}

export type BulkImportResult = BulkImportProgress;

export function getVariants(product: ShopifyProduct): ShopifyVariant[] {
  return product.variants?.edges?.map((e) => e.node) ?? [];
}
