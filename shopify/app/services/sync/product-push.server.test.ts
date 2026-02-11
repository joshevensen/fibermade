import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import type { FibermadeClient } from "../fibermade-client.server";
import type { BaseData } from "../fibermade-client.types";
import { ProductPushService } from "./product-push.server";

vi.mock("./mapping.server", () => ({
  createMapping: vi.fn().mockResolvedValue({}),
  findShopifyGidByFibermadeId: vi.fn(),
}));

const { createMapping, findShopifyGidByFibermadeId } = await import(
  "./mapping.server"
);

function baseData(overrides?: Partial<BaseData>): BaseData {
  return {
    id: 1,
    descriptor: "Merino Worsted",
    description: null,
    code: "MW",
    status: "active",
    weight: null,
    size: null,
    cost: null,
    retail_price: "29.99",
    wool_percent: null,
    nylon_percent: null,
    alpaca_percent: null,
    yak_percent: null,
    camel_percent: null,
    cotton_percent: null,
    bamboo_percent: null,
    silk_percent: null,
    linen_percent: null,
    created_at: "",
    updated_at: "",
    ...overrides,
  };
}

function colorwayWithBases(
  colorwayId: number,
  bases: BaseData[],
  overrides?: { name?: string; description?: string | null; status?: string }
) {
  const inventories = bases.map((base, i) => ({
    id: 100 + i,
    colorway_id: colorwayId,
    base_id: base.id,
    quantity: 0,
    created_at: "",
    updated_at: "",
    base,
  }));
  return {
    id: colorwayId,
    name: overrides?.name ?? "Red Merino Yarn",
    description: overrides?.description ?? "<p>Soft merino</p>",
    technique: null,
    colors: [],
    per_pan: 1,
    status: overrides?.status ?? "active",
    created_at: "",
    updated_at: "",
    inventories,
  };
}

describe("ProductPushService#pushColorway", () => {
  const integrationId = 1;
  const shopDomain = "test.myshopify.com";

  let mockClient: {
    getColorway: ReturnType<typeof vi.fn>;
    getBase: ReturnType<typeof vi.fn>;
    createIntegrationLog: ReturnType<typeof vi.fn>;
  };
  let mockGraphql: ReturnType<typeof vi.fn>;
  let client: FibermadeClient;

  beforeEach(() => {
    vi.mocked(findShopifyGidByFibermadeId).mockResolvedValue(null);
    vi.mocked(createMapping).mockResolvedValue({} as never);

    mockClient = {
      getColorway: vi.fn(),
      getBase: vi.fn(),
      createIntegrationLog: vi.fn().mockResolvedValue({}),
    };
    client = mockClient as unknown as FibermadeClient;

    mockGraphql = vi.fn().mockResolvedValue({
      data: {
        productCreate: {
          product: {
            id: "gid://shopify/Product/999",
            handle: "red-merino-yarn",
            variants: {
              edges: [
                { node: { id: "gid://shopify/ProductVariant/1001" } },
                { node: { id: "gid://shopify/ProductVariant/1002" } },
              ],
            },
          },
          userErrors: [],
        },
      },
    });
  });

  afterEach(() => {
    vi.clearAllMocks();
  });

  it("fetches Colorway, maps fields to Shopify input, and calls productCreate mutation", async () => {
    const bases = [
      baseData({ id: 1, descriptor: "Merino Worsted", code: "MW", retail_price: "29.99" }),
      baseData({ id: 2, descriptor: "Merino Fingering", code: "MF", retail_price: "24.99" }),
    ];
    mockClient.getColorway.mockResolvedValue(
      colorwayWithBases(42, bases)
    );

    const service = new ProductPushService(
      client,
      integrationId,
      shopDomain,
      mockGraphql
    );
    const result = await service.pushColorway(42);

    expect(mockClient.getColorway).toHaveBeenCalledWith(42);
    expect(mockGraphql).toHaveBeenCalledTimes(1);
    const [, { product }] = mockGraphql.mock.calls[0];
    expect(product.title).toBe("Red Merino Yarn");
    expect(product.descriptionHtml).toBe("<p>Soft merino</p>");
    expect(product.status).toBe("ACTIVE");
    expect(result.shopifyProductGid).toBe("gid://shopify/Product/999");
    expect(result.colorwayId).toBe(42);
    expect(result.skipped).toBeUndefined();
  });

  it("maps status: active→ACTIVE, idea→DRAFT, retired→ARCHIVED", async () => {
    for (const [fibermadeStatus, shopifyStatus] of [
      ["active", "ACTIVE"],
      ["idea", "DRAFT"],
      ["retired", "ARCHIVED"],
    ] as const) {
      vi.mocked(findShopifyGidByFibermadeId).mockResolvedValue(null);
      mockClient.getColorway.mockResolvedValue(
        colorwayWithBases(10, [baseData()], { status: fibermadeStatus })
      );
      mockGraphql.mockResolvedValue({
        data: {
          productCreate: {
            product: { id: "gid://shopify/Product/1", handle: null, variants: { edges: [{ node: { id: "gid://shopify/ProductVariant/1" } }] } },
            userErrors: [],
          },
        },
      });

      const service = new ProductPushService(
        client,
        integrationId,
        shopDomain,
        mockGraphql
      );
      await service.pushColorway(10);

      const product = mockGraphql.mock.calls[mockGraphql.mock.calls.length - 1][1].product;
      expect(product.status).toBe(shopifyStatus);
    }
  });

  it("maps variant: descriptor→option value, code→sku, retail_price→price", async () => {
    const bases = [
      baseData({ descriptor: "Merino Worsted", code: "MW", retail_price: "29.99" }),
    ];
    mockClient.getColorway.mockResolvedValue(colorwayWithBases(42, bases));

    const service = new ProductPushService(
      client,
      integrationId,
      shopDomain,
      mockGraphql
    );
    await service.pushColorway(42);

    const product = mockGraphql.mock.calls[0][1].product;
    expect(product.productOptions).toEqual([
      { name: "Base", values: [{ name: "Merino Worsted" }] },
    ]);
    expect(product.variants).toHaveLength(1);
    expect(product.variants[0]).toMatchObject({
      optionValues: [{ optionName: "Base", name: "Merino Worsted" }],
      sku: "MW",
      price: "29.99",
    });
  });

  it("includes metafields: fibermade.colorway_id on product, fibermade.base_id on variants", async () => {
    const bases = [baseData({ id: 7 })];
    mockClient.getColorway.mockResolvedValue(colorwayWithBases(42, bases));

    const service = new ProductPushService(
      client,
      integrationId,
      shopDomain,
      mockGraphql
    );
    await service.pushColorway(42);

    const product = mockGraphql.mock.calls[0][1].product;
    expect(product.metafields).toContainEqual({
      namespace: "fibermade",
      key: "colorway_id",
      value: "42",
      type: "number_integer",
    });
    expect(product.variants[0].metafields).toContainEqual({
      namespace: "fibermade",
      key: "base_id",
      value: "7",
      type: "number_integer",
    });
  });

  it("creates ExternalIdentifier for product and each variant after success", async () => {
    const bases = [
      baseData({ id: 1 }),
      baseData({ id: 2 }),
    ];
    mockClient.getColorway.mockResolvedValue(colorwayWithBases(42, bases));

    const service = new ProductPushService(
      client,
      integrationId,
      shopDomain,
      mockGraphql
    );
    await service.pushColorway(42);

    expect(createMapping).toHaveBeenCalledTimes(3);
    expect(createMapping).toHaveBeenNthCalledWith(
      1,
      client,
      integrationId,
      "App\\Models\\Colorway",
      42,
      "shopify_product",
      "gid://shopify/Product/999",
      expect.objectContaining({
        admin_url: "https://test.myshopify.com/admin/products/999",
        shopify_handle: "red-merino-yarn",
      })
    );
    expect(createMapping).toHaveBeenNthCalledWith(
      2,
      client,
      integrationId,
      "App\\Models\\Inventory",
      100,
      "shopify_variant",
      "gid://shopify/ProductVariant/1001",
      expect.objectContaining({
        admin_url: "https://test.myshopify.com/admin/products/999/variants/1001",
      })
    );
    expect(createMapping).toHaveBeenNthCalledWith(
      3,
      client,
      integrationId,
      "App\\Models\\Inventory",
      101,
      "shopify_variant",
      "gid://shopify/ProductVariant/1002",
      expect.any(Object)
    );
  });

  it("skips when Colorway already has Shopify mapping", async () => {
    vi.mocked(findShopifyGidByFibermadeId).mockResolvedValue("gid://shopify/Product/123");

    const service = new ProductPushService(
      client,
      integrationId,
      shopDomain,
      mockGraphql
    );
    const result = await service.pushColorway(42);

    expect(result.skipped).toBe(true);
    expect(result.shopifyProductGid).toBe("gid://shopify/Product/123");
    expect(result.variantMappings).toEqual([]);
    expect(mockClient.getColorway).not.toHaveBeenCalled();
    expect(mockGraphql).not.toHaveBeenCalled();
    expect(createMapping).not.toHaveBeenCalled();
  });

  it("zero Inventory creates product with single default variant", async () => {
    mockClient.getColorway.mockResolvedValue({
      id: 42,
      name: "Solo Colorway",
      description: null,
      technique: null,
      colors: [],
      per_pan: 1,
      status: "active",
      created_at: "",
      updated_at: "",
      inventories: [],
    });
    mockGraphql.mockResolvedValue({
      data: {
        productCreate: {
          product: {
            id: "gid://shopify/Product/1",
            handle: null,
            variants: { edges: [{ node: { id: "gid://shopify/ProductVariant/1" } }] },
          },
          userErrors: [],
        },
      },
    });

    const service = new ProductPushService(
      client,
      integrationId,
      shopDomain,
      mockGraphql
    );
    const result = await service.pushColorway(42);

    const product = mockGraphql.mock.calls[0][1].product;
    expect(product.productOptions).toEqual([{ name: "Base", values: [{ name: "Solo Colorway" }] }]);
    expect(product.variants).toHaveLength(1);
    expect(product.variants[0]).toMatchObject({
      optionValues: [{ optionName: "Base", name: "Solo Colorway" }],
      sku: "",
      price: "0",
    });
    expect(result.variantMappings).toHaveLength(0);
  });

  it("multi-variant creates product with Base option and multiple variants", async () => {
    const bases = [
      baseData({ id: 1, descriptor: "Worsted", code: "W", retail_price: "28.00" }),
      baseData({ id: 2, descriptor: "Fingering", code: "F", retail_price: "22.00" }),
    ];
    mockClient.getColorway.mockResolvedValue(colorwayWithBases(10, bases));

    const service = new ProductPushService(
      client,
      integrationId,
      shopDomain,
      mockGraphql
    );
    await service.pushColorway(10);

    const product = mockGraphql.mock.calls[0][1].product;
    expect(product.productOptions).toEqual([
      { name: "Base", values: [{ name: "Worsted" }, { name: "Fingering" }] },
    ]);
    expect(product.variants).toHaveLength(2);
    expect(product.variants[0].optionValues).toEqual([{ optionName: "Base", name: "Worsted" }]);
    expect(product.variants[1].optionValues).toEqual([{ optionName: "Base", name: "Fingering" }]);
  });

  it("throws when GraphQL returns userErrors", async () => {
    mockClient.getColorway.mockResolvedValue(
      colorwayWithBases(42, [baseData()])
    );
    mockGraphql.mockResolvedValue({
      data: {
        productCreate: {
          product: null,
          userErrors: [{ field: ["product"], message: "Title can't be blank" }],
        },
      },
    });

    const service = new ProductPushService(
      client,
      integrationId,
      shopDomain,
      mockGraphql
    );

    await expect(service.pushColorway(42)).rejects.toThrow(
      /productCreate failed.*Title can't be blank/
    );
    expect(mockClient.createIntegrationLog).toHaveBeenCalledWith(
      integrationId,
      expect.objectContaining({
        status: "error",
        message: expect.stringContaining("Title can't be blank"),
      })
    );
  });

  it("creates IntegrationLog on success", async () => {
    mockClient.getColorway.mockResolvedValue(
      colorwayWithBases(42, [baseData()])
    );
    mockGraphql.mockResolvedValue({
      data: {
        productCreate: {
          product: {
            id: "gid://shopify/Product/1",
            handle: null,
            variants: { edges: [{ node: { id: "gid://shopify/ProductVariant/1" } }] },
          },
          userErrors: [],
        },
      },
    });

    const service = new ProductPushService(
      client,
      integrationId,
      shopDomain,
      mockGraphql
    );
    await service.pushColorway(42);

    expect(mockClient.createIntegrationLog).toHaveBeenCalledWith(
      integrationId,
      expect.objectContaining({
        loggable_type: "App\\Models\\Colorway",
        loggable_id: 42,
        status: "success",
        message: expect.stringContaining("Pushed Colorway"),
        metadata: expect.objectContaining({
          shopify_gid: "gid://shopify/Product/1",
          variant_count: 1,
        }),
      })
    );
  });

  it("propagates error when getColorway throws (Colorway not found)", async () => {
    const notFoundError = new Error("Colorway not found");
    (notFoundError as Error & { status?: number }).status = 404;
    mockClient.getColorway.mockRejectedValue(notFoundError);

    const service = new ProductPushService(
      client,
      integrationId,
      shopDomain,
      mockGraphql
    );

    await expect(service.pushColorway(999)).rejects.toThrow("Colorway not found");
    expect(mockGraphql).not.toHaveBeenCalled();
  });

  it("fetches base via getBase when not nested in inventory", async () => {
    const base = baseData({ id: 5, descriptor: "DK", code: "DK", retail_price: "26.00" });
    mockClient.getColorway.mockResolvedValue({
      id: 42,
      name: "Test",
      description: null,
      technique: null,
      colors: [],
      per_pan: 1,
      status: "active",
      created_at: "",
      updated_at: "",
      inventories: [
        {
          id: 100,
          colorway_id: 42,
          base_id: 5,
          quantity: 0,
          created_at: "",
          updated_at: "",
          base: undefined,
        },
      ],
    });
    mockClient.getBase.mockResolvedValue(base);
    mockGraphql.mockResolvedValue({
      data: {
        productCreate: {
          product: {
            id: "gid://shopify/Product/1",
            handle: null,
            variants: { edges: [{ node: { id: "gid://shopify/ProductVariant/1" } }] },
          },
          userErrors: [],
        },
      },
    });

    const service = new ProductPushService(
      client,
      integrationId,
      shopDomain,
      mockGraphql
    );
    await service.pushColorway(42);

    expect(mockClient.getBase).toHaveBeenCalledWith(5);
    const product = mockGraphql.mock.calls[0][1].product;
    expect(product.variants[0]).toMatchObject({
      sku: "DK",
      price: "26.00",
    });
  });
});
