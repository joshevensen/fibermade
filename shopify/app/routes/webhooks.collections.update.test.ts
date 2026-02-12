import { beforeEach, describe, expect, it, vi } from "vitest";
import { action } from "./webhooks.collections.update";

const mockUpdateCollection = vi.fn();

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
      updateCollection: ReturnType<typeof vi.fn>;
    }) {
      this.updateCollection = mockUpdateCollection;
      return this;
    }),
}));

import { authenticate } from "../shopify.server";
import db from "../db.server";
import { CollectionSyncService } from "../services/sync/collection-sync.server";

describe("webhooks.collections.update", () => {
  const mockPayload = {
    id: 5678901234,
    title: "Updated Collection",
    body_html: "<p>New description</p>",
    handle: "updated-collection",
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
    mockUpdateCollection.mockResolvedValue({
      collectionId: 5,
      colorwayCount: 10,
    });
    vi.mocked(authenticate.webhook).mockResolvedValue({
      shop: "test.myshopify.com",
      session: { shop: "test.myshopify.com" },
      admin: { graphql: vi.fn() },
      payload: mockPayload,
      topic: "collections/update",
    } as never);
    vi.mocked(db.fibermadeConnection.findUnique).mockResolvedValue(
      mockConnection as never
    );
    process.env.FIBERMADE_API_URL = "https://api.fibermade.test";
  });

  it("calls updateCollection when connection exists", async () => {
    const request = new Request("http://localhost/webhooks/collections/update", {
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
    expect(mockUpdateCollection).toHaveBeenCalledWith(
      expect.objectContaining({
        id: "gid://shopify/Collection/5678901234",
        title: "Updated Collection",
        descriptionHtml: "<p>New description</p>",
        handle: "updated-collection",
      })
    );
  });

  it("returns 200 when no FibermadeConnection exists", async () => {
    vi.mocked(db.fibermadeConnection.findUnique).mockResolvedValue(null);

    const request = new Request("http://localhost/webhooks/collections/update", {
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

  it("returns 200 even when updateCollection throws (GraphQL failure)", async () => {
    mockUpdateCollection.mockRejectedValue(new Error("GraphQL failed"));

    const request = new Request("http://localhost/webhooks/collections/update", {
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
