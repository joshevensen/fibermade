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
      create: vi.fn(),
      delete: vi.fn(),
    },
  },
}));

import { authenticate } from "../shopify.server";
import db from "../db.server";

const mockSession = { shop: "test.myshopify.com", accessToken: "shpat_test" };

const mockConnection = {
  id: 1,
  shop: "test.myshopify.com",
  fibermadeApiToken: "token",
  fibermadeIntegrationId: 42,
  connectedAt: new Date("2024-01-15T10:00:00Z"),
};

function makeRequest(method: string, formData?: Record<string, string>) {
  if (method === "GET" || method === "HEAD") {
    return new Request("http://localhost", { method });
  }
  const body = new FormData();
  for (const [key, value] of Object.entries(formData ?? {})) {
    body.set(key, value);
  }
  return new Request("http://localhost", { method, body });
}

describe("app._index", () => {
  beforeEach(() => {
    vi.resetAllMocks();
    vi.mocked(authenticate.admin).mockResolvedValue({ session: mockSession } as never);
    vi.unstubAllGlobals();
  });

  // ---------------------------------------------------------------------------
  // loader
  // ---------------------------------------------------------------------------

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
      expect(result).not.toHaveProperty("connectionError");
    });

    it("returns connected: true when connection exists and FIBERMADE_API_URL is empty", async () => {
      vi.mocked(db.fibermadeConnection.findUnique).mockResolvedValue(mockConnection as never);

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

    it("returns connectionError: token_invalid when API returns 401", async () => {
      vi.mocked(db.fibermadeConnection.findUnique).mockResolvedValue(mockConnection as never);
      vi.stubGlobal(
        "fetch",
        vi.fn().mockResolvedValue(
          new Response(JSON.stringify({ message: "Unauthenticated." }), {
            status: 401,
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

      expect(result).toMatchObject({
        connected: false,
        connectionError: "token_invalid",
        shop: "test.myshopify.com",
        connectedAt: "2024-01-15T10:00:00.000Z",
      });
    });

    it("returns connectionError: integration_inactive when API returns 404", async () => {
      vi.mocked(db.fibermadeConnection.findUnique).mockResolvedValue(mockConnection as never);
      vi.stubGlobal(
        "fetch",
        vi.fn().mockResolvedValue(
          new Response(JSON.stringify({ message: "Not found." }), {
            status: 404,
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

      expect(result).toMatchObject({ connected: false, connectionError: "integration_inactive" });
    });

    it("returns connectionError: integration_inactive when integration.active is false", async () => {
      vi.mocked(db.fibermadeConnection.findUnique).mockResolvedValue(mockConnection as never);
      vi.stubGlobal(
        "fetch",
        vi.fn().mockResolvedValue(
          new Response(JSON.stringify({ data: { id: 42, active: false } }), {
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

      expect(result).toMatchObject({ connected: false, connectionError: "integration_inactive" });
    });

    it("returns connected: true when integration is active", async () => {
      vi.mocked(db.fibermadeConnection.findUnique).mockResolvedValue(mockConnection as never);
      vi.stubGlobal(
        "fetch",
        vi.fn().mockResolvedValue(
          new Response(JSON.stringify({ data: { id: 42, active: true } }), {
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

      expect(result.connected).toBe(true);
      expect(result.shop).toBe("test.myshopify.com");
    });

    it("returns connected: true when API returns 500 (graceful fallback)", async () => {
      vi.mocked(db.fibermadeConnection.findUnique).mockResolvedValue(mockConnection as never);
      vi.stubGlobal(
        "fetch",
        vi.fn().mockResolvedValue(
          new Response(JSON.stringify({ message: "Server error" }), {
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

      expect(result.connected).toBe(true);
    });
  });

  // ---------------------------------------------------------------------------
  // action
  // ---------------------------------------------------------------------------

  describe("action", () => {
    it("returns error for non-POST method", async () => {
      const result = await action({
        request: makeRequest("GET"),
        params: {},
        context: {},
        unstable_pattern: "/",
      });

      expect(result).toEqual({ intent: "disconnect", success: false, error: "Method not allowed" });
    });

    it("returns error for invalid intent", async () => {
      const result = await action({
        request: makeRequest("POST", { intent: "invalid" }),
        params: {},
        context: {},
        unstable_pattern: "/",
      });

      expect(result).toEqual({ intent: "disconnect", success: false, error: "Invalid intent." });
    });

    // -------------------------------------------------------------------------
    // connect
    // -------------------------------------------------------------------------

    describe("connect", () => {
      it("returns error when apiToken is missing", async () => {
        const result = await action({
          request: makeRequest("POST", { intent: "connect" }),
          params: {},
          context: {},
          unstable_pattern: "/",
        });

        expect(result).toEqual({
          intent: "connect",
          success: false,
          error: "API token is required.",
          field: "apiToken",
        });
      });

      it("returns error when shop is already connected", async () => {
        vi.mocked(db.fibermadeConnection.findUnique).mockResolvedValue(mockConnection as never);

        const result = await action({
          request: makeRequest("POST", { intent: "connect", apiToken: "newtoken" }),
          params: {},
          context: {},
          unstable_pattern: "/",
        });

        expect(result).toMatchObject({
          intent: "connect",
          success: false,
          field: "shop",
        });
      });

      it("returns error when health check returns 401", async () => {
        vi.mocked(db.fibermadeConnection.findUnique).mockResolvedValue(null);
        vi.stubGlobal(
          "fetch",
          vi.fn().mockResolvedValue(
            new Response(JSON.stringify({ message: "Unauthenticated." }), {
              status: 401,
              headers: { "Content-Type": "application/json" },
            })
          )
        );

        const originalEnv = process.env.FIBERMADE_API_URL;
        process.env.FIBERMADE_API_URL = "https://api.example.com";

        const result = await action({
          request: makeRequest("POST", { intent: "connect", apiToken: "badtoken" }),
          params: {},
          context: {},
          unstable_pattern: "/",
        });

        process.env.FIBERMADE_API_URL = originalEnv;

        expect(result).toEqual({
          intent: "connect",
          success: false,
          error: "Invalid Fibermade API token. Check your credentials and try again.",
          field: "apiToken",
        });
      });

      it("returns success when token is valid and integration is created", async () => {
        vi.mocked(db.fibermadeConnection.findUnique).mockResolvedValue(null);
        vi.mocked(db.fibermadeConnection.create).mockResolvedValue({} as never);

        let callCount = 0;
        vi.stubGlobal(
          "fetch",
          vi.fn().mockImplementation(() => {
            callCount++;
            if (callCount === 1) {
              // health check
              return Promise.resolve(
                new Response(JSON.stringify({ status: "ok" }), {
                  status: 200,
                  headers: { "Content-Type": "application/json" },
                })
              );
            }
            // create integration
            return Promise.resolve(
              new Response(JSON.stringify({ data: { id: 7, type: "shopify", active: true } }), {
                status: 201,
                headers: { "Content-Type": "application/json" },
              })
            );
          })
        );

        const originalEnv = process.env.FIBERMADE_API_URL;
        process.env.FIBERMADE_API_URL = "https://api.example.com";

        const result = await action({
          request: makeRequest("POST", { intent: "connect", apiToken: "goodtoken" }),
          params: {},
          context: {},
          unstable_pattern: "/",
        });

        process.env.FIBERMADE_API_URL = originalEnv;

        expect(result).toEqual({ intent: "connect", success: true });
        expect(db.fibermadeConnection.create).toHaveBeenCalledWith(
          expect.objectContaining({
            data: expect.objectContaining({
              shop: "test.myshopify.com",
              fibermadeApiToken: "goodtoken",
              fibermadeIntegrationId: 7,
            }),
          })
        );
      });
    });

    // -------------------------------------------------------------------------
    // disconnect
    // -------------------------------------------------------------------------

    describe("disconnect", () => {
      it("returns success when no connection exists", async () => {
        vi.mocked(db.fibermadeConnection.findUnique).mockResolvedValue(null);

        const result = await action({
          request: makeRequest("POST", { intent: "disconnect" }),
          params: {},
          context: {},
          unstable_pattern: "/",
        });

        expect(result).toEqual({ intent: "disconnect", success: true });
      });

      it("deletes connection and calls Fibermade API to deactivate", async () => {
        vi.mocked(db.fibermadeConnection.findUnique).mockResolvedValue(mockConnection as never);
        vi.mocked(db.fibermadeConnection.delete).mockResolvedValue({} as never);
        vi.stubGlobal(
          "fetch",
          vi.fn().mockResolvedValue(
            new Response(JSON.stringify({ data: { id: 42, active: false } }), {
              status: 200,
              headers: { "Content-Type": "application/json" },
            })
          )
        );

        const originalEnv = process.env.FIBERMADE_API_URL;
        process.env.FIBERMADE_API_URL = "https://api.example.com";

        const result = await action({
          request: makeRequest("POST", { intent: "disconnect" }),
          params: {},
          context: {},
          unstable_pattern: "/",
        });

        process.env.FIBERMADE_API_URL = originalEnv;

        expect(result).toEqual({ intent: "disconnect", success: true });
        expect(db.fibermadeConnection.delete).toHaveBeenCalledWith({
          where: { id: mockConnection.id },
        });
      });

      it("still deletes connection even if Fibermade API call fails", async () => {
        vi.mocked(db.fibermadeConnection.findUnique).mockResolvedValue(mockConnection as never);
        vi.mocked(db.fibermadeConnection.delete).mockResolvedValue({} as never);
        vi.stubGlobal("fetch", vi.fn().mockRejectedValue(new Error("network error")));

        const originalEnv = process.env.FIBERMADE_API_URL;
        process.env.FIBERMADE_API_URL = "https://api.example.com";

        const result = await action({
          request: makeRequest("POST", { intent: "disconnect" }),
          params: {},
          context: {},
          unstable_pattern: "/",
        });

        process.env.FIBERMADE_API_URL = originalEnv;

        expect(result).toEqual({ intent: "disconnect", success: true });
        expect(db.fibermadeConnection.delete).toHaveBeenCalled();
      });
    });
  });
});
