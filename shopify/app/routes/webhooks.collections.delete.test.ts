import { beforeEach, describe, expect, it, vi } from "vitest";
import { action } from "./webhooks.collections.delete";

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

const mockUpdateCollection = vi.fn().mockResolvedValue({});
const mockCreateIntegrationLog = vi.fn().mockResolvedValue({});

vi.mock("../services/fibermade-client.server", () => ({
  FibermadeClient: vi.fn().mockImplementation(function (this: {
    setToken: ReturnType<typeof vi.fn>;
    updateCollection: ReturnType<typeof vi.fn>;
    createIntegrationLog: ReturnType<typeof vi.fn>;
  }) {
    this.setToken = vi.fn();
    this.updateCollection = mockUpdateCollection;
    this.createIntegrationLog = mockCreateIntegrationLog;
    return this;
  }),
}));

import { authenticate } from "../shopify.server";
import db from "../db.server";
import { findFibermadeIdByShopifyGid } from "../services/sync/mapping.server";
import { IDENTIFIABLE_TYPES } from "../services/sync/constants";

describe("webhooks.collections.delete", () => {
  const mockConnection = {
    id: 1,
    shop: "test.myshopify.com",
    fibermadeApiToken: "token",
    fibermadeIntegrationId: 42,
    connectedAt: new Date(),
  };

  beforeEach(() => {
    vi.clearAllMocks();
    mockUpdateCollection.mockResolvedValue({});
    mockCreateIntegrationLog.mockResolvedValue({});
    vi.mocked(authenticate.webhook).mockResolvedValue({
      shop: "test.myshopify.com",
      payload: { id: 5678901234 },
      topic: "collections/delete",
    } as never);
    vi.mocked(db.fibermadeConnection.findUnique).mockResolvedValue(
      mockConnection as never
    );
    vi.mocked(findFibermadeIdByShopifyGid).mockResolvedValue({
      identifiableType: IDENTIFIABLE_TYPES.COLLECTION,
      identifiableId: 99,
    });
    process.env.FIBERMADE_API_URL = "https://api.fibermade.test";
  });

  it("updates Collection status to retired when mapping exists", async () => {
    const request = new Request("http://localhost/webhooks/collections/delete", {
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
      "shopify_collection",
      "gid://shopify/Collection/5678901234"
    );
    expect(mockUpdateCollection).toHaveBeenCalledWith(99, {
      status: "retired",
    });
    expect(mockCreateIntegrationLog).toHaveBeenCalledWith(
      42,
      expect.objectContaining({
        loggable_type: IDENTIFIABLE_TYPES.COLLECTION,
        loggable_id: 99,
        status: "success",
        message: expect.stringContaining("Retired Collection #99"),
        metadata: expect.objectContaining({
          shopify_collection_id: 5678901234,
          shopify_gid: "gid://shopify/Collection/5678901234",
        }),
      })
    );
  });

  it("returns 200 when collection not found in Fibermade", async () => {
    vi.mocked(findFibermadeIdByShopifyGid).mockResolvedValue(null);

    const request = new Request("http://localhost/webhooks/collections/delete", {
      method: "POST",
    });

    const response = await action({
      request,
      params: {},
      context: {},
      unstable_pattern: "/",
    });

    expect(response.status).toBe(200);
    expect(mockUpdateCollection).not.toHaveBeenCalled();
  });

  it("returns 200 when no FibermadeConnection exists", async () => {
    vi.mocked(db.fibermadeConnection.findUnique).mockResolvedValue(null);

    const request = new Request("http://localhost/webhooks/collections/delete", {
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
