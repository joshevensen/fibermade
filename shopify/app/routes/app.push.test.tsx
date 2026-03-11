import { beforeEach, describe, expect, it, vi } from "vitest";
import { action, loader } from "./app.push";

vi.mock("../shopify.server", () => ({
  authenticate: {
    admin: vi.fn(),
  },
}));

vi.mock("../db.server", () => ({
  default: {
    fibermadeConnection: {
      findUnique: vi.fn(),
    },
  },
}));

const mockPushColorway = vi.fn();

vi.mock("../services/sync/product-push.server", () => ({
  ProductPushService: vi.fn().mockImplementation(function (this: { pushColorway: ReturnType<typeof vi.fn> }) {
    this.pushColorway = mockPushColorway;
    return this;
  }),
}));

vi.mock("../services/fibermade-client.server", () => ({
  FibermadeClient: vi.fn().mockImplementation(function (this: { setToken: ReturnType<typeof vi.fn> }) {
    this.setToken = vi.fn();
    return this;
  }),
}));

import { authenticate } from "../shopify.server";
import db from "../db.server";

describe("app.push", () => {
  const mockSession = { shop: "test.myshopify.com" };

  beforeEach(() => {
    vi.clearAllMocks();
    vi.mocked(authenticate.admin).mockResolvedValue({
      session: mockSession,
      admin: { graphql: vi.fn() },
    } as never);
    process.env.FIBERMADE_API_URL = "https://api.fibermade.test";
  });

  describe("loader", () => {
    it("redirects to /app when no connection exists", async () => {
      vi.mocked(db.fibermadeConnection.findUnique).mockResolvedValue(null);

      let thrown: unknown;
      try {
        await loader({
          request: new Request("http://localhost/app/push"),
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
      expect(response.headers.get("Location")).toContain("/app");
    });

    it("returns connected and shop when connection exists", async () => {
      const connection = {
        id: "conn-1",
        shop: "test.myshopify.com",
        fibermadeApiToken: "token",
        fibermadeIntegrationId: 1,
        connectedAt: new Date(),
      };
      vi.mocked(db.fibermadeConnection.findUnique).mockResolvedValue(connection as never);

      const result = await loader({
        request: new Request("http://localhost/app/push"),
        params: {},
        context: {},
        unstable_pattern: "/",
      });

      expect(result).toEqual({
        connected: true,
        shop: "test.myshopify.com",
      });
    });
  });

  describe("action", () => {
    const connection = {
      id: "conn-1",
      shop: "test.myshopify.com",
      fibermadeApiToken: "token",
      fibermadeIntegrationId: 1,
      connectedAt: new Date(),
    };

    it("returns error when method is not POST", async () => {
      vi.mocked(db.fibermadeConnection.findUnique).mockResolvedValue(connection as never);

      const result = await action({
        request: new Request("http://localhost/app/push", { method: "GET" }),
        params: {},
        context: {},
        unstable_pattern: "/",
      });

      expect(result).toEqual({ success: false, error: "Method not allowed" });
    });

    it("returns error when colorwayId is missing", async () => {
      vi.mocked(db.fibermadeConnection.findUnique).mockResolvedValue(connection as never);

      const result = await action({
        request: new Request("http://localhost/app/push", { method: "POST", body: new FormData() }),
        params: {},
        context: {},
        unstable_pattern: "/",
      });

      expect(result).toEqual({ success: false, error: "Colorway ID is required" });
    });

    it("returns error when colorwayId is invalid", async () => {
      vi.mocked(db.fibermadeConnection.findUnique).mockResolvedValue(connection as never);

      const formData = new FormData();
      formData.set("colorwayId", "abc");
      const result = await action({
        request: new Request("http://localhost/app/push", { method: "POST", body: formData }),
        params: {},
        context: {},
        unstable_pattern: "/",
      });

      expect(result).toEqual({ success: false, error: "Colorway ID must be a positive integer" });
    });

    it("returns success when push succeeds", async () => {
      vi.mocked(db.fibermadeConnection.findUnique).mockResolvedValue(connection as never);
      mockPushColorway.mockResolvedValue({
        shopifyProductGid: "gid://shopify/Product/999",
        colorwayId: 42,
      });

      const formData = new FormData();
      formData.set("colorwayId", "42");
      const result = await action({
        request: new Request("http://localhost/app/push", { method: "POST", body: formData }),
        params: {},
        context: {},
        unstable_pattern: "/",
      });

      expect(result).toEqual({
        success: true,
        shopifyProductGid: "gid://shopify/Product/999",
        adminUrl: "https://test.myshopify.com/admin/products/999",
        colorwayId: 42,
      });
      expect(mockPushColorway).toHaveBeenCalledWith(42);
    });

    it("redirects to /app when no connection", async () => {
      vi.mocked(db.fibermadeConnection.findUnique).mockResolvedValue(null);

      const formData = new FormData();
      formData.set("colorwayId", "42");
      let thrown: unknown;
      try {
        await action({
          request: new Request("http://localhost/app/push", { method: "POST", body: formData }),
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
    });
  });
});
