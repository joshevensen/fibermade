import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import type { FibermadeClient } from "../fibermade-client.server";
import type { BaseData } from "../fibermade-client.types";
import {
  findExistingBase,
  mapProductToColorwayPayload,
  mapVariantToBasePayload,
  parseNumericIdFromGid,
  ProductSyncService,
} from "./product-sync.server";
import type { ShopifyProduct, ShopifyVariant } from "./types";

vi.mock("./mapping.server", () => ({
  mappingExists: vi.fn(),
  createMapping: vi.fn().mockResolvedValue({}),
  findFibermadeIdByShopifyGid: vi.fn(),
}));

const { mappingExists, createMapping, findFibermadeIdByShopifyGid } = await import(
  "./mapping.server"
);

describe("parseNumericIdFromGid", () => {
  it("extracts numeric id from product GID", () => {
    expect(parseNumericIdFromGid("gid://shopify/Product/1234567890")).toBe("1234567890");
  });
  it("extracts numeric id from variant GID", () => {
    expect(parseNumericIdFromGid("gid://shopify/ProductVariant/9876543210")).toBe(
      "9876543210"
    );
  });
});

describe("mapProductToColorwayPayload", () => {
  it("maps title to name, descriptionHtml to description", () => {
    const product: ShopifyProduct = {
      id: "gid://shopify/Product/1",
      title: "My Yarn",
      descriptionHtml: "<p>Soft yarn</p>",
      status: "ACTIVE",
    };
    const payload = mapProductToColorwayPayload(product);
    expect(payload.name).toBe("My Yarn");
    expect(payload.description).toBe("<p>Soft yarn</p>");
    expect(payload.per_pan).toBe(1);
    expect(payload.status).toBe("active");
  });
  it("maps ACTIVE to active, DRAFT to idea, ARCHIVED to retired", () => {
    expect(
      mapProductToColorwayPayload({
        id: "gid://shopify/Product/1",
        title: "P",
        status: "ACTIVE",
      } as ShopifyProduct).status
    ).toBe("active");
    expect(
      mapProductToColorwayPayload({
        id: "gid://shopify/Product/1",
        title: "P",
        status: "DRAFT",
      } as ShopifyProduct).status
    ).toBe("idea");
    expect(
      mapProductToColorwayPayload({
        id: "gid://shopify/Product/1",
        title: "P",
        status: "ARCHIVED",
      } as ShopifyProduct).status
    ).toBe("retired");
  });
  it("defaults per_pan to 1", () => {
    const payload = mapProductToColorwayPayload({
      id: "gid://shopify/Product/1",
      title: "P",
      status: "ACTIVE",
    } as ShopifyProduct);
    expect(payload.per_pan).toBe(1);
  });
});

describe("mapVariantToBasePayload", () => {
  it("maps variant title to descriptor", () => {
    const variant: ShopifyVariant = {
      id: "gid://shopify/ProductVariant/1",
      title: "DK Weight",
      price: "29.99",
    };
    const payload = mapVariantToBasePayload(variant, "Product Title");
    expect(payload.descriptor).toBe("DK Weight");
    expect(payload.status).toBe("active");
    expect(payload.retail_price).toBe(29.99);
    expect(payload.weight).toBeNull();
  });
  it("uses product title as descriptor when variant title is Default Title", () => {
    const variant: ShopifyVariant = {
      id: "gid://shopify/ProductVariant/1",
      title: "Default Title",
      price: "19.00",
    };
    const payload = mapVariantToBasePayload(variant, "Merino DK");
    expect(payload.descriptor).toBe("Merino DK");
  });
  it("parses price to retail_price", () => {
    const payload = mapVariantToBasePayload(
      {
        id: "gid://shopify/ProductVariant/1",
        title: "Skein",
        price: "32.50",
      } as ShopifyVariant,
      "Product"
    );
    expect(payload.retail_price).toBe(32.5);
  });
  it("does not send code (platform auto-generates)", () => {
    const payload = mapVariantToBasePayload(
      {
        id: "gid://shopify/ProductVariant/1",
        title: "Skein",
        sku: "SKU-123",
        price: "10",
      } as ShopifyVariant,
      "Product"
    );
    expect("code" in payload).toBe(false);
  });
});

describe("findExistingBase", () => {
  const bases: BaseData[] = [
    {
      id: 1,
      descriptor: "DK Weight",
      description: null,
      code: "DW",
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
    },
    {
      id: 2,
      descriptor: "Fingering",
      description: null,
      code: "F",
      status: "active",
      weight: null,
      size: null,
      cost: null,
      retail_price: "24.00",
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
    },
  ];

  it("returns base when descriptor matches exactly", () => {
    expect(findExistingBase(bases, "DK Weight")).toEqual(bases[0]);
  });
  it("returns base when descriptor matches after normalizing spaces/case", () => {
    expect(findExistingBase(bases, "  dk weight  ")).toEqual(bases[0]);
    expect(findExistingBase(bases, "dkweight")).toEqual(bases[0]);
  });
  it("returns null when no match", () => {
    expect(findExistingBase(bases, "Worsted")).toBeNull();
  });
  it("optionally matches retail_price", () => {
    expect(findExistingBase(bases, "DK Weight", 29.99)).toEqual(bases[0]);
    expect(findExistingBase(bases, "DK Weight", 30)).toBeNull();
  });
});

describe("ProductSyncService#importProduct", () => {
  const integrationId = 1;
  const shopDomain = "test.myshopify.com";

  let mockClient: {
    createColorway: ReturnType<typeof vi.fn>;
    createBase: ReturnType<typeof vi.fn>;
    createInventory: ReturnType<typeof vi.fn>;
    createIntegrationLog: ReturnType<typeof vi.fn>;
    getColorway: ReturnType<typeof vi.fn>;
    listBases: ReturnType<typeof vi.fn>;
  };
  let client: FibermadeClient;

  function standardProduct(overrides?: Partial<ShopifyProduct>): ShopifyProduct {
    return {
      id: "gid://shopify/Product/100",
      title: "Merino DK",
      descriptionHtml: "<p>Soft</p>",
      status: "ACTIVE",
      handle: "merino-dk",
      variants: {
        edges: [
          {
            node: {
              id: "gid://shopify/ProductVariant/200",
              title: "DK Weight",
              price: "29.99",
            },
          },
          {
            node: {
              id: "gid://shopify/ProductVariant/201",
              title: "Fingering",
              price: "24.00",
            },
          },
        ],
      },
      ...overrides,
    };
  }

  beforeEach(() => {
    vi.mocked(mappingExists).mockResolvedValue(false);
    vi.mocked(createMapping).mockResolvedValue({} as never);
    vi.mocked(findFibermadeIdByShopifyGid).mockResolvedValue(null);

    mockClient = {
      createColorway: vi.fn().mockResolvedValue({ id: 10, name: "Merino DK" }),
      createBase: vi
        .fn()
        .mockImplementation((payload: { descriptor: string }) =>
          Promise.resolve({
            id: payload.descriptor === "DK Weight" ? 20 : 21,
            descriptor: payload.descriptor,
          })
        ),
      createInventory: vi
        .fn()
        .mockImplementation((payload: { colorway_id: number; base_id: number }) =>
          Promise.resolve({
            id: 30 + payload.base_id,
            colorway_id: payload.colorway_id,
            base_id: payload.base_id,
            quantity: 0,
          })
        ),
      createIntegrationLog: vi.fn().mockResolvedValue({}),
      getColorway: vi.fn().mockResolvedValue({
        id: 10,
        name: "Merino DK",
        inventories: [
          { id: 31, colorway_id: 10, base_id: 20, quantity: 0 },
          { id: 32, colorway_id: 10, base_id: 21, quantity: 0 },
        ],
      }),
      listBases: vi.fn().mockResolvedValue({ data: [], meta: { total: 0 } }),
    };
    client = mockClient as unknown as FibermadeClient;
  });

  afterEach(() => {
    vi.clearAllMocks();
  });

  it("creates Colorway, Bases, Inventory records, and mappings for a standard product", async () => {
    const service = new ProductSyncService(client, integrationId, shopDomain);
    const product = standardProduct();

    const result = await service.importProduct(product);

    expect(result.colorwayId).toBe(10);
    expect(result.bases).toHaveLength(2);
    expect(result.inventoryRecords).toHaveLength(2);
    expect(result.skipped).toBeUndefined();

    expect(mockClient.createColorway).toHaveBeenCalledTimes(1);
    expect(mockClient.createColorway).toHaveBeenCalledWith(
      expect.objectContaining({
        name: "Merino DK",
        description: "<p>Soft</p>",
        per_pan: 1,
        status: "active",
      })
    );
    expect(mockClient.createBase).toHaveBeenCalledTimes(2);
    expect(mockClient.createInventory).toHaveBeenCalledTimes(2);
    expect(createMapping).toHaveBeenCalledTimes(3);
    expect(mockClient.createIntegrationLog).toHaveBeenCalledTimes(1);
    expect(mockClient.createIntegrationLog).toHaveBeenCalledWith(
      integrationId,
      expect.objectContaining({
        status: "success",
        message: expect.stringContaining("Merino DK"),
        metadata: expect.objectContaining({
          shopify_gid: "gid://shopify/Product/100",
          variant_count: 2,
        }),
      })
    );
  });

  it("multi-variant product creates multiple Bases and Inventory records", async () => {
    const product = standardProduct();
    const service = new ProductSyncService(client, integrationId, shopDomain);

    const result = await service.importProduct(product);

    expect(result.bases.length).toBe(2);
    expect(result.inventoryRecords.length).toBe(2);
    expect(mockClient.createBase).toHaveBeenCalledTimes(2);
    expect(mockClient.createInventory).toHaveBeenCalledWith({
      colorway_id: 10,
      base_id: expect.any(Number),
      quantity: 0,
    });
  });

  it("single-variant with Default Title uses product title as descriptor", async () => {
    const product: ShopifyProduct = {
      id: "gid://shopify/Product/1",
      title: "Single Color Merino",
      status: "ACTIVE",
      variants: {
        edges: [
          {
            node: {
              id: "gid://shopify/ProductVariant/1",
              title: "Default Title",
              price: "25.00",
            },
          },
        ],
      },
    };
    const service = new ProductSyncService(client, integrationId, shopDomain);

    await service.importProduct(product);

    expect(mockClient.createBase).toHaveBeenCalledWith(
      expect.objectContaining({
        descriptor: "Single Color Merino",
        status: "active",
        retail_price: 25,
      })
    );
  });

  it("skips import when product is already mapped and returns existing mapping", async () => {
    vi.mocked(mappingExists).mockResolvedValue(true);
    vi.mocked(findFibermadeIdByShopifyGid).mockResolvedValue({
      identifiableType: "App\\Models\\Colorway",
      identifiableId: 10,
    });
    mockClient.getColorway.mockResolvedValueOnce({
      id: 10,
      name: "Existing",
      inventories: [
        { id: 31, colorway_id: 10, base_id: 20, quantity: 0 },
        { id: 32, colorway_id: 10, base_id: 21, quantity: 0 },
      ],
    });

    const service = new ProductSyncService(client, integrationId, shopDomain);
    const result = await service.importProduct(standardProduct());

    expect(result.skipped).toBe(true);
    expect(result.colorwayId).toBe(10);
    expect(result.bases).toEqual([{ id: 20 }, { id: 21 }]);
    expect(result.inventoryRecords).toHaveLength(2);

    expect(mockClient.createColorway).not.toHaveBeenCalled();
    expect(mockClient.createBase).not.toHaveBeenCalled();
    expect(mockClient.createInventory).not.toHaveBeenCalled();
    expect(mockClient.getColorway).toHaveBeenCalledWith(10);
  });

  it("partial failure: one variant failing does not prevent other variants", async () => {
    const product = standardProduct();
    mockClient.createBase
      .mockRejectedValueOnce(new Error("Base create failed"))
      .mockResolvedValueOnce({ id: 21, descriptor: "Fingering" });

    const service = new ProductSyncService(client, integrationId, shopDomain);
    const result = await service.importProduct(product);

    expect(result.bases).toHaveLength(1);
    expect(result.inventoryRecords).toHaveLength(1);
    expect(mockClient.createBase).toHaveBeenCalledTimes(2);
    expect(mockClient.createInventory).toHaveBeenCalledTimes(1);
  });

  it("reuses existing Base when listBases returns matching descriptor", async () => {
    const existingBase: BaseData = {
      id: 99,
      descriptor: "DK Weight",
      description: null,
      code: "DW",
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
    };
    mockClient.listBases.mockResolvedValue({
      data: [existingBase],
      meta: { total: 1 },
    });

    const product = standardProduct();
    const service = new ProductSyncService(client, integrationId, shopDomain);
    const result = await service.importProduct(product);

    expect(mockClient.createBase).toHaveBeenCalledTimes(1);
    expect(mockClient.createBase).toHaveBeenCalledWith(
      expect.objectContaining({ descriptor: "Fingering" })
    );
    expect(result.bases.map((b) => b.id)).toContain(99);
    expect(mockClient.createInventory).toHaveBeenCalledWith(
      expect.objectContaining({ base_id: 99 })
    );
  });

  it("ProductSyncResult contains correct IDs for created path", async () => {
    const product = standardProduct();
    const service = new ProductSyncService(client, integrationId, shopDomain);
    const result = await service.importProduct(product);

    expect(result).toMatchObject({
      colorwayId: 10,
      bases: expect.arrayContaining([{ id: expect.any(Number) }, { id: expect.any(Number) }]),
      inventoryRecords: expect.arrayContaining([
        { id: expect.any(Number), base_id: expect.any(Number) },
        { id: expect.any(Number), base_id: expect.any(Number) },
      ]),
    });
    expect(result.inventoryRecords.length).toBe(result.bases.length);
  });

  it("on failed import (createColorway throws) creates integration log with status error", async () => {
    mockClient.createColorway.mockRejectedValueOnce(new Error("API unavailable"));
    const service = new ProductSyncService(client, integrationId, shopDomain);

    await expect(service.importProduct(standardProduct())).rejects.toThrow("API unavailable");

    expect(mockClient.createIntegrationLog).toHaveBeenCalledTimes(1);
    expect(mockClient.createIntegrationLog).toHaveBeenCalledWith(
      integrationId,
      expect.objectContaining({
        status: "error",
        message: "API unavailable",
        metadata: expect.objectContaining({ shopify_gid: "gid://shopify/Product/100" }),
      })
    );
  });

  it("on partial failure (one variant fails) creates integration log with status warning", async () => {
    mockClient.createBase
      .mockRejectedValueOnce(new Error("Base create failed"))
      .mockResolvedValueOnce({ id: 21, descriptor: "Fingering" });
    const service = new ProductSyncService(client, integrationId, shopDomain);

    const result = await service.importProduct(standardProduct());

    expect(result.bases).toHaveLength(1);
    expect(result.inventoryRecords).toHaveLength(1);
    expect(mockClient.createIntegrationLog).toHaveBeenCalledTimes(1);
    expect(mockClient.createIntegrationLog).toHaveBeenCalledWith(
      integrationId,
      expect.objectContaining({
        status: "warning",
        message: expect.stringMatching(/Partial import.*1.*variant.*failed/),
        metadata: expect.objectContaining({
          shopify_gid: "gid://shopify/Product/100",
          variant_count: 2,
        }),
      })
    );
  });
});
