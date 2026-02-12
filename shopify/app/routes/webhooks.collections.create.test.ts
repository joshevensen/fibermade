import { beforeEach, describe, expect, it, vi } from "vitest";
import { action } from "./webhooks.collections.create";

const mockImportCollection = vi.fn();

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

vi.mock("../services/sync/collection-sync.server", () => ({
  CollectionSyncService: vi
    .fn()
    .mockImplementation(function (this: {
      importCollection: ReturnType<typeof vi.fn>;
    }) {
      this.importCollection = mockImportCollection;
      return this;
    }),
}));

import { authenticate } from "../shopify.server";
import db from "../db.server";
import { CollectionSyncService } from "../services/sync/collection-sync.server";

describe("webhooks.collections.create", () => {
  const mockPayload = {
    id: 5678901234,
    title: "Summer Collection",
    body_html: "<p>Our summer yarn colors</p>",
    handle: "summer-collection",
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
    mockImportCollection.mockResolvedValue({
      collectionId: 1,
      colorwayCount: 5,
    });
    vi.mocked(authenticate.webhook).mockResolvedValue({
      shop: "test.myshopify.com",
      session: { shop: "test.myshopify.com" },
      admin: { graphql: vi.fn() },
      payload: mockPayload,
      topic: "collections/create",
    } as never);
    vi.mocked(db.fibermadeConnection.findUnique).mockResolvedValue(
      mockConnection as never
    );
    process.env.FIBERMADE_API_URL = "https://api.fibermade.test";
  });

  it("calls importCollection when connection exists", async () => {
    const request = new Request("http://localhost/webhooks/collections/create", {
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
    expect(CollectionSyncService).toHaveBeenCalledWith(
      expect.anything(),
      42,
      "test.myshopify.com",
      expect.anything()
    );
    expect(mockImportCollection).toHaveBeenCalledWith(
      expect.objectContaining({
        id: "gid://shopify/Collection/5678901234",
        title: "Summer Collection",
        descriptionHtml: "<p>Our summer yarn colors</p>",
        handle: "summer-collection",
      })
    );
  });

  it("returns 200 when no FibermadeConnection exists", async () => {
    vi.mocked(db.fibermadeConnection.findUnique).mockResolvedValue(null);

    const request = new Request("http://localhost/webhooks/collections/create", {
      method: "POST",
    });

    const response = await action({
      request,
      params: {},
      context: {},
      unstable_pattern: "/",
    });

    expect(response.status).toBe(200);
    expect(CollectionSyncService).not.toHaveBeenCalled();
  });

  it("returns 200 even when importCollection throws", async () => {
    mockImportCollection.mockRejectedValue(new Error("Sync failed"));

    const request = new Request("http://localhost/webhooks/collections/create", {
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
