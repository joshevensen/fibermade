import { beforeEach, describe, expect, it, vi } from "vitest";
import { action } from "./webhooks.products.update";

const mockUpdateProduct = vi.fn();

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
  ProductSyncService: vi.fn().mockImplementation(function (this: { updateProduct: ReturnType<typeof vi.fn> }) {
    this.updateProduct = mockUpdateProduct;
    return this;
  }),
}));

import { authenticate } from "../shopify.server";
import db from "../db.server";
import { ProductSyncService } from "../services/sync/product-sync.server";

describe("webhooks.products.update", () => {
  const mockPayload = {
    id: 1234567890,
    title: "Updated Product",
    body_html: "<p>New desc</p>",
    status: "active",
    variants: [{ id: 9876543210, title: "Default Title", price: "39.99" }],
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
    mockUpdateProduct.mockResolvedValue(undefined);
    vi.mocked(authenticate.webhook).mockResolvedValue({
      shop: "test.myshopify.com",
      session: { shop: "test.myshopify.com" },
      admin: { graphql: vi.fn() },
      payload: mockPayload,
      topic: "products/update",
    } as never);
    vi.mocked(db.fibermadeConnection.findUnique).mockResolvedValue(
      mockConnection as never
    );
    process.env.FIBERMADE_API_URL = "https://api.fibermade.test";
  });

  it("calls updateProduct when connection exists", async () => {
    const request = new Request("http://localhost/webhooks/products/update", {
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
    expect(mockUpdateProduct).toHaveBeenCalledWith(
      expect.objectContaining({
        id: "gid://shopify/Product/1234567890",
        title: "Updated Product",
      })
    );
  });

  it("returns 200 when no FibermadeConnection exists", async () => {
    vi.mocked(db.fibermadeConnection.findUnique).mockResolvedValue(null);

    const request = new Request("http://localhost/webhooks/products/update", {
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

  it("returns 200 even when updateProduct throws", async () => {
    mockUpdateProduct.mockRejectedValue(new Error("Sync failed"));

    const request = new Request("http://localhost/webhooks/products/update", {
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
