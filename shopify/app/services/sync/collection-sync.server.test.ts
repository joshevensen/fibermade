import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import type { FibermadeClient } from "../fibermade-client.server";
import type { CollectionData } from "../fibermade-client.types";
import type { ShopifyGraphqlRunner } from "./metafields.server";
import { CollectionSyncService } from "./collection-sync.server";

vi.mock("./mapping.server", () => ({
  createMapping: vi.fn().mockResolvedValue({}),
  findFibermadeIdByShopifyGid: vi.fn(),
  mappingExists: vi.fn(),
}));

const { createMapping, findFibermadeIdByShopifyGid, mappingExists } = await import(
  "./mapping.server"
);

function collectionData(overrides?: Partial<CollectionData>): CollectionData {
  return {
    id: 1,
    name: "Test Collection",
    description: "Test description",
    status: "active",
    created_at: "",
    updated_at: "",
    colorways: [],
    ...overrides,
  };
}

function shopifyCollection(overrides?: Partial<{ id: string; title: string; descriptionHtml: string | null; handle: string | null }>): {
  id: string;
  title: string;
  descriptionHtml: string | null;
  handle: string | null;
} {
  return {
    id: "gid://shopify/Collection/123",
    title: "Test Collection",
    descriptionHtml: "<p>Test description</p>",
    handle: "test-collection",
    ...overrides,
  };
}

describe("CollectionSyncService#importCollection", () => {
  const integrationId = 1;
  const shopDomain = "test.myshopify.com";

  let mockClient: {
    createCollection: ReturnType<typeof vi.fn>;
    getCollection: ReturnType<typeof vi.fn>;
    updateCollectionColorways: ReturnType<typeof vi.fn>;
    createIntegrationLog: ReturnType<typeof vi.fn>;
  };
  let mockGraphql: ReturnType<typeof vi.fn>;
  let graphqlRunner: ShopifyGraphqlRunner;
  let client: FibermadeClient;

  beforeEach(() => {
    vi.mocked(mappingExists).mockResolvedValue(false);
    vi.mocked(findFibermadeIdByShopifyGid).mockResolvedValue(null);
    vi.mocked(createMapping).mockResolvedValue({} as never);

    mockClient = {
      createCollection: vi.fn().mockResolvedValue(collectionData({ id: 1 })),
      getCollection: vi.fn().mockResolvedValue(collectionData({ id: 1 })),
      updateCollectionColorways: vi.fn().mockResolvedValue(undefined),
      createIntegrationLog: vi.fn().mockResolvedValue({}),
    };
    client = mockClient as unknown as FibermadeClient;

    mockGraphql = vi.fn().mockResolvedValue({
      data: {
        collection: {
          products: {
            edges: [
              { node: { id: "gid://shopify/Product/1" } },
              { node: { id: "gid://shopify/Product/2" } },
            ],
            pageInfo: { hasNextPage: false, endCursor: null },
          },
        },
      },
    });
    graphqlRunner = mockGraphql as ShopifyGraphqlRunner;
  });

  afterEach(() => {
    vi.clearAllMocks();
  });

  it("creates Collection, ExternalIdentifier, and associates Colorways", async () => {
    const collection = shopifyCollection();
    vi.mocked(findFibermadeIdByShopifyGid).mockImplementation((_client, _integrationId, externalType, gid) => {
      if (externalType === "shopify_collection" && gid === "gid://shopify/Collection/123") {
        return Promise.resolve(null);
      }
      if (externalType === "shopify_product" && gid === "gid://shopify/Product/1") {
        return Promise.resolve({ identifiableType: "App\\Models\\Colorway", identifiableId: 10 });
      }
      if (externalType === "shopify_product" && gid === "gid://shopify/Product/2") {
        return Promise.resolve({ identifiableType: "App\\Models\\Colorway", identifiableId: 20 });
      }
      return Promise.resolve(null);
    });

    const service = new CollectionSyncService(
      client,
      integrationId,
      shopDomain,
      graphqlRunner
    );
    const result = await service.importCollection(collection);

    expect(mockClient.createCollection).toHaveBeenCalledWith({
      name: "Test Collection",
      description: "<p>Test description</p>",
      status: "active",
    });
    expect(createMapping).toHaveBeenCalledWith(
      client,
      integrationId,
      "App\\Models\\Collection",
      1,
      "shopify_collection",
      "gid://shopify/Collection/123",
      expect.objectContaining({
        admin_url: "https://test.myshopify.com/admin/collections/123",
        shopify_handle: "test-collection",
      })
    );
    expect(mockClient.updateCollectionColorways).toHaveBeenCalledWith(1, [10, 20]);
    expect(result.collectionId).toBe(1);
    expect(result.colorwayCount).toBe(2);
    expect(result.skipped).toBeUndefined();
  });

  it("maps fields: title → name, descriptionHtml → description, status = active", async () => {
    const collection = shopifyCollection({
      title: "My Collection",
      descriptionHtml: "<p>My description</p>",
    });
    mockGraphql.mockResolvedValue({
      data: {
        collection: {
          products: {
            edges: [],
            pageInfo: { hasNextPage: false, endCursor: null },
          },
        },
      },
    });

    const service = new CollectionSyncService(
      client,
      integrationId,
      shopDomain,
      graphqlRunner
    );
    await service.importCollection(collection);

    expect(mockClient.createCollection).toHaveBeenCalledWith({
      name: "My Collection",
      description: "<p>My description</p>",
      status: "active",
    });
  });

  it("stores collection handle in ExternalIdentifier metadata", async () => {
    const collection = shopifyCollection({ handle: "my-handle" });
    mockGraphql.mockResolvedValue({
      data: {
        collection: {
          products: {
            edges: [],
            pageInfo: { hasNextPage: false, endCursor: null },
          },
        },
      },
    });

    const service = new CollectionSyncService(
      client,
      integrationId,
      shopDomain,
      graphqlRunner
    );
    await service.importCollection(collection);

    expect(createMapping).toHaveBeenCalledWith(
      expect.anything(),
      expect.anything(),
      expect.anything(),
      expect.anything(),
      expect.anything(),
      expect.anything(),
      expect.objectContaining({
        shopify_handle: "my-handle",
      })
    );
  });

  it("skips collection already mapped and returns skipped result", async () => {
    const collection = shopifyCollection();
    vi.mocked(mappingExists).mockResolvedValue(true);
    vi.mocked(findFibermadeIdByShopifyGid).mockImplementation((_client, _integrationId, externalType, gid) => {
      if (externalType === "shopify_collection" && gid === "gid://shopify/Collection/123") {
        return Promise.resolve({
          identifiableType: "App\\Models\\Collection",
          identifiableId: 5,
        });
      }
      return Promise.resolve(null);
    });
    mockClient.getCollection.mockResolvedValue(
      collectionData({ id: 5, colorways: [{ id: 10 } as never, { id: 20 } as never] })
    );

    const service = new CollectionSyncService(
      client,
      integrationId,
      shopDomain,
      graphqlRunner
    );
    const result = await service.importCollection(collection);

    expect(mockClient.createCollection).not.toHaveBeenCalled();
    expect(result.collectionId).toBe(5);
    expect(result.colorwayCount).toBe(2);
    expect(result.skipped).toBe(true);
  });

  it("looks up products via ExternalIdentifier and associates Colorways", async () => {
    const collection = shopifyCollection();
    vi.mocked(findFibermadeIdByShopifyGid).mockImplementation((_client, _integrationId, externalType, gid) => {
      if (externalType === "shopify_collection" && gid === "gid://shopify/Collection/123") {
        return Promise.resolve(null);
      }
      if (externalType === "shopify_product" && gid === "gid://shopify/Product/1") {
        return Promise.resolve({ identifiableType: "App\\Models\\Colorway", identifiableId: 10 });
      }
      if (externalType === "shopify_product" && gid === "gid://shopify/Product/2") {
        return Promise.resolve({ identifiableType: "App\\Models\\Colorway", identifiableId: 20 });
      }
      if (externalType === "shopify_product" && gid === "gid://shopify/Product/3") {
        return Promise.resolve({ identifiableType: "App\\Models\\Colorway", identifiableId: 30 });
      }
      return Promise.resolve(null);
    });

    mockGraphql.mockResolvedValue({
      data: {
        collection: {
          products: {
            edges: [
              { node: { id: "gid://shopify/Product/1" } },
              { node: { id: "gid://shopify/Product/2" } },
              { node: { id: "gid://shopify/Product/3" } },
            ],
            pageInfo: { hasNextPage: false, endCursor: null },
          },
        },
      },
    });

    const service = new CollectionSyncService(
      client,
      integrationId,
      shopDomain,
      graphqlRunner
    );
    await service.importCollection(collection);

    expect(findFibermadeIdByShopifyGid).toHaveBeenCalledWith(
      client,
      integrationId,
      "shopify_product",
      "gid://shopify/Product/1"
    );
    expect(findFibermadeIdByShopifyGid).toHaveBeenCalledWith(
      client,
      integrationId,
      "shopify_product",
      "gid://shopify/Product/2"
    );
    expect(findFibermadeIdByShopifyGid).toHaveBeenCalledWith(
      client,
      integrationId,
      "shopify_product",
      "gid://shopify/Product/3"
    );
    expect(mockClient.updateCollectionColorways).toHaveBeenCalledWith(1, [10, 20, 30]);
  });

  it("creates Collection with no mapped products and logs warning", async () => {
    const collection = shopifyCollection();
    vi.mocked(findFibermadeIdByShopifyGid).mockImplementation((_client, _integrationId, externalType, gid) => {
      if (externalType === "shopify_collection" && gid === "gid://shopify/Collection/123") {
        return Promise.resolve(null);
      }
      return Promise.resolve(null);
    });
    mockGraphql.mockResolvedValue({
      data: {
        collection: {
          products: {
            edges: [
              { node: { id: "gid://shopify/Product/1" } },
              { node: { id: "gid://shopify/Product/2" } },
            ],
            pageInfo: { hasNextPage: false, endCursor: null },
          },
        },
      },
    });

    const service = new CollectionSyncService(
      client,
      integrationId,
      shopDomain,
      graphqlRunner
    );
    const result = await service.importCollection(collection);

    expect(mockClient.createCollection).toHaveBeenCalled();
    expect(mockClient.updateCollectionColorways).not.toHaveBeenCalled();
    expect(mockClient.createIntegrationLog).toHaveBeenCalledWith(
      integrationId,
      expect.objectContaining({
        status: "warning",
        message: expect.stringContaining("no products mapped to Colorways"),
      })
    );
    expect(result.collectionId).toBe(1);
    expect(result.colorwayCount).toBe(0);
  });

  it("handles product pagination for collections with many products", async () => {
    const collection = shopifyCollection();
    let callCount = 0;
    mockGraphql.mockImplementation(() => {
      callCount++;
      if (callCount === 1) {
        return Promise.resolve({
          data: {
            collection: {
              products: {
                edges: Array.from({ length: 100 }, (_, i) => ({
                  node: { id: `gid://shopify/Product/${i + 1}` },
                })),
                pageInfo: { hasNextPage: true, endCursor: "cursor1" },
              },
            },
          },
        });
      }
      return Promise.resolve({
        data: {
          collection: {
            products: {
              edges: Array.from({ length: 50 }, (_, i) => ({
                node: { id: `gid://shopify/Product/${i + 101}` },
              })),
              pageInfo: { hasNextPage: false, endCursor: null },
            },
          },
        },
      });
    });

    vi.mocked(findFibermadeIdByShopifyGid).mockImplementation((_client, _integrationId, _type, gid) => {
      if (gid === "gid://shopify/Collection/123") {
        return Promise.resolve(null);
      }
      const productNum = parseInt(gid.split("/").pop() ?? "0");
      return Promise.resolve({
        identifiableType: "App\\Models\\Colorway",
        identifiableId: productNum,
      });
    });

    const service = new CollectionSyncService(
      client,
      integrationId,
      shopDomain,
      graphqlRunner
    );
    await service.importCollection(collection);

    expect(mockGraphql).toHaveBeenCalledTimes(2);
    expect(mockClient.updateCollectionColorways).toHaveBeenCalledWith(
      1,
      expect.arrayContaining(Array.from({ length: 150 }, (_, i) => i + 1))
    );
  });

  it("logs integration success with metadata", async () => {
    const collection = shopifyCollection();
    vi.mocked(findFibermadeIdByShopifyGid)
      .mockResolvedValueOnce(null)
      .mockResolvedValueOnce({ identifiableType: "App\\Models\\Colorway", identifiableId: 10 });

    const service = new CollectionSyncService(
      client,
      integrationId,
      shopDomain,
      graphqlRunner
    );
    await service.importCollection(collection);

    expect(mockClient.createIntegrationLog).toHaveBeenCalledWith(
      integrationId,
      expect.objectContaining({
        loggable_type: "App\\Models\\Collection",
        loggable_id: 1,
        status: "success",
        message: expect.stringContaining("Imported Shopify collection"),
        metadata: expect.objectContaining({
          shopify_gid: "gid://shopify/Collection/123",
          collection_id: 1,
          colorway_count: 1,
          shopify_handle: "test-collection",
        }),
      })
    );
  });

  it("handles GraphQL errors and logs them", async () => {
    const collection = shopifyCollection();
    mockGraphql.mockRejectedValue(new Error("GraphQL error"));

    const service = new CollectionSyncService(
      client,
      integrationId,
      shopDomain,
      graphqlRunner
    );

    await expect(service.importCollection(collection)).rejects.toThrow("GraphQL error");
    expect(mockClient.createIntegrationLog).toHaveBeenCalledWith(
      integrationId,
      expect.objectContaining({
        status: "error",
        message: "GraphQL error",
      })
    );
  });

  it("retries on rate limit errors with exponential backoff", async () => {
    const collection = shopifyCollection();
    let attempt = 0;
    mockGraphql.mockImplementation(() => {
      attempt++;
      if (attempt === 1) {
        const error = new Error("Rate limited") as Error & { status?: number };
        error.status = 429;
        return Promise.reject(error);
      }
      return Promise.resolve({
        data: {
          collection: {
            products: {
              edges: [],
              pageInfo: { hasNextPage: false, endCursor: null },
            },
          },
        },
      });
    });

    const service = new CollectionSyncService(
      client,
      integrationId,
      shopDomain,
      graphqlRunner
    );
    const startTime = Date.now();
    await service.importCollection(collection);
    const elapsed = Date.now() - startTime;

    expect(mockGraphql).toHaveBeenCalledTimes(2);
    expect(elapsed).toBeGreaterThanOrEqual(1000);
  });
});

describe("CollectionSyncService#importAllCollections", () => {
  const integrationId = 1;
  const shopDomain = "test.myshopify.com";

  let mockClient: {
    createCollection: ReturnType<typeof vi.fn>;
    getCollection: ReturnType<typeof vi.fn>;
    updateCollectionColorways: ReturnType<typeof vi.fn>;
    createIntegrationLog: ReturnType<typeof vi.fn>;
  };
  let mockGraphql: ReturnType<typeof vi.fn>;
  let graphqlRunner: ShopifyGraphqlRunner;
  let client: FibermadeClient;

  beforeEach(() => {
    vi.mocked(mappingExists).mockResolvedValue(false);
    vi.mocked(findFibermadeIdByShopifyGid).mockResolvedValue(null);
    vi.mocked(createMapping).mockResolvedValue({} as never);

    mockClient = {
      createCollection: vi.fn().mockImplementation((data) =>
        Promise.resolve(collectionData({ id: Math.floor(Math.random() * 1000), ...data }))
      ),
      getCollection: vi.fn().mockResolvedValue(collectionData({ id: 1 })),
      updateCollectionColorways: vi.fn().mockResolvedValue(undefined),
      createIntegrationLog: vi.fn().mockResolvedValue({}),
    };
    client = mockClient as unknown as FibermadeClient;

    mockGraphql = vi.fn().mockResolvedValue({
      data: {
        collections: {
          edges: [
            {
              node: {
                id: "gid://shopify/Collection/1",
                title: "Collection 1",
                descriptionHtml: "<p>Desc 1</p>",
                handle: "collection-1",
              },
              cursor: "cursor1",
            },
            {
              node: {
                id: "gid://shopify/Collection/2",
                title: "Collection 2",
                descriptionHtml: "<p>Desc 2</p>",
                handle: "collection-2",
              },
              cursor: "cursor2",
            },
          ],
          pageInfo: { hasNextPage: false, endCursor: null },
        },
        collection: {
          products: {
            edges: [],
            pageInfo: { hasNextPage: false, endCursor: null },
          },
        },
      },
    });
    graphqlRunner = mockGraphql as ShopifyGraphqlRunner;
  });

  afterEach(() => {
    vi.clearAllMocks();
  });

  it("paginates through all collections and imports each one", async () => {
    let collectionsCallCount = 0;
    mockGraphql.mockImplementation((query) => {
      if (query.includes("collections(first:")) {
        collectionsCallCount++;
        if (collectionsCallCount === 1) {
          return Promise.resolve({
            data: {
              collections: {
                edges: [
                  {
                    node: {
                      id: "gid://shopify/Collection/1",
                      title: "Collection 1",
                      descriptionHtml: null,
                      handle: "collection-1",
                    },
                    cursor: "cursor1",
                  },
                ],
                pageInfo: { hasNextPage: true, endCursor: "cursor1" },
              },
            },
          });
        }
        return Promise.resolve({
          data: {
            collections: {
              edges: [
                {
                  node: {
                    id: "gid://shopify/Collection/2",
                    title: "Collection 2",
                    descriptionHtml: null,
                    handle: "collection-2",
                  },
                  cursor: "cursor2",
                },
              ],
              pageInfo: { hasNextPage: false, endCursor: null },
            },
          },
        });
      }
      return Promise.resolve({
        data: {
          collection: {
            products: {
              edges: [],
              pageInfo: { hasNextPage: false, endCursor: null },
            },
          },
        },
      });
    });

    const service = new CollectionSyncService(
      client,
      integrationId,
      shopDomain,
      graphqlRunner
    );
    const results = await service.importAllCollections();

    expect(results).toHaveLength(2);
    expect(mockClient.createCollection).toHaveBeenCalledTimes(2);
  });

  it("handles errors gracefully and continues importing", async () => {
    mockClient.createCollection.mockImplementationOnce(() => {
      throw new Error("Create failed");
    });

    const service = new CollectionSyncService(
      client,
      integrationId,
      shopDomain,
      graphqlRunner
    );
    const results = await service.importAllCollections();

    expect(results).toHaveLength(1);
    expect(mockClient.createIntegrationLog).toHaveBeenCalledWith(
      integrationId,
      expect.objectContaining({
        status: "error",
        message: expect.stringContaining("Failed to import collection"),
      })
    );
  });
});
