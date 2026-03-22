import { beforeEach, describe, expect, it, vi } from "vitest";
import { action } from "./webhooks.app.uninstalled";

vi.mock("../shopify.server", () => ({
  authenticate: {
    webhook: vi.fn(),
  },
}));

vi.mock("../db.server", () => ({
  default: {
    fibermadeConnection: {
      findUnique: vi.fn(),
      delete: vi.fn(),
    },
    session: {
      deleteMany: vi.fn(),
    },
  },
}));

import { authenticate } from "../shopify.server";
import db from "../db.server";

describe("webhooks.app.uninstalled", () => {
  const mockConnection = {
    id: 1,
    shop: "test.myshopify.com",
    fibermadeApiToken: "token",
    fibermadeIntegrationId: 42,
    connectedAt: new Date(),
  };

  beforeEach(() => {
    vi.clearAllMocks();
    vi.unstubAllGlobals();
    vi.mocked(authenticate.webhook).mockResolvedValue({
      shop: "test.myshopify.com",
      session: { id: "sess-1" },
      topic: "app/uninstalled",
    } as never);
    vi.mocked(db.fibermadeConnection.findUnique).mockResolvedValue(mockConnection as never);
    vi.mocked(db.fibermadeConnection.delete).mockResolvedValue({} as never);
    vi.mocked(db.session.deleteMany).mockResolvedValue({ count: 1 } as never);
  });

  it("deactivates integration via PATCH, deletes connection and sessions", async () => {
    const mockFetch = vi.fn().mockResolvedValue(
      new Response(JSON.stringify({ data: { id: 42, active: false } }), {
        status: 200,
        headers: { "Content-Type": "application/json" },
      })
    );
    vi.stubGlobal("fetch", mockFetch);

    const originalEnv = process.env.FIBERMADE_API_URL;
    process.env.FIBERMADE_API_URL = "https://api.fibermade.test";

    const response = await action({
      request: new Request("http://localhost/webhooks/app/uninstalled", { method: "POST" }),
      params: {},
      context: {},
      unstable_pattern: "/",
    });

    process.env.FIBERMADE_API_URL = originalEnv;

    expect(response.status).toBe(200);
    expect(mockFetch).toHaveBeenCalledWith(
      "https://api.fibermade.test/api/v1/integrations/42",
      expect.objectContaining({ method: "PATCH" })
    );
    expect(db.fibermadeConnection.delete).toHaveBeenCalledWith({ where: { id: mockConnection.id } });
    expect(db.session.deleteMany).toHaveBeenCalledWith({ where: { shop: "test.myshopify.com" } });
  });

  it("returns 200 when no connection exists", async () => {
    vi.mocked(db.fibermadeConnection.findUnique).mockResolvedValue(null);

    const response = await action({
      request: new Request("http://localhost/webhooks/app/uninstalled", { method: "POST" }),
      params: {},
      context: {},
      unstable_pattern: "/",
    });

    expect(response.status).toBe(200);
    expect(db.fibermadeConnection.delete).not.toHaveBeenCalled();
  });

  it("still deletes connection when Fibermade API call throws", async () => {
    vi.stubGlobal("fetch", vi.fn().mockRejectedValue(new Error("network error")));

    const originalEnv = process.env.FIBERMADE_API_URL;
    process.env.FIBERMADE_API_URL = "https://api.fibermade.test";

    const response = await action({
      request: new Request("http://localhost/webhooks/app/uninstalled", { method: "POST" }),
      params: {},
      context: {},
      unstable_pattern: "/",
    });

    process.env.FIBERMADE_API_URL = originalEnv;

    expect(response.status).toBe(200);
    expect(db.fibermadeConnection.delete).toHaveBeenCalled();
  });
});
