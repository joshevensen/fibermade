import { beforeEach, describe, expect, it, vi } from "vitest";
import { action, loader } from "./app.settings";

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

const mockGetIntegration = vi.fn();
const mockListCollections = vi.fn();
const mockUpdateIntegration = vi.fn();

vi.mock("../services/fibermade-client.server", () => ({
  FibermadeClient: vi.fn().mockImplementation(function (this: {
    setToken: ReturnType<typeof vi.fn>;
    getIntegration: ReturnType<typeof vi.fn>;
    listCollections: ReturnType<typeof vi.fn>;
    updateIntegration: ReturnType<typeof vi.fn>;
  }) {
    this.setToken = vi.fn();
    this.getIntegration = mockGetIntegration;
    this.listCollections = mockListCollections;
    this.updateIntegration = mockUpdateIntegration;
    return this;
  }),
}));

import { authenticate } from "../shopify.server";
import db from "../db.server";

describe("app.settings", () => {
  const mockSession = { shop: "test.myshopify.com" };

  const mockIntegration = {
    id: 1,
    type: "shopify",
    settings: { shop: "test.myshopify.com", auto_sync: true, excluded_collection_ids: [] },
    active: true,
    created_at: "2025-02-14T12:00:00.000000Z",
    updated_at: "2025-02-14T12:00:00.000000Z",
  };

  const mockCollections = [
    {
      id: 5,
      name: "Yarn Collection",
      description: null,
      status: "active",
      created_at: "2025-02-14T12:00:00.000000Z",
      updated_at: "2025-02-14T12:00:00.000000Z",
      colorways_count: 12,
    },
    {
      id: 12,
      name: "Fiber Collection",
      description: null,
      status: "active",
      created_at: "2025-02-14T12:00:00.000000Z",
      updated_at: "2025-02-14T12:00:00.000000Z",
      colorways_count: 8,
    },
  ];

  const connection = {
    id: "conn-1",
    shop: "test.myshopify.com",
    fibermadeApiToken: "token",
    fibermadeIntegrationId: 42,
    connectedAt: new Date("2024-01-15T10:00:00Z"),
  };

  const loaderRequest = () =>
    new Request("http://localhost/app/settings");
  const loaderArgs = () => ({
    request: loaderRequest(),
    params: {},
    context: {},
    unstable_pattern: "/",
  });

  const actionRequest = (formData: Record<string, string>) =>
    new Request("http://localhost/app/settings", {
      method: "POST",
      body: new URLSearchParams(formData),
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
    });
  const actionArgs = (formData: Record<string, string>) => ({
    request: actionRequest(formData),
    params: {},
    context: {},
    unstable_pattern: "/",
  });

  beforeEach(() => {
    vi.clearAllMocks();
    vi.mocked(authenticate.admin).mockResolvedValue({
      session: mockSession,
    } as never);
    vi.mocked(db.fibermadeConnection.findUnique).mockResolvedValue(
      connection as never
    );
    mockGetIntegration.mockResolvedValue(mockIntegration);
    mockListCollections.mockResolvedValue({ data: mockCollections });
    mockUpdateIntegration.mockResolvedValue({});
    process.env.FIBERMADE_API_URL = "https://api.fibermade.test";
  });

  describe("loader", () => {
    it("redirects to /app when no connection exists", async () => {
      vi.mocked(db.fibermadeConnection.findUnique).mockResolvedValue(null);

      let thrown: unknown;
      try {
        await loader(loaderArgs());
      } catch (e) {
        thrown = e;
      }

      expect(thrown).toBeInstanceOf(Response);
      const response = thrown as Response;
      expect(response.status).toBe(302);
      expect(response.headers.get("Location")).toContain("/app");
    });

    it("redirects to /app when FIBERMADE_API_URL is missing", async () => {
      const originalEnv = process.env.FIBERMADE_API_URL;
      process.env.FIBERMADE_API_URL = "";

      let thrown: unknown;
      try {
        await loader(loaderArgs());
      } catch (e) {
        thrown = e;
      }

      process.env.FIBERMADE_API_URL = originalEnv;
      expect(thrown).toBeInstanceOf(Response);
      const response = thrown as Response;
      expect(response.status).toBe(302);
      expect(response.headers.get("Location")).toContain("/app");
    });

    it("returns integration, collections, and parsed settings when connected", async () => {
      const result = await loader(loaderArgs());

      expect(result).toEqual({
        integration: mockIntegration,
        collections: mockCollections,
        autoSync: true,
        excludedCollectionIds: [],
      });
      expect(mockGetIntegration).toHaveBeenCalledTimes(1);
      expect(mockGetIntegration).toHaveBeenCalledWith(42);
      expect(mockListCollections).toHaveBeenCalledTimes(1);
      expect(mockListCollections).toHaveBeenCalledWith({ limit: 100 });
    });

    it("parses autoSync false and excludedCollectionIds from settings", async () => {
      mockGetIntegration.mockResolvedValue({
        ...mockIntegration,
        settings: {
          shop: "test.myshopify.com",
          auto_sync: false,
          excluded_collection_ids: [5],
        },
      });

      const result = await loader(loaderArgs());

      expect(result).toEqual({
        integration: { ...mockIntegration, settings: expect.any(Object) },
        collections: mockCollections,
        autoSync: false,
        excludedCollectionIds: [5],
      });
    });

    it("propagates error when getIntegration throws", async () => {
      mockGetIntegration.mockRejectedValue(new Error("Network error"));

      await expect(loader(loaderArgs())).rejects.toThrow("Network error");
    });

    it("propagates error when listCollections throws", async () => {
      mockListCollections.mockRejectedValue(new Error("API error"));

      await expect(loader(loaderArgs())).rejects.toThrow("API error");
    });
  });

  describe("action", () => {
    it("returns success after updateIntegration", async () => {
      const formData = {
        intent: "save-settings",
        autoSync: "on",
        include_5: "on",
        include_12: "on",
      };

      const result = await action(actionArgs(formData));

      expect(result).toEqual({ success: true });
      expect(mockUpdateIntegration).toHaveBeenCalledTimes(1);
      expect(mockUpdateIntegration).toHaveBeenCalledWith(42, {
        settings: expect.objectContaining({
          auto_sync: true,
          excluded_collection_ids: [],
        }),
      });
    });

    it("preserves shop in merged settings", async () => {
      const formData = {
        intent: "save-settings",
        autoSync: "on",
        include_5: "on",
        include_12: "on",
      };

      await action(actionArgs(formData));

      const call = mockUpdateIntegration.mock.calls[0];
      const settings = call[1].settings as Record<string, unknown>;
      expect(settings.shop).toBe("test.myshopify.com");
    });

    it("computes excludedCollectionIds from unchecked include checkboxes", async () => {
      const formData = {
        intent: "save-settings",
        autoSync: "on",
        include_5: "on",
      };

      await action(actionArgs(formData));

      const call = mockUpdateIntegration.mock.calls[0];
      const settings = call[1].settings as Record<string, unknown>;
      expect(settings.excluded_collection_ids).toEqual([12]);
    });

    it("returns error when updateIntegration throws", async () => {
      mockUpdateIntegration.mockRejectedValue(new Error("API failed"));

      const formData = {
        intent: "save-settings",
        autoSync: "on",
        include_5: "on",
        include_12: "on",
      };

      const result = await action(actionArgs(formData));

      expect(result).toEqual({ success: false, error: "API failed" });
    });

    it("returns error when no connection exists", async () => {
      vi.mocked(db.fibermadeConnection.findUnique).mockResolvedValue(null);

      const formData = {
        intent: "save-settings",
        autoSync: "on",
        include_5: "on",
        include_12: "on",
      };

      const result = await action(actionArgs(formData));

      expect(result).toEqual({ success: false, error: "Not connected to Fibermade." });
      expect(mockUpdateIntegration).not.toHaveBeenCalled();
    });

    it("returns error for invalid intent", async () => {
      const formData = { intent: "invalid", autoSync: "on" };

      const result = await action(actionArgs(formData));

      expect(result).toEqual({ success: false, error: "Invalid intent" });
      expect(mockUpdateIntegration).not.toHaveBeenCalled();
    });

    it("returns error for non-POST request", async () => {
      const result = await action({
        request: new Request("http://localhost/app/settings", { method: "GET" }),
        params: {},
        context: {},
        unstable_pattern: "/",
      });

      expect(result).toEqual({ success: false, error: "Method not allowed" });
      expect(mockUpdateIntegration).not.toHaveBeenCalled();
    });
  });
});
