import { beforeEach, describe, expect, it, vi } from "vitest";
import { action, loader } from "./app._index";

vi.mock("../shopify.server", () => ({
  authenticate: {
    admin: vi.fn(),
  },
}));

vi.mock("../db.server", () => ({
  default: {
    fibermadeConnection: {
      findUnique: vi.fn(),
      delete: vi.fn(),
    },
  },
}));

import { authenticate } from "../shopify.server";
import db from "../db.server";

describe("app._index", () => {
  const mockSession = { shop: "test.myshopify.com" };

  beforeEach(() => {
    vi.mocked(authenticate.admin).mockResolvedValue({ session: mockSession } as never);
  });

  describe("action", () => {
    const createRequest = (overrides: { method?: string; intent?: string } = {}) => {
      const method = overrides.method ?? "POST";
      if (method === "GET" || method === "HEAD") {
        return new Request("http://localhost", { method });
      }
      const formData = new FormData();
      if (overrides.intent) formData.set("intent", overrides.intent);
      return new Request("http://localhost", { method, body: formData });
    };

    it("returns success when no connection exists", async () => {
      vi.mocked(db.fibermadeConnection.findUnique).mockResolvedValue(null);

      const result = await action({
        request: createRequest({ intent: "disconnect" }),
        params: {},
        context: {},
      });

      expect(result).toEqual({ success: true });
    });

    it("returns success after deleting connection", async () => {
      const connection = {
        id: "conn-1",
        shop: "test.myshopify.com",
        fibermadeApiToken: "token",
        fibermadeIntegrationId: 1,
        connectedAt: new Date(),
      };
      vi.mocked(db.fibermadeConnection.findUnique).mockResolvedValue(connection as never);
      vi.mocked(db.fibermadeConnection.delete).mockResolvedValue({} as never);

      const result = await action({
        request: createRequest({ intent: "disconnect" }),
        params: {},
        context: {},
      });

      expect(result).toEqual({ success: true });
      expect(db.fibermadeConnection.delete).toHaveBeenCalledWith({
        where: { id: connection.id },
      });
    });

    it("returns error for non-POST method", async () => {
      const result = await action({
        request: createRequest({ method: "GET" }),
        params: {},
        context: {},
      });

      expect(result).toEqual({ success: false, error: "Method not allowed" });
    });

    it("returns error for invalid intent", async () => {
      const result = await action({
        request: createRequest({ intent: "other" }),
        params: {},
        context: {},
      });

      expect(result).toEqual({ success: false, error: "Invalid intent" });
    });
  });

  describe("loader", () => {
    it("returns connected: false when no connection exists", async () => {
      vi.mocked(db.fibermadeConnection.findUnique).mockResolvedValue(null);

      const result = await loader({
        request: new Request("http://localhost"),
        params: {},
        context: {},
      });

      expect(result).toEqual({ connected: false });
    });

    it("returns connected: true when connection exists and FIBERMADE_API_URL is empty", async () => {
      const connection = {
        id: "conn-1",
        shop: "test.myshopify.com",
        fibermadeApiToken: "token",
        fibermadeIntegrationId: 1,
        connectedAt: new Date("2024-01-15T10:00:00Z"),
      };
      vi.mocked(db.fibermadeConnection.findUnique).mockResolvedValue(connection as never);

      const originalEnv = process.env.FIBERMADE_API_URL;
      process.env.FIBERMADE_API_URL = "";

      const result = await loader({
        request: new Request("http://localhost"),
        params: {},
        context: {},
      });

      process.env.FIBERMADE_API_URL = originalEnv;

      expect(result).toEqual({
        connected: true,
        shop: "test.myshopify.com",
        connectedAt: "2024-01-15T10:00:00.000Z",
      });
    });

    it("returns connectionError: token_invalid when FibermadeClient throws FibermadeAuthError", async () => {
      const connection = {
        id: "conn-1",
        shop: "test.myshopify.com",
        fibermadeApiToken: "token",
        fibermadeIntegrationId: 1,
        connectedAt: new Date("2024-01-15T10:00:00Z"),
      };
      vi.mocked(db.fibermadeConnection.findUnique).mockResolvedValue(connection as never);

      const fetchMock = vi.fn().mockResolvedValue(
        new Response(JSON.stringify({ message: "Unauthenticated." }), {
          status: 401,
          headers: { "Content-Type": "application/json" },
        })
      );
      vi.stubGlobal("fetch", fetchMock);

      const originalEnv = process.env.FIBERMADE_API_URL;
      process.env.FIBERMADE_API_URL = "https://api.example.com";

      const result = await loader({
        request: new Request("http://localhost"),
        params: {},
        context: {},
      });

      process.env.FIBERMADE_API_URL = originalEnv;
      vi.unstubAllGlobals();

      expect(result).toEqual({
        connected: false,
        connectionError: "token_invalid",
        shop: "test.myshopify.com",
        connectedAt: "2024-01-15T10:00:00.000Z",
      });
    });
  });
});
