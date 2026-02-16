import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import type { FibermadeClient } from "../fibermade-client.server";
import { BulkImportService } from "./bulk-import.server";
import type { UpdateConnectionFn } from "./bulk-import.server";
import type { ShopifyGraphqlRunner } from "./metafields.server";

const mockImportProduct = vi.fn();

vi.mock("./product-sync.server", () => ({
  ProductSyncService: class MockProductSyncService {
    importProduct = mockImportProduct;
  },
}));

function makeProductNode(id: string, title: string) {
  return {
    id: `gid://shopify/Product/${id}`,
    title,
    descriptionHtml: null,
    status: "ACTIVE",
    handle: `product-${id}`,
    featuredImage: null,
    variants: {
      edges: [
        {
          node: {
            id: `gid://shopify/ProductVariant/${id}-1`,
            title: "Default",
            sku: null,
            price: "10.00",
            weight: null,
            weightUnit: null,
          },
        },
      ],
    },
    images: { edges: [] },
  };
}

function makeProductsResponse(
  nodes: ReturnType<typeof makeProductNode>[],
  hasNextPage: boolean,
  endCursor: string | null
) {
  return {
    data: {
      products: {
        edges: nodes.map((node) => ({ node, cursor: `cursor-${node.id}` })),
        pageInfo: { hasNextPage, endCursor },
      },
    },
  };
}

describe("BulkImportService", () => {
  let mockClient: FibermadeClient;
  let mockGraphql: ShopifyGraphqlRunner;
  let updateConnection: UpdateConnectionFn;

  beforeEach(() => {
    vi.clearAllMocks();
    mockClient = {} as FibermadeClient;
    mockGraphql = vi.fn();
    updateConnection = vi.fn().mockResolvedValue(undefined);
    mockImportProduct.mockResolvedValue({
      colorwayId: 1,
      bases: [{ id: 1 }],
      inventoryRecords: [{ id: 1, base_id: 1 }],
      skipped: false,
    });
  });

  afterEach(() => {
    vi.clearAllMocks();
  });

  it("fetches products with cursor-based pagination and processes each through ProductSyncService", async () => {
    const node1 = makeProductNode("1", "Product 1");
    mockGraphql = vi.fn().mockImplementation((query: string) => {
      if (query.includes("products(first:")) {
        return Promise.resolve(makeProductsResponse([node1], false, null));
      }
      if (query.includes("collections(first:")) {
        return Promise.resolve({
          data: {
            collections: {
              edges: [],
              pageInfo: { hasNextPage: false, endCursor: null },
            },
          },
        });
      }
      return Promise.resolve({ data: {} });
    });

    const service = new BulkImportService(
      mockClient,
      99,
      "test.myshopify.com",
      mockGraphql,
      updateConnection
    );
    const result = await service.runImport();

    expect(mockGraphql).toHaveBeenCalledTimes(2);
    expect(mockGraphql).toHaveBeenCalledWith(
      expect.stringContaining("products(first:"),
      {}
    );
    expect(mockImportProduct).toHaveBeenCalledTimes(1);
    expect(mockImportProduct).toHaveBeenCalledWith(
      expect.objectContaining({
        id: "gid://shopify/Product/1",
        title: "Product 1",
      })
    );
    expect(result.total).toBe(1);
    expect(result.imported).toBe(1);
    expect(result.failed).toBe(0);
  });

  it("handles pagination: passes cursor when hasNextPage is true and stops when false", async () => {
    const node1 = makeProductNode("1", "P1");
    const node2 = makeProductNode("2", "P2");
    let productCallCount = 0;
    mockGraphql = vi.fn().mockImplementation((query: string) => {
      if (query.includes("products(first:")) {
        productCallCount++;
        if (productCallCount === 1) {
          return Promise.resolve(makeProductsResponse([node1], true, "cursor-page-1"));
        }
        return Promise.resolve(makeProductsResponse([node2], false, null));
      }
      if (query.includes("collections(first:")) {
        return Promise.resolve({
          data: {
            collections: {
              edges: [],
              pageInfo: { hasNextPage: false, endCursor: null },
            },
          },
        });
      }
      return Promise.resolve({ data: {} });
    });

    const service = new BulkImportService(
      mockClient,
      99,
      "test.myshopify.com",
      mockGraphql,
      updateConnection
    );
    const result = await service.runImport();

    expect(mockGraphql).toHaveBeenCalledTimes(3);
    expect(mockGraphql).toHaveBeenNthCalledWith(1, expect.any(String), {});
    expect(mockGraphql).toHaveBeenNthCalledWith(2, expect.any(String), {
      cursor: "cursor-page-1",
    });
    expect(mockGraphql).toHaveBeenNthCalledWith(3, expect.stringContaining("collections(first:"), expect.anything());
    expect(mockImportProduct).toHaveBeenCalledTimes(2);
    expect(result.total).toBe(2);
    expect(result.imported).toBe(2);
  });

  it("updates initialImportProgress after each batch with correct counts", async () => {
    const node1 = makeProductNode("1", "P1");
    const node2 = makeProductNode("2", "P2");
    mockGraphql = vi
      .fn()
      .mockResolvedValueOnce(
        makeProductsResponse([node1], true, "c1")
      )
      .mockResolvedValueOnce(
        makeProductsResponse([node2], false, null)
      );

    const service = new BulkImportService(
      mockClient,
      99,
      "test.myshopify.com",
      mockGraphql,
      updateConnection
    );
    await service.runImport();

    expect(updateConnection).toHaveBeenCalled();
    const inProgressCalls = (updateConnection as ReturnType<typeof vi.fn>).mock.calls.filter(
      (c) => c[0].initialImportStatus === "in_progress"
    );
    const progressPayloads = inProgressCalls.map((c) =>
      JSON.parse(c[0].initialImportProgress ?? "{}")
    );
    const afterFirstBatch = progressPayloads.find((p) => p.total === 1);
    const afterSecondBatch = progressPayloads.find((p) => p.total === 2);
    expect(afterFirstBatch).toBeDefined();
    expect(afterFirstBatch?.imported).toBe(1);
    expect(afterSecondBatch).toBeDefined();
    expect(afterSecondBatch?.imported).toBe(2);
  });

  it("sets initialImportStatus to complete after all products processed", async () => {
    mockGraphql = vi.fn().mockResolvedValue(
      makeProductsResponse([makeProductNode("1", "P1")], false, null)
    );

    const service = new BulkImportService(
      mockClient,
      99,
      "test.myshopify.com",
      mockGraphql,
      updateConnection
    );
    await service.runImport();

    expect(updateConnection).toHaveBeenCalledWith(
      expect.objectContaining({
        initialImportStatus: "complete",
        initialImportProgress: expect.any(String),
      })
    );
  });

  it("increments failed count on single product failure and continues import", async () => {
    const node1 = makeProductNode("1", "P1");
    const node2 = makeProductNode("2", "P2");
    mockGraphql = vi.fn().mockResolvedValue(
      makeProductsResponse([node1, node2], false, null)
    );
    mockImportProduct
      .mockRejectedValueOnce(new Error("API error"))
      .mockResolvedValueOnce({
        colorwayId: 1,
        bases: [],
        inventoryRecords: [],
        skipped: false,
      });

    const service = new BulkImportService(
      mockClient,
      99,
      "test.myshopify.com",
      mockGraphql,
      updateConnection
    );
    const result = await service.runImport();

    expect(result.imported).toBe(1);
    expect(result.failed).toBe(1);
    expect(result.errors).toHaveLength(1);
    expect(result.errors?.[0].message).toBe("API error");
    expect(updateConnection).toHaveBeenLastCalledWith(
      expect.objectContaining({
        initialImportStatus: "complete",
      })
    );
  });

  it("sets initialImportStatus to failed on GraphQL pagination error", async () => {
    mockGraphql = vi.fn().mockRejectedValue(new Error("GraphQL network error"));

    const service = new BulkImportService(
      mockClient,
      99,
      "test.myshopify.com",
      mockGraphql,
      updateConnection
    );
    const result = await service.runImport();

    expect(result.total).toBe(0);
    expect(updateConnection).toHaveBeenCalledWith(
      expect.objectContaining({
        initialImportStatus: "failed",
      })
    );
  });

  it("skips already-imported products and counts them as imported (resumability)", async () => {
    const node1 = makeProductNode("1", "P1");
    mockGraphql = vi.fn().mockResolvedValue(
      makeProductsResponse([node1], false, null)
    );
    mockImportProduct.mockResolvedValue({
      colorwayId: 1,
      bases: [],
      inventoryRecords: [],
      skipped: true,
    });

    const service = new BulkImportService(
      mockClient,
      99,
      "test.myshopify.com",
      mockGraphql,
      updateConnection
    );
    const result = await service.runImport();

    expect(mockImportProduct).toHaveBeenCalledTimes(1);
    expect(result.imported).toBe(1);
    expect(result.failed).toBe(0);
  });

  it("handles empty store: zero products, status set to complete", async () => {
    mockGraphql = vi.fn().mockResolvedValue(
      makeProductsResponse([], false, null)
    );

    const service = new BulkImportService(
      mockClient,
      99,
      "test.myshopify.com",
      mockGraphql,
      updateConnection
    );
    const result = await service.runImport();

    expect(result.total).toBe(0);
    expect(result.imported).toBe(0);
    expect(result.failed).toBe(0);
    expect(mockImportProduct).not.toHaveBeenCalled();
    expect(updateConnection).toHaveBeenCalledWith(
      expect.objectContaining({
        initialImportStatus: "complete",
      })
    );
  });
});
