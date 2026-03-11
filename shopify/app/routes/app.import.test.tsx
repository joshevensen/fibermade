import { beforeEach, describe, expect, it, vi } from "vitest";
import { action, loader } from "./app.import";

vi.mock("../shopify.server", () => ({
  authenticate: {
    admin: vi.fn(),
  },
}));

vi.mock("../db.server", () => ({
  default: {
    fibermadeConnection: {
      findUnique: vi.fn(),
      update: vi.fn(),
    },
  },
}));

const mockRunImport = vi.fn().mockResolvedValue(undefined);

vi.mock("../services/sync/bulk-import.server", () => ({
  BulkImportService: vi.fn().mockImplementation(function (this: { runImport: ReturnType<typeof vi.fn> }) {
    this.runImport = mockRunImport;
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

describe("app.import", () => {
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
          request: new Request("http://localhost/app/import"),
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

    it("returns initialImportStatus and initialImportProgress when connected", async () => {
      const connection = {
        id: "conn-1",
        shop: "test.myshopify.com",
        fibermadeApiToken: "token",
        fibermadeIntegrationId: 1,
        connectedAt: new Date("2024-01-15T10:00:00Z"),
        initialImportStatus: "complete" as const,
        initialImportProgress: JSON.stringify({
          total: 10,
          imported: 10,
          failed: 0,
          errors: [],
        }),
      };
      vi.mocked(db.fibermadeConnection.findUnique).mockResolvedValue(connection as never);

      const result = await loader({
        request: new Request("http://localhost/app/import"),
        params: {},
        context: {},
        unstable_pattern: "/",
      });

      expect(result).toEqual({
        initialImportStatus: "complete",
        initialImportProgress: {
          total: 10,
          imported: 10,
          failed: 0,
          errors: [],
        },
      });
    });
  });

  describe("action", () => {
    it("redirects to /app when no connection exists", async () => {
      vi.mocked(db.fibermadeConnection.findUnique).mockResolvedValue(null);

      let thrown: unknown;
      try {
        await action({
          request: new Request("http://localhost/app/import", { method: "POST" }),
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

    it("returns error when method is not POST", async () => {
      const connection = {
        id: "conn-1",
        shop: "test.myshopify.com",
        fibermadeApiToken: "token",
        fibermadeIntegrationId: 1,
        connectedAt: new Date(),
        initialImportStatus: "pending" as const,
        initialImportProgress: null,
      };
      vi.mocked(db.fibermadeConnection.findUnique).mockResolvedValue(connection as never);

      const result = await action({
        request: new Request("http://localhost/app/import", { method: "GET" }),
        params: {},
        context: {},
        unstable_pattern: "/",
      });

      expect(result).toEqual({ error: "Method not allowed" });
    });

    it("returns started when connected and POST", async () => {
      const connection = {
        id: "conn-1",
        shop: "test.myshopify.com",
        fibermadeApiToken: "token",
        fibermadeIntegrationId: 1,
        connectedAt: new Date(),
        initialImportStatus: "pending" as const,
        initialImportProgress: null,
      };
      vi.mocked(db.fibermadeConnection.findUnique).mockResolvedValue(connection as never);
      vi.mocked(db.fibermadeConnection.update).mockResolvedValue(connection as never);

      const result = await action({
        request: new Request("http://localhost/app/import", { method: "POST" }),
        params: {},
        context: {},
        unstable_pattern: "/",
      });

      expect(result).toEqual({ started: true });
      expect(db.fibermadeConnection.update).toHaveBeenCalled();
      expect(mockRunImport).toHaveBeenCalled();
    });

    it("returns error when import is already in progress", async () => {
      const connection = {
        id: "conn-1",
        shop: "test.myshopify.com",
        fibermadeApiToken: "token",
        fibermadeIntegrationId: 1,
        connectedAt: new Date(),
        initialImportStatus: "in_progress" as const,
        initialImportProgress: JSON.stringify({ total: 5, imported: 2, failed: 0 }),
      };
      vi.mocked(db.fibermadeConnection.findUnique).mockResolvedValue(connection as never);

      const result = await action({
        request: new Request("http://localhost/app/import", { method: "POST" }),
        params: {},
        context: {},
        unstable_pattern: "/",
      });

      expect(result).toEqual({ error: "Import is already in progress" });
      expect(mockRunImport).not.toHaveBeenCalled();
    });
  });
});
