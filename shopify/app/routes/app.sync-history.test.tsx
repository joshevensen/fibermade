import { beforeEach, describe, expect, it, vi } from "vitest";
import { loader } from "./app.sync-history";

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

const mockGetIntegrationLogs = vi.fn();

vi.mock("../services/fibermade-client.server", () => ({
  FibermadeClient: vi.fn().mockImplementation(function (this: {
    setToken: ReturnType<typeof vi.fn>;
    getIntegrationLogs: ReturnType<typeof vi.fn>;
  }) {
    this.setToken = vi.fn();
    this.getIntegrationLogs = mockGetIntegrationLogs;
    return this;
  }),
}));

import { authenticate } from "../shopify.server";
import db from "../db.server";

describe("app.sync-history", () => {
  const mockSession = { shop: "test.myshopify.com" };

  const mockLogs = [
    {
      id: 1,
      integration_id: 1,
      loggable_type: "App\\Models\\Colorway",
      loggable_id: 10,
      status: "success",
      message: "Synced colorway",
      metadata: null,
      synced_at: "2025-02-14T12:00:00.000000Z",
      created_at: "2025-02-14T12:00:00.000000Z",
      updated_at: "2025-02-14T12:00:00.000000Z",
    },
    {
      id: 2,
      integration_id: 1,
      loggable_type: "App\\Models\\Collection",
      loggable_id: 20,
      status: "error",
      message: "Sync failed",
      metadata: null,
      synced_at: "2025-02-14T11:00:00.000000Z",
      created_at: "2025-02-14T11:00:00.000000Z",
      updated_at: "2025-02-14T11:00:00.000000Z",
    },
  ];

  beforeEach(() => {
    vi.clearAllMocks();
    vi.mocked(authenticate.admin).mockResolvedValue({
      session: mockSession,
    } as never);
    mockGetIntegrationLogs.mockResolvedValue({ data: mockLogs });
    process.env.FIBERMADE_API_URL = "https://api.fibermade.test";
  });

  describe("loader", () => {
    it("redirects to /app when no connection exists", async () => {
      vi.mocked(db.fibermadeConnection.findUnique).mockResolvedValue(null);

      let thrown: unknown;
      try {
        await loader({
          request: new Request("http://localhost/app/sync-history"),
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

    it("redirects to /app when FIBERMADE_API_URL is missing", async () => {
      const connection = {
        id: "conn-1",
        shop: "test.myshopify.com",
        fibermadeApiToken: "token",
        fibermadeIntegrationId: 1,
        connectedAt: new Date("2024-01-15T10:00:00Z"),
      };
      vi.mocked(db.fibermadeConnection.findUnique).mockResolvedValue(
        connection as never
      );
      const originalEnv = process.env.FIBERMADE_API_URL;
      process.env.FIBERMADE_API_URL = "";

      let thrown: unknown;
      try {
        await loader({
          request: new Request("http://localhost/app/sync-history"),
          params: {},
          context: {},
          unstable_pattern: "/",
        });
      } catch (e) {
        thrown = e;
      }

      process.env.FIBERMADE_API_URL = originalEnv;
      expect(thrown).toBeInstanceOf(Response);
      const response = thrown as Response;
      expect(response.status).toBe(302);
      expect(response.headers.get("Location")).toContain("/app");
    });

    it("returns logs when connected and calls getIntegrationLogs with limit 100", async () => {
      const connection = {
        id: "conn-1",
        shop: "test.myshopify.com",
        fibermadeApiToken: "token",
        fibermadeIntegrationId: 42,
        connectedAt: new Date("2024-01-15T10:00:00Z"),
      };
      vi.mocked(db.fibermadeConnection.findUnique).mockResolvedValue(
        connection as never
      );

      const result = await loader({
        request: new Request("http://localhost/app/sync-history"),
        params: {},
        context: {},
        unstable_pattern: "/",
      });

      expect(result).toEqual({ logs: mockLogs });
      expect(mockGetIntegrationLogs).toHaveBeenCalledTimes(1);
      expect(mockGetIntegrationLogs).toHaveBeenCalledWith(42, { limit: 100 });
    });

    it("propagates error when getIntegrationLogs throws", async () => {
      const connection = {
        id: "conn-1",
        shop: "test.myshopify.com",
        fibermadeApiToken: "token",
        fibermadeIntegrationId: 1,
        connectedAt: new Date("2024-01-15T10:00:00Z"),
      };
      vi.mocked(db.fibermadeConnection.findUnique).mockResolvedValue(
        connection as never
      );
      mockGetIntegrationLogs.mockRejectedValue(new Error("Network error"));

      await expect(
        loader({
          request: new Request("http://localhost/app/sync-history"),
          params: {},
          context: {},
          unstable_pattern: "/",
        })
      ).rejects.toThrow("Network error");
    });
  });
});
