import type { FibermadeClient } from "../fibermade-client.server";
import { EXTERNAL_TYPES, IDENTIFIABLE_TYPES } from "./constants";
import type { ShopifyGraphqlRunner } from "./metafields.server";
import {
  createMapping,
  findFibermadeIdByShopifyGid,
  mappingExists,
} from "./mapping.server";
import type { CollectionSyncResult, ShopifyCollection } from "./types";

const COLLECTIONS_PAGE_SIZE = 50;
const PRODUCTS_PAGE_SIZE = 100;
const RATE_LIMIT_RETRIES = 3;
const RATE_LIMIT_BACKOFF_MS = [1000, 2000, 3000];

const COLLECTIONS_QUERY = `#graphql
  query Collections($first: Int!, $after: String) {
    collections(first: $first, after: $after) {
      edges {
        node {
          id
          title
          descriptionHtml
          handle
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

const COLLECTION_PRODUCTS_QUERY = `#graphql
  query CollectionProducts($id: ID!, $first: Int!, $after: String) {
    collection(id: $id) {
      products(first: $first, after: $after) {
        edges {
          node {
            id
          }
        }
        pageInfo {
          hasNextPage
          endCursor
        }
      }
    }
  }
`;

/**
 * Extracts the numeric ID from a Shopify GID (e.g. gid://shopify/Collection/123 -> "123").
 */
function parseNumericIdFromGid(gid: string): string {
  return gid.split("/").pop() ?? "";
}

export class CollectionSyncService {
  constructor(
    private readonly client: FibermadeClient,
    private readonly integrationId: number,
    private readonly shopDomain: string,
    private readonly graphql: ShopifyGraphqlRunner
  ) {}

  async importCollection(shopifyCollection: ShopifyCollection): Promise<CollectionSyncResult> {
    const exists = await mappingExists(
      this.client,
      this.integrationId,
      EXTERNAL_TYPES.SHOPIFY_COLLECTION,
      shopifyCollection.id
    );
    if (exists) {
      const result = await findFibermadeIdByShopifyGid(
        this.client,
        this.integrationId,
        EXTERNAL_TYPES.SHOPIFY_COLLECTION,
        shopifyCollection.id
      );
      if (!result || result.identifiableType !== IDENTIFIABLE_TYPES.COLLECTION) {
        throw new Error(
          `CollectionSyncService: expected Collection mapping for ${shopifyCollection.id} but got ${result?.identifiableType ?? "null"}`
        );
      }
      const collection = await this.client.getCollection(result.identifiableId);
      return {
        collectionId: collection.id,
        colorwayCount: collection.colorways?.length ?? 0,
        skipped: true,
      };
    }

    try {
      return await this.runImport(shopifyCollection);
    } catch (err) {
      await this.logIntegration(
        shopifyCollection.id,
        "error",
        err instanceof Error ? err.message : String(err),
        { shopify_gid: shopifyCollection.id }
      );
      throw err;
    }
  }

  async importAllCollections(): Promise<CollectionSyncResult[]> {
    const results: CollectionSyncResult[] = [];
    let cursor: string | null = null;
    let hasNextPage = true;

    while (hasNextPage) {
      const pageResult = await this.fetchCollectionsPage(cursor);
      if (!pageResult) {
        break;
      }

      const { collections, nextCursor, hasNext } = pageResult;
      const nodes = collections?.edges?.map((e) => e.node) ?? [];

      for (const node of nodes) {
        const collection = this.normalizeCollection(node);
        try {
          const result = await this.importCollection(collection);
          results.push(result);
        } catch (err) {
          const message = err instanceof Error ? err.message : String(err);
          await this.logIntegration(
            collection.id,
            "error",
            `Failed to import collection: ${message}`,
            { shopify_gid: collection.id }
          );
        }
      }

      cursor = nextCursor;
      hasNextPage = hasNext;
    }

    return results;
  }

  private async runImport(shopifyCollection: ShopifyCollection): Promise<CollectionSyncResult> {
    const name = shopifyCollection.title?.trim() || "Untitled";
    const description = shopifyCollection.descriptionHtml?.trim() || null;

    const collection = await this.client.createCollection({
      name,
      description,
      status: "active",
    });
    const collectionId = collection.id;

    const collectionNumericId = parseNumericIdFromGid(shopifyCollection.id);
    await createMapping(
      this.client,
      this.integrationId,
      IDENTIFIABLE_TYPES.COLLECTION,
      collectionId,
      EXTERNAL_TYPES.SHOPIFY_COLLECTION,
      shopifyCollection.id,
      {
        admin_url: `https://${this.shopDomain}/admin/collections/${collectionNumericId}`,
        shopify_handle: shopifyCollection.handle ?? undefined,
      }
    );

    const colorwayIds: number[] = [];
    let productCursor: string | null = null;
    let hasMoreProducts = true;

    while (hasMoreProducts) {
      const productsResult = await this.fetchCollectionProducts(shopifyCollection.id, productCursor);
      if (!productsResult) {
        break;
      }

      const { products, nextCursor, hasNext } = productsResult;
      const productNodes = products?.edges?.map((e) => e.node) ?? [];

      for (const productNode of productNodes) {
        const productGid = String(productNode.id ?? "");
        if (!productGid) continue;

        const colorwayResult = await findFibermadeIdByShopifyGid(
          this.client,
          this.integrationId,
          EXTERNAL_TYPES.SHOPIFY_PRODUCT,
          productGid
        );
        if (colorwayResult && colorwayResult.identifiableType === IDENTIFIABLE_TYPES.COLORWAY) {
          colorwayIds.push(colorwayResult.identifiableId);
        }
      }

      productCursor = nextCursor;
      hasMoreProducts = hasNext;
    }

    if (colorwayIds.length > 0) {
      await this.client.updateCollectionColorways(collectionId, colorwayIds);
    } else {
      await this.logIntegration(
        shopifyCollection.id,
        "warning",
        `Collection '${name}' created but no products mapped to Colorways`,
        {
          shopify_gid: shopifyCollection.id,
          collection_id: collectionId,
        }
      );
    }

    const logStatus = colorwayIds.length > 0 ? "success" : "warning";
    const message =
      colorwayIds.length > 0
        ? `Imported Shopify collection '${name}' as Collection #${collectionId} with ${colorwayIds.length} colorway(s)`
        : `Imported Shopify collection '${name}' as Collection #${collectionId} (no colorways mapped)`;
    const metadata: Record<string, unknown> = {
      shopify_gid: shopifyCollection.id,
      collection_id: collectionId,
      colorway_count: colorwayIds.length,
      shopify_handle: shopifyCollection.handle ?? undefined,
    };
    await this.logIntegration(shopifyCollection.id, logStatus, message, metadata, collectionId);

    return {
      collectionId,
      colorwayCount: colorwayIds.length,
    };
  }

  private async fetchCollectionsPage(
    cursor: string | null
  ): Promise<{
    collections: { edges: Array<{ node: unknown; cursor: string }>; pageInfo: { hasNextPage: boolean; endCursor: string | null } };
    nextCursor: string | null;
    hasNext: boolean;
  } | null> {
    const variables = cursor ? { first: COLLECTIONS_PAGE_SIZE, after: cursor } : { first: COLLECTIONS_PAGE_SIZE };
    let lastErr: unknown;

    for (let attempt = 0; attempt <= RATE_LIMIT_RETRIES; attempt++) {
      if (attempt > 0) {
        await this.sleep(RATE_LIMIT_BACKOFF_MS[attempt - 1]);
      }
      try {
        const result = await this.graphql(COLLECTIONS_QUERY, variables);
        const data = result.data as {
          collections?: {
            edges: Array<{ node: unknown; cursor: string }>;
            pageInfo: { hasNextPage: boolean; endCursor: string | null };
          };
        } | null;
        const collections = data?.collections;
        if (!collections) {
          return null;
        }
        const pageInfo = collections.pageInfo ?? { hasNextPage: false, endCursor: null };
        return {
          collections,
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

  private async fetchCollectionProducts(
    collectionId: string,
    cursor: string | null
  ): Promise<{
    products: { edges: Array<{ node: { id: string } }>; pageInfo: { hasNextPage: boolean; endCursor: string | null } };
    nextCursor: string | null;
    hasNext: boolean;
  } | null> {
    const variables = cursor
      ? { id: collectionId, first: PRODUCTS_PAGE_SIZE, after: cursor }
      : { id: collectionId, first: PRODUCTS_PAGE_SIZE };
    let lastErr: unknown;

    for (let attempt = 0; attempt <= RATE_LIMIT_RETRIES; attempt++) {
      if (attempt > 0) {
        await this.sleep(RATE_LIMIT_BACKOFF_MS[attempt - 1]);
      }
      try {
        const result = await this.graphql(COLLECTION_PRODUCTS_QUERY, variables);
        const data = result.data as {
          collection?: {
            products?: {
              edges: Array<{ node: { id: string } }>;
              pageInfo: { hasNextPage: boolean; endCursor: string | null };
            };
          };
        } | null;
        const products = data?.collection?.products;
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

  private normalizeCollection(node: unknown): ShopifyCollection {
    const n = node as Record<string, unknown>;
    return {
      id: String(n.id ?? ""),
      title: String(n.title ?? ""),
      descriptionHtml: n.descriptionHtml != null ? String(n.descriptionHtml) : null,
      handle: n.handle != null ? String(n.handle) : null,
    };
  }

  private async logIntegration(
    _collectionGid: string,
    status: "success" | "error" | "warning",
    message: string,
    metadata: Record<string, unknown>,
    loggableId?: number
  ): Promise<void> {
    const collectionId = loggableId ?? 0;
    await this.client.createIntegrationLog(this.integrationId, {
      loggable_type: IDENTIFIABLE_TYPES.COLLECTION,
      loggable_id: collectionId,
      status,
      message,
      metadata,
      synced_at: new Date().toISOString(),
    });
  }

  private sleep(ms: number): Promise<void> {
    return new Promise((resolve) => setTimeout(resolve, ms));
  }
}
