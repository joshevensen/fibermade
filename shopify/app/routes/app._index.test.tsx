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

vi.mock("../services/sync/mapping.server", () => ({
  mappingExists: vi.fn(),
}));

vi.mock("../services/sync/product-sync.server", () => ({
  ProductSyncService: vi.fn().mockImplementation(function (this: unknown) {
    return {
      importProduct: vi.fn().mockResolvedValue({
        skipped: false,
        colorwayId: 1,
        bases: [],
        inventoryRecords: [],
      }),
      updateProduct: vi.fn().mockResolvedValue(undefined),
    };
  }),
}));

import { authenticate } from "../shopify.server";
import db from "../db.server";
import { BulkImportService } from "../services/sync/bulk-import.server";
import { mappingExists } from "../services/sync/mapping.server";
import { ProductSyncService } from "../services/sync/product-sync.server";

describe("app._index", () => {
  const mockSession = { shop: "test.myshopify.com" };

  beforeEach(() => {
    vi.mocked(authenticate.admin).mockResolvedValue({ session: mockSession } as never);
  });

  describe("action", () => {
    const createRequest = (
      overrides: { method?: string; intent?: string; productId?: string } = {}
    ) => {
      const method = overrides.method ?? "POST";
      if (method === "GET" || method === "HEAD") {
        return new Request("http://localhost", { method });
      }
      const formData = new FormData();
      if (overrides.intent) formData.set("intent", overrides.intent);
      if (overrides.productId != null) formData.set("productId", overrides.productId);
      return new Request("http://localhost", { method, body: formData });
    };

    const connectionWithIntegration = {
      id: "conn-1",
      shop: "test.myshopify.com",
      fibermadeApiToken: "token",
      fibermadeIntegrationId: 1,
      connectedAt: new Date("2024-01-15T10:00:00Z"),
      initialImportStatus: "complete" as const,
      initialImportProgress: JSON.stringify({ total: 10, imported: 10, failed: 0 }),
    };

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
        unstable_pattern: "/",
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

    describe("resync-all", () => {
      it("returns error when no connection exists", async () => {
        vi.mocked(db.fibermadeConnection.findUnique).mockResolvedValue(null);
        vi.mocked(authenticate.admin).mockResolvedValue({
          session: mockSession,
          admin: { graphql: vi.fn() },
        } as never);

        const result = await action({
          request: createRequest({ intent: "resync-all" }),
          params: {},
          context: {},
          unstable_pattern: "/",
        });

        expect(result).toEqual({ success: false, error: "Not connected to Fibermade." });
      });

      it("returns error when initialImportStatus is in_progress", async () => {
        vi.mocked(db.fibermadeConnection.findUnique).mockResolvedValue({
          ...connectionWithIntegration,
          initialImportStatus: "in_progress",
        } as never);
        vi.mocked(authenticate.admin).mockResolvedValue({
          session: mockSession,
          admin: { graphql: vi.fn() },
        } as never);
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

        const result = await action({
          request: createRequest({ intent: "resync-all" }),
          params: {},
          context: {},
          unstable_pattern: "/",
        });

        process.env.FIBERMADE_API_URL = originalEnv;
        vi.unstubAllGlobals();

        expect(result).toEqual({
          success: false,
          error: "Import is already in progress",
        });
      });

      it("returns success and progress when runImport succeeds", async () => {
        vi.mocked(db.fibermadeConnection.findUnique).mockResolvedValue(
          connectionWithIntegration as never
        );
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

        const result = await action({
          request: createRequest({ intent: "resync-all" }),
          params: {},
          context: {},
          unstable_pattern: "/",
        });

        process.env.FIBERMADE_API_URL = originalEnv;
        vi.unstubAllGlobals();

        expect(result).toEqual({
          success: true,
          progress: { total: 5, imported: 5, failed: 0 },
        });
        expect(BulkImportService).toHaveBeenCalled();
      });

      it("returns error when runImport throws", async () => {
        vi.mocked(db.fibermadeConnection.findUnique).mockResolvedValue(
          connectionWithIntegration as never
        );
        vi.mocked(authenticate.admin).mockResolvedValue({
          session: mockSession,
          admin: {
            graphql: vi.fn().mockResolvedValue({
              ok: true,
              json: () => Promise.resolve({ data: {} }),
            }),
          },
        } as never);
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
          request: createRequest({ intent: "resync-all" }),
          params: {},
          context: {},
          unstable_pattern: "/",
        });

        process.env.FIBERMADE_API_URL = originalEnv;
        vi.unstubAllGlobals();

        expect(result).toEqual({ success: false, error: "Import failed" });
      });
    });

    describe("sync-product", () => {
      const productGraphqlResponse = {
        data: {
          product: {
            id: "gid://shopify/Product/123",
            title: "Test Product",
            descriptionHtml: null,
            status: "ACTIVE",
            handle: "test-product",
            featuredImage: null,
            variants: { edges: [{ node: { id: "gid://shopify/ProductVariant/456", title: "Default", sku: null, price: "10.00", weight: null, weightUnit: null } }] },
            images: { edges: [] },
          },
        },
      };

      it("returns error when no connection exists", async () => {
        vi.mocked(db.fibermadeConnection.findUnique).mockResolvedValue(null);
        vi.mocked(authenticate.admin).mockResolvedValue({
          session: mockSession,
          admin: { graphql: vi.fn() },
        } as never);

        const result = await action({
          request: createRequest({ intent: "sync-product", productId: "123" }),
          params: {},
          context: {},
          unstable_pattern: "/",
        });

        expect(result).toEqual({ success: false, error: "Not connected to Fibermade." });
      });

      it("returns error for invalid productId", async () => {
        vi.mocked(db.fibermadeConnection.findUnique).mockResolvedValue(
          connectionWithIntegration as never
        );
        vi.mocked(authenticate.admin).mockResolvedValue({
          session: mockSession,
          admin: { graphql: vi.fn() },
        } as never);
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

        const result = await action({
          request: createRequest({ intent: "sync-product", productId: "" }),
          params: {},
          context: {},
          unstable_pattern: "/",
        });

        process.env.FIBERMADE_API_URL = originalEnv;
        vi.unstubAllGlobals();

        expect(result).toEqual({ success: false, error: "Invalid product ID or URL" });
      });

      it("returns success with productTitle when mapping does not exist (importProduct)", async () => {
        vi.mocked(db.fibermadeConnection.findUnique).mockResolvedValue(
          connectionWithIntegration as never
        );
        vi.mocked(mappingExists).mockResolvedValue(false);
        vi.mocked(authenticate.admin).mockResolvedValue({
          session: mockSession,
          admin: {
            graphql: vi.fn().mockResolvedValue({
              ok: true,
              json: () => Promise.resolve(productGraphqlResponse),
            }),
          },
        } as never);
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

        const result = await action({
          request: createRequest({ intent: "sync-product", productId: "gid://shopify/Product/123" }),
          params: {},
          context: {},
          unstable_pattern: "/",
        });

        process.env.FIBERMADE_API_URL = originalEnv;
        vi.unstubAllGlobals();

        expect(result).toEqual({ success: true, productTitle: "Test Product" });
        expect(ProductSyncService).toHaveBeenCalled();
        const instance = vi.mocked(ProductSyncService).mock.results[0]?.value as {
          importProduct: ReturnType<typeof vi.fn>;
          updateProduct: ReturnType<typeof vi.fn>;
        };
        expect(instance.importProduct).toHaveBeenCalled();
        expect(instance.updateProduct).not.toHaveBeenCalled();
      });

      it("returns success when mapping exists (updateProduct)", async () => {
        vi.mocked(db.fibermadeConnection.findUnique).mockResolvedValue(
          connectionWithIntegration as never
        );
        vi.mocked(mappingExists).mockResolvedValue(true);
        vi.mocked(authenticate.admin).mockResolvedValue({
          session: mockSession,
          admin: {
            graphql: vi.fn().mockResolvedValue({
              ok: true,
              json: () => Promise.resolve(productGraphqlResponse),
            }),
          },
        } as never);
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

        const result = await action({
          request: createRequest({ intent: "sync-product", productId: "123" }),
          params: {},
          context: {},
          unstable_pattern: "/",
        });

        process.env.FIBERMADE_API_URL = originalEnv;
        vi.unstubAllGlobals();

        expect(result).toEqual({ success: true, productTitle: "Test Product" });
        const results = vi.mocked(ProductSyncService).mock.results;
        const instance = results[results.length - 1]?.value as {
          importProduct: ReturnType<typeof vi.fn>;
          updateProduct: ReturnType<typeof vi.fn>;
        };
        expect(instance.updateProduct).toHaveBeenCalled();
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
        unstable_pattern: "/",
      });

      process.env.FIBERMADE_API_URL = originalEnv;

      expect(result).toEqual({
        connected: true,
        shop: "test.myshopify.com",
        connectedAt: "2024-01-15T10:00:00.000Z",
        initialImportStatus: undefined,
        initialImportProgress: { total: 0, imported: 0, failed: 0 },
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
        unstable_pattern: "/",
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

    it("returns integrationActive and integrationUpdatedAt when getIntegration succeeds", async () => {
      const connection = {
        id: "conn-1",
        shop: "test.myshopify.com",
        fibermadeApiToken: "token",
        fibermadeIntegrationId: 1,
        connectedAt: new Date("2024-01-15T10:00:00Z"),
      };
      vi.mocked(db.fibermadeConnection.findUnique).mockResolvedValue(connection as never);

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
      const fetchMock = vi.fn().mockResolvedValue(
        new Response(JSON.stringify(integrationResponse), {
          status: 200,
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

      expect(result).toEqual({
        connected: true,
        shop: "test.myshopify.com",
        connectedAt: "2024-01-15T10:00:00.000Z",
        initialImportStatus: undefined,
        initialImportProgress: { total: 0, imported: 0, failed: 0 },
        integrationActive: true,
        integrationUpdatedAt: "2024-02-01T12:00:00.000000Z",
      });
    });

    it("returns connection payload without integration fields when getIntegration fails with 500", async () => {
      const connection = {
        id: "conn-1",
        shop: "test.myshopify.com",
        fibermadeApiToken: "token",
        fibermadeIntegrationId: 1,
        connectedAt: new Date("2024-01-15T10:00:00Z"),
      };
      vi.mocked(db.fibermadeConnection.findUnique).mockResolvedValue(connection as never);

      const fetchMock = vi.fn().mockResolvedValue(
        new Response(JSON.stringify({ message: "Server Error" }), {
          status: 500,
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

      expect(result.connected).toBe(true);
      expect(result.shop).toBe("test.myshopify.com");
      expect(result.connectedAt).toBe("2024-01-15T10:00:00.000Z");
      expect(result.integrationActive).toBeUndefined();
      expect(result.integrationUpdatedAt).toBeUndefined();
    });
  });
});
