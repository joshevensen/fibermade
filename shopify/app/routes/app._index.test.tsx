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
      update: vi.fn(),
    },
  },
}));

vi.mock("../services/sync/bulk-import.server", () => ({
  BulkImportService: vi.fn().mockImplementation(function (this: unknown) {
    return {
      runImport: vi.fn().mockResolvedValue({ total: 5, imported: 5, failed: 0 }),
    };
  }),
}));

import { authenticate } from "../shopify.server";
import db from "../db.server";
import { BulkImportService } from "../services/sync/bulk-import.server";

describe("app._index", () => {
  const mockSession = { shop: "test.myshopify.com" };

  beforeEach(() => {
    vi.mocked(authenticate.admin).mockResolvedValue({ session: mockSession } as never);
  });

  describe("action", () => {
    const createRequest = (
      overrides: { method?: string; intent?: string } = {}
    ) => {
      const method = overrides.method ?? "POST";
      if (method === "GET" || method === "HEAD") {
        return new Request("http://localhost", { method });
      }
      const formData = new FormData();
      if (overrides.intent) formData.set("intent", overrides.intent);
      return new Request("http://localhost", { method, body: formData });
    };

    const connection = {
      id: "conn-1",
      shop: "test.myshopify.com",
      fibermadeApiToken: "token",
      fibermadeIntegrationId: 1,
      connectedAt: new Date("2024-01-15T10:00:00Z"),
      initialImportStatus: "complete",
      initialImportProgress: null,
    };

    it("returns error for non-POST method", async () => {
      const result = await action({
        request: createRequest({ method: "GET" }),
        params: {},
        context: {},
        unstable_pattern: "/",
      });

      expect(result).toEqual({ success: false, error: "Method not allowed" });
    });

    it("returns error for invalid intent", async () => {
      const result = await action({
        request: createRequest({ intent: "other" }),
        params: {},
        context: {},
        unstable_pattern: "/",
      });

      expect(result).toEqual({ success: false, error: "Invalid intent" });
    });

    describe("disconnect", () => {
      it("returns success when no connection exists", async () => {
        vi.mocked(db.fibermadeConnection.findUnique).mockResolvedValue(null);

        const result = await action({
          request: createRequest({ intent: "disconnect" }),
          params: {},
          context: {},
          unstable_pattern: "/",
        });

        expect(result).toEqual({ success: true });
      });

      it("deletes connection and returns success", async () => {
        vi.mocked(db.fibermadeConnection.findUnique).mockResolvedValue(connection as never);
        vi.mocked(db.fibermadeConnection.delete).mockResolvedValue({} as never);

        const result = await action({
          request: createRequest({ intent: "disconnect" }),
          params: {},
          context: {},
          unstable_pattern: "/",
        });

        expect(result).toEqual({ success: true });
        expect(db.fibermadeConnection.delete).toHaveBeenCalledWith({
          where: { id: connection.id },
        });
      });
    });

    describe("sync-all", () => {
      it("returns error when no connection exists", async () => {
        vi.mocked(db.fibermadeConnection.findUnique).mockResolvedValue(null);
        vi.mocked(authenticate.admin).mockResolvedValue({
          session: mockSession,
          admin: { graphql: vi.fn() },
        } as never);

        const result = await action({
          request: createRequest({ intent: "sync-all" }),
          params: {},
          context: {},
          unstable_pattern: "/",
        });

        expect(result).toEqual({ success: false, error: "Not connected to Fibermade." });
      });

      it("returns success and progress when runImport succeeds", async () => {
        vi.mocked(db.fibermadeConnection.findUnique).mockResolvedValue(connection as never);
        vi.mocked(db.fibermadeConnection.update).mockResolvedValue({} as never);
        vi.mocked(authenticate.admin).mockResolvedValue({
          session: mockSession,
          admin: {
            graphql: vi.fn().mockResolvedValue({
              ok: true,
              json: () => Promise.resolve({ data: {} }),
            }),
          },
        } as never);

        const originalEnv = process.env.FIBERMADE_API_URL;
        process.env.FIBERMADE_API_URL = "https://api.example.com";

        const result = await action({
          request: createRequest({ intent: "sync-all" }),
          params: {},
          context: {},
          unstable_pattern: "/",
        });

        process.env.FIBERMADE_API_URL = originalEnv;

        expect(result).toEqual({
          success: true,
          progress: { total: 5, imported: 5, failed: 0 },
        });
        expect(BulkImportService).toHaveBeenCalled();
      });

      it("returns error when runImport throws", async () => {
        vi.mocked(db.fibermadeConnection.findUnique).mockResolvedValue(connection as never);
        vi.mocked(authenticate.admin).mockResolvedValue({
          session: mockSession,
          admin: {
            graphql: vi.fn().mockResolvedValue({
              ok: true,
              json: () => Promise.resolve({ data: {} }),
            }),
          },
        } as never);
        vi.mocked(BulkImportService).mockImplementationOnce(
          function (this: unknown) {
            return {
              runImport: vi.fn().mockRejectedValue(new Error("Import failed")),
            };
          } as never
        );

        const originalEnv = process.env.FIBERMADE_API_URL;
        process.env.FIBERMADE_API_URL = "https://api.example.com";

        const result = await action({
          request: createRequest({ intent: "sync-all" }),
          params: {},
          context: {},
          unstable_pattern: "/",
        });

        process.env.FIBERMADE_API_URL = originalEnv;

        expect(result).toEqual({ success: false, error: "Import failed" });
      });
    });
  });

  describe("loader", () => {
    it("returns connected: false when no connection exists", async () => {
      vi.mocked(db.fibermadeConnection.findUnique).mockResolvedValue(null);

      const result = await loader({
        request: new Request("http://localhost"),
        params: {},
        context: {},
        unstable_pattern: "/",
      });

      expect(result.connected).toBe(false);
    });

    it("returns connected: true when connection exists and FIBERMADE_API_URL is empty", async () => {
      const conn = {
        id: "conn-1",
        shop: "test.myshopify.com",
        fibermadeApiToken: "token",
        fibermadeIntegrationId: 1,
        connectedAt: new Date("2024-01-15T10:00:00Z"),
        initialImportStatus: "complete",
        initialImportProgress: null,
      };
      vi.mocked(db.fibermadeConnection.findUnique).mockResolvedValue(conn as never);

      const originalEnv = process.env.FIBERMADE_API_URL;
      process.env.FIBERMADE_API_URL = "";

      const result = await loader({
        request: new Request("http://localhost"),
        params: {},
        context: {},
        unstable_pattern: "/",
      });

      process.env.FIBERMADE_API_URL = originalEnv;

      expect(result.connected).toBe(true);
      expect(result.shop).toBe("test.myshopify.com");
      expect(result.connectedAt).toBe("2024-01-15T10:00:00.000Z");
    });

    it("returns connectionError: token_invalid when FibermadeClient throws FibermadeAuthError", async () => {
      const conn = {
        id: "conn-1",
        shop: "test.myshopify.com",
        fibermadeApiToken: "token",
        fibermadeIntegrationId: 1,
        connectedAt: new Date("2024-01-15T10:00:00Z"),
        initialImportStatus: "complete",
        initialImportProgress: null,
      };
      vi.mocked(db.fibermadeConnection.findUnique).mockResolvedValue(conn as never);

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
        unstable_pattern: "/",
      });

      process.env.FIBERMADE_API_URL = originalEnv;
      vi.unstubAllGlobals();

      expect(result).toMatchObject({
        connected: false,
        connectionError: "token_invalid",
        shop: "test.myshopify.com",
        connectedAt: "2024-01-15T10:00:00.000Z",
      });
    });

    it("returns connected: true when getIntegration succeeds", async () => {
      const conn = {
        id: "conn-1",
        shop: "test.myshopify.com",
        fibermadeApiToken: "token",
        fibermadeIntegrationId: 1,
        connectedAt: new Date("2024-01-15T10:00:00Z"),
        initialImportStatus: "complete",
        initialImportProgress: null,
      };
      vi.mocked(db.fibermadeConnection.findUnique).mockResolvedValue(conn as never);

      const integrationResponse = {
        data: {
          id: 1,
          type: "shopify",
          settings: null,
          active: true,
          created_at: "2024-01-10T08:00:00Z",
          updated_at: "2024-02-01T12:00:00.000000Z",
        },
      };
      vi.stubGlobal(
        "fetch",
        vi.fn().mockResolvedValue(
          new Response(JSON.stringify(integrationResponse), {
            status: 200,
            headers: { "Content-Type": "application/json" },
          })
        )
      );

      const originalEnv = process.env.FIBERMADE_API_URL;
      process.env.FIBERMADE_API_URL = "https://api.example.com";

      const result = await loader({
        request: new Request("http://localhost"),
        params: {},
        context: {},
        unstable_pattern: "/",
      });

      process.env.FIBERMADE_API_URL = originalEnv;
      vi.unstubAllGlobals();

      expect(result.connected).toBe(true);
      expect(result.shop).toBe("test.myshopify.com");
      expect(result.connectedAt).toBe("2024-01-15T10:00:00.000Z");
    });

    it("returns connected: true when getIntegration fails with 500", async () => {
      const conn = {
        id: "conn-1",
        shop: "test.myshopify.com",
        fibermadeApiToken: "token",
        fibermadeIntegrationId: 1,
        connectedAt: new Date("2024-01-15T10:00:00Z"),
        initialImportStatus: "complete",
        initialImportProgress: null,
      };
      vi.mocked(db.fibermadeConnection.findUnique).mockResolvedValue(conn as never);

      vi.stubGlobal(
        "fetch",
        vi.fn().mockResolvedValue(
          new Response(JSON.stringify({ message: "Server Error" }), {
            status: 500,
            headers: { "Content-Type": "application/json" },
          })
        )
      );

      const originalEnv = process.env.FIBERMADE_API_URL;
      process.env.FIBERMADE_API_URL = "https://api.example.com";

      const result = await loader({
        request: new Request("http://localhost"),
        params: {},
        context: {},
        unstable_pattern: "/",
      });

      process.env.FIBERMADE_API_URL = originalEnv;
      vi.unstubAllGlobals();

      expect(result.connected).toBe(true);
      expect(result.shop).toBe("test.myshopify.com");
    });
  });
});
