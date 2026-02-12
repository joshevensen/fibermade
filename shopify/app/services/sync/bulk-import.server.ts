import type { FibermadeClient } from "../fibermade-client.server";
import type { ShopifyGraphqlRunner } from "./metafields.server";
import { CollectionSyncService } from "./collection-sync.server";
import { ProductSyncService } from "./product-sync.server";
import type {
  BulkImportProgress,
  BulkImportResult,
  ShopifyProduct,
  ShopifyVariant,
  ShopifyWeightUnit,
} from "./types";

const PRODUCTS_PAGE_SIZE = 50;
const MAX_ERRORS_STORED = 50;
const RATE_LIMIT_RETRIES = 3;
const RATE_LIMIT_BACKOFF_MS = [1000, 2000, 3000];

const PRODUCTS_QUERY = `#graphql
  query BulkImportProducts($cursor: String) {
    products(first: ${PRODUCTS_PAGE_SIZE}, after: $cursor) {
      edges {
        node {
          id
          title
          descriptionHtml
          status
          handle
          featuredImage { url }
          productType
          vendor
          tags
          variants(first: 100) {
            edges {
              node {
                id
                title
                sku
                price
                weight
                weightUnit
              }
            }
          }
          images(first: 10) {
            edges {
              node {
                id
                url
                altText
              }
            }
          }
        }
        cursor
      }
      pageInfo {
        hasNextPage
        endCursor
      }
    }
  }
`;

export type UpdateConnectionFn = (data: {
  initialImportStatus: string;
  initialImportProgress?: string | null;
}) => Promise<void>;

export class BulkImportService {
  constructor(
    private readonly client: FibermadeClient,
    private readonly integrationId: number,
    private readonly shopDomain: string,
    private readonly graphql: ShopifyGraphqlRunner,
    private readonly updateConnection: UpdateConnectionFn
  ) {}

  async runImport(): Promise<BulkImportResult> {
    const progress: BulkImportProgress = {
      total: 0,
      imported: 0,
      failed: 0,
      errors: [],
    };

    await this.updateConnection({
      initialImportStatus: "in_progress",
      initialImportProgress: JSON.stringify(progress),
    });

    const productSync = new ProductSyncService(
      this.client,
      this.integrationId,
      this.shopDomain,
      this.graphql
    );

    let cursor: string | null = null;
    let hasNextPage = true;

    try {
      while (hasNextPage) {
        const result = await this.fetchProductsPage(cursor);
        if (!result) {
          await this.updateConnection({
            initialImportStatus: "failed",
            initialImportProgress: JSON.stringify({
              ...progress,
              lastCursor: cursor,
            }),
          });
          return progress;
        }

        const { products, nextCursor, hasNext } = result;
        const nodes = products?.edges?.map((e) => e.node) ?? [];

        progress.total += nodes.length;

        for (const node of nodes) {
          const product = this.normalizeProduct(node);
          try {
            const syncResult = await productSync.importProduct(product);
            if (syncResult.skipped) {
              progress.imported += 1;
            } else {
              progress.imported += 1;
            }
          } catch (err) {
            progress.failed += 1;
            const message = err instanceof Error ? err.message : String(err);
            const list = progress.errors ?? [];
            list.push({ productId: product.id, message });
            if (list.length > MAX_ERRORS_STORED) {
              list.shift();
            }
            progress.errors = list;
          }
        }

        await this.updateConnection({
          initialImportStatus: "in_progress",
          initialImportProgress: JSON.stringify(progress),
        });

        cursor = nextCursor;
        hasNextPage = hasNext;

        if (nodes.length === 0 && !hasNextPage && progress.total === 0) {
          progress.total = 0;
          break;
        }
      }

      if (hasNextPage === false) {
        await this.updateConnection({
          initialImportStatus: "in_progress",
          initialImportProgress: JSON.stringify({
            ...progress,
            importingCollections: true,
          }),
        });

        try {
          const collectionSync = new CollectionSyncService(
            this.client,
            this.integrationId,
            this.shopDomain,
            this.graphql
          );
          await collectionSync.importAllCollections();
        } catch (err) {
          const message = err instanceof Error ? err.message : String(err);
          const list = progress.errors ?? [];
          list.push({ message: `Collection import failed: ${message}` });
          if (list.length > MAX_ERRORS_STORED) {
            list.shift();
          }
          progress.errors = list;
        }

        await this.updateConnection({
          initialImportStatus: "complete",
          initialImportProgress: JSON.stringify(progress),
        });
      }
    } catch (err) {
      await this.updateConnection({
        initialImportStatus: "failed",
        initialImportProgress: JSON.stringify({
          ...progress,
          lastCursor: cursor,
          criticalError: err instanceof Error ? err.message : String(err),
        }),
      });
    }

    return progress;
  }

  private async fetchProductsPage(
    cursor: string | null
  ): Promise<{
    products: { edges: Array<{ node: unknown; cursor: string }>; pageInfo: { hasNextPage: boolean; endCursor: string | null } };
    nextCursor: string | null;
    hasNext: boolean;
  } | null> {
    const variables = cursor ? { cursor } : {};
    let lastErr: unknown;

    for (let attempt = 0; attempt <= RATE_LIMIT_RETRIES; attempt++) {
      if (attempt > 0) {
        await this.sleep(RATE_LIMIT_BACKOFF_MS[attempt - 1]);
      }
      try {
        const result = await this.graphql(PRODUCTS_QUERY, variables);
        const data = result.data as {
          products?: {
            edges: Array<{ node: unknown; cursor: string }>;
            pageInfo: { hasNextPage: boolean; endCursor: string | null };
          };
        } | null;
        const products = data?.products;
        if (!products) {
          return null;
        }
        const pageInfo = products.pageInfo ?? { hasNextPage: false, endCursor: null };
        return {
          products,
          nextCursor: pageInfo.endCursor ?? null,
          hasNext: pageInfo.hasNextPage ?? false,
        };
      } catch (e) {
        lastErr = e;
        const status = (e as { status?: number }).status;
        if (status === 429 && attempt < RATE_LIMIT_RETRIES) {
          continue;
        }
        throw e;
      }
    }
    throw lastErr;
  }

  private normalizeProduct(node: unknown): ShopifyProduct {
    const n = node as Record<string, unknown>;
    const variants = n.variants as { edges?: Array<{ node: Record<string, unknown> }> } | undefined;
    const variantEdges = variants?.edges ?? [];
    return {
      id: String(n.id ?? ""),
      title: String(n.title ?? ""),
      descriptionHtml: n.descriptionHtml != null ? String(n.descriptionHtml) : null,
      status: (n.status as ShopifyProduct["status"]) ?? "ACTIVE",
      handle: n.handle != null ? String(n.handle) : null,
      featuredImage:
        n.featuredImage != null && typeof n.featuredImage === "object" && "url" in n.featuredImage
          ? { url: String((n.featuredImage as { url: string }).url) }
          : null,
      variants: {
        edges: variantEdges.map((e) => ({
          node: this.normalizeVariant(e.node),
        })),
      },
    };
  }

  private normalizeVariant(node: Record<string, unknown>): ShopifyVariant {
    return {
      id: String(node.id ?? ""),
      title: String(node.title ?? ""),
      sku: node.sku != null ? String(node.sku) : null,
      price: String(node.price ?? ""),
      weight: typeof node.weight === "number" ? node.weight : null,
      weightUnit: (node.weightUnit as ShopifyWeightUnit | null) ?? null,
    };
  }

  private sleep(ms: number): Promise<void> {
    return new Promise((resolve) => setTimeout(resolve, ms));
  }
}
