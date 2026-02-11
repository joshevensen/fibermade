import { beforeEach, describe, expect, it, vi } from "vitest";
import { action } from "./webhooks.products.delete";

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

vi.mock("../services/sync/mapping.server", () => ({
  findFibermadeIdByShopifyGid: vi.fn(),
}));

const mockUpdateColorway = vi.fn().mockResolvedValue({});
const mockCreateIntegrationLog = vi.fn().mockResolvedValue({});

vi.mock("../services/fibermade-client.server", () => ({
  FibermadeClient: vi.fn().mockImplementation(function (this: {
    setToken: ReturnType<typeof vi.fn>;
    updateColorway: ReturnType<typeof vi.fn>;
    createIntegrationLog: ReturnType<typeof vi.fn>;
  }) {
    this.setToken = vi.fn();
    this.updateColorway = mockUpdateColorway;
    this.createIntegrationLog = mockCreateIntegrationLog;
    return this;
  }),
}));

import { authenticate } from "../shopify.server";
import db from "../db.server";
import { findFibermadeIdByShopifyGid } from "../services/sync/mapping.server";
import { IDENTIFIABLE_TYPES } from "../services/sync/constants";

describe("webhooks.products.delete", () => {
  const mockConnection = {
    id: 1,
    shop: "test.myshopify.com",
    fibermadeApiToken: "token",
    fibermadeIntegrationId: 42,
    connectedAt: new Date(),
  };

  beforeEach(() => {
    vi.clearAllMocks();
    mockUpdateColorway.mockResolvedValue({});
    mockCreateIntegrationLog.mockResolvedValue({});
    vi.mocked(authenticate.webhook).mockResolvedValue({
      shop: "test.myshopify.com",
      payload: { id: 1234567890 },
      topic: "products/delete",
    } as never);
    vi.mocked(db.fibermadeConnection.findUnique).mockResolvedValue(
      mockConnection as never
    );
    vi.mocked(findFibermadeIdByShopifyGid).mockResolvedValue({
      identifiableType: IDENTIFIABLE_TYPES.COLORWAY,
      identifiableId: 99,
    });
    process.env.FIBERMADE_API_URL = "https://api.fibermade.test";
  });

  it("updates Colorway status to retired when mapping exists", async () => {
    const request = new Request("http://localhost/webhooks/products/delete", {
      method: "POST",
    });

    const response = await action({
      request,
      params: {},
      context: {},
      unstable_pattern: "/",
    });

    expect(response.status).toBe(200);
    expect(findFibermadeIdByShopifyGid).toHaveBeenCalledWith(
      expect.anything(),
      42,
      "shopify_product",
      "gid://shopify/Product/1234567890"
    );
    expect(mockUpdateColorway).toHaveBeenCalledWith(99, {
      status: "retired",
    });
  });

  it("returns 200 when product not found in Fibermade", async () => {
    vi.mocked(findFibermadeIdByShopifyGid).mockResolvedValue(null);

    const request = new Request("http://localhost/webhooks/products/delete", {
      method: "POST",
    });

    const response = await action({
      request,
      params: {},
      context: {},
      unstable_pattern: "/",
    });

    expect(response.status).toBe(200);
    expect(mockUpdateColorway).not.toHaveBeenCalled();
  });

  it("returns 200 when no FibermadeConnection exists", async () => {
    vi.mocked(db.fibermadeConnection.findUnique).mockResolvedValue(null);

    const request = new Request("http://localhost/webhooks/products/delete", {
      method: "POST",
    });

    const response = await action({
      request,
      params: {},
      context: {},
      unstable_pattern: "/",
    });

    expect(response.status).toBe(200);
    expect(findFibermadeIdByShopifyGid).not.toHaveBeenCalled();
  });
});
