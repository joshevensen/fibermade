import { beforeEach, describe, expect, it, vi } from "vitest";
import { action, loader } from "./app.connect";

vi.mock("../shopify.server", () => ({
  authenticate: {
    admin: vi.fn(),
  },
}));

vi.mock("../db.server", () => ({
  default: {
    fibermadeConnection: {
      findUnique: vi.fn(),
      create: vi.fn(),
    },
  },
}));

vi.mock("../services/fibermade-client.server", () => ({
  FibermadeClient: vi.fn().mockImplementation(function (this: {
    setToken: ReturnType<typeof vi.fn>;
    createIntegration: ReturnType<typeof vi.fn>;
    getIntegration: ReturnType<typeof vi.fn>;
  }) {
    this.setToken = vi.fn();
    this.createIntegration = vi.fn().mockResolvedValue({ id: 1, type: "shopify", active: true });
    this.getIntegration = vi.fn().mockResolvedValue({ id: 1, active: true });
    return this;
  }),
}));

import { authenticate } from "../shopify.server";
import db from "../db.server";

describe("app.connect", () => {
  const mockSession = { shop: "test.myshopify.com", accessToken: "shopify-token" };

  beforeEach(() => {
    vi.clearAllMocks();
    vi.mocked(authenticate.admin).mockResolvedValue({
      session: mockSession,
    } as never);
    process.env.FIBERMADE_API_URL = "https://api.fibermade.test";
  });

  describe("loader", () => {
    it("redirects to /app when connection already exists", async () => {
      const connection = {
        id: "conn-1",
        shop: "test.myshopify.com",
        fibermadeApiToken: "token",
        fibermadeIntegrationId: 1,
        connectedAt: new Date(),
      };
      vi.mocked(db.fibermadeConnection.findUnique).mockResolvedValue(connection as never);

      let thrown: unknown;
      try {
        await loader({
          request: new Request("http://localhost/app/connect"),
          params: {},
          context: {},
          unstable_pattern: "/",
        });
      } catch (e) {
        thrown = e;
      }

      expect(thrown).toBeInstanceOf(Response);
      const response = thrown as Response;
      expect(response.status).toBe(302);
      expect(response.headers.get("Location")).toBe("/app");
    });

    it("returns null when no connection exists", async () => {
      vi.mocked(db.fibermadeConnection.findUnique).mockResolvedValue(null);

      const result = await loader({
        request: new Request("http://localhost/app/connect"),
        params: {},
        context: {},
        unstable_pattern: "/",
      });

      expect(result).toBeNull();
    });
  });

  describe("action", () => {
    it("returns error when method is not POST", async () => {
      vi.mocked(db.fibermadeConnection.findUnique).mockResolvedValue(null);

      const result = await action({
        request: new Request("http://localhost/app/connect", { method: "GET" }),
        params: {},
        context: {},
        unstable_pattern: "/",
      });

      expect(result).toEqual({ success: false, error: "Method not allowed" });
    });

    it("returns error when apiToken is missing", async () => {
      vi.mocked(db.fibermadeConnection.findUnique).mockResolvedValue(null);
      vi.mocked(authenticate.admin).mockResolvedValue({
        session: { ...mockSession, accessToken: "shop-token" },
      } as never);

      const result = await action({
        request: new Request("http://localhost/app/connect", { method: "POST", body: new FormData() }),
        params: {},
        context: {},
        unstable_pattern: "/",
      });

      expect(result).toEqual({
        success: false,
        error: "API token is required",
        field: "apiToken",
      });
    });
  });
});
