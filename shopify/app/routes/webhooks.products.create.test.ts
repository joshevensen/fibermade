import { beforeEach, describe, expect, it, vi } from "vitest";
import { action } from "./webhooks.products.create";

const mockImportProduct = vi.fn();

vi.mock("../shopify.server", () => ({
  authenticate: {
    webhook: vi.fn(),
  },
}));

vi.mock("../db.server", () => ({
  default: {
    fibermadeConnection: {
      findUnique: vi.fn(),
    },
  },
}));

vi.mock("../services/sync/product-sync.server", () => ({
  ProductSyncService: vi.fn().mockImplementation(function (this: { importProduct: ReturnType<typeof vi.fn> }) {
    this.importProduct = mockImportProduct;
    return this;
  }),
}));

import { authenticate } from "../shopify.server";
import db from "../db.server";
import { ProductSyncService } from "../services/sync/product-sync.server";

describe("webhooks.products.create", () => {
  const mockPayload = {
    id: 1234567890,
    title: "Test Product",
    body_html: "<p>Desc</p>",
    status: "active",
    handle: "test-product",
    variants: [{ id: 9876543210, title: "Default Title", price: "29.99" }],
  };

  const mockConnection = {
    id: 1,
    shop: "test.myshopify.com",
    fibermadeApiToken: "token",
    fibermadeIntegrationId: 42,
    connectedAt: new Date(),
  };

  beforeEach(() => {
    vi.clearAllMocks();
    mockImportProduct.mockResolvedValue({
      colorwayId: 1,
      bases: [],
      inventoryRecords: [],
    });
    vi.mocked(authenticate.webhook).mockResolvedValue({
      shop: "test.myshopify.com",
      session: { shop: "test.myshopify.com" },
      admin: { graphql: vi.fn() },
      payload: mockPayload,
      topic: "products/create",
    } as never);
    vi.mocked(db.fibermadeConnection.findUnique).mockResolvedValue(
      mockConnection as never
    );
    process.env.FIBERMADE_API_URL = "https://api.fibermade.test";
  });

  it("calls importProduct when connection exists", async () => {
    const request = new Request("http://localhost/webhooks/products/create", {
      method: "POST",
    });

    const response = await action({
      request,
      params: {},
      context: {},
      unstable_pattern: "/",
    });

    expect(response).toBeInstanceOf(Response);
    expect(response.status).toBe(200);
    expect(ProductSyncService).toHaveBeenCalledWith(
      expect.anything(),
      42,
      "test.myshopify.com",
      expect.anything()
    );
    expect(mockImportProduct).toHaveBeenCalledWith(
      expect.objectContaining({
        id: "gid://shopify/Product/1234567890",
        title: "Test Product",
      })
    );
  });

  it("returns 200 when no FibermadeConnection exists", async () => {
    vi.mocked(db.fibermadeConnection.findUnique).mockResolvedValue(null);

    const request = new Request("http://localhost/webhooks/products/create", {
      method: "POST",
    });

    const response = await action({
      request,
      params: {},
      context: {},
      unstable_pattern: "/",
    });

    expect(response.status).toBe(200);
    expect(ProductSyncService).not.toHaveBeenCalled();
  });

  it("returns 200 even when importProduct throws", async () => {
    mockImportProduct.mockRejectedValue(new Error("Sync failed"));

    const request = new Request("http://localhost/webhooks/products/create", {
      method: "POST",
    });

    const response = await action({
      request,
      params: {},
      context: {},
      unstable_pattern: "/",
    });

    expect(response.status).toBe(200);
  });
});
