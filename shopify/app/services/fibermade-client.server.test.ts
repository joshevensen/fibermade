import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { FibermadeClient } from "./fibermade-client.server";
import { FibermadeAuthError } from "./fibermade-client.types";

describe("FibermadeClient", () => {
  const baseUrl = "https://api.example.com";
  const token = "test-token";

  let fetchMock: ReturnType<typeof vi.fn>;

  beforeEach(() => {
    fetchMock = vi.fn();
    vi.stubGlobal("fetch", fetchMock);
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  describe("getIntegration", () => {
    it("returns integration data and calls correct URL with Bearer token", async () => {
      const integration = {
        id: 1,
        type: "shopify",
        settings: null,
        active: true,
        created_at: "2024-01-01T00:00:00Z",
        updated_at: "2024-01-01T00:00:00Z",
      };
      fetchMock.mockResolvedValueOnce(
        new Response(JSON.stringify({ data: integration }), {
          status: 200,
          headers: { "Content-Type": "application/json" },
        })
      );

      const client = new FibermadeClient(baseUrl, token);
      const result = await client.getIntegration(1);

      expect(result).toEqual(integration);
      expect(fetchMock).toHaveBeenCalledTimes(1);
      expect(fetchMock).toHaveBeenCalledWith(
        "https://api.example.com/api/v1/integrations/1",
        expect.objectContaining({
          method: "GET",
          headers: expect.objectContaining({
            Authorization: "Bearer test-token",
            Accept: "application/json",
          }),
        })
      );
    });

    it("throws FibermadeAuthError on 401 response", async () => {
      fetchMock.mockResolvedValueOnce(
        new Response(JSON.stringify({ message: "Unauthenticated." }), {
          status: 401,
          headers: { "Content-Type": "application/json" },
        })
      );

      const client = new FibermadeClient(baseUrl, token);

      let thrown: unknown;
      try {
        await client.getIntegration(1);
      } catch (e) {
        thrown = e;
      }
      expect(thrown).toBeInstanceOf(FibermadeAuthError);
      expect(thrown).toMatchObject({
        status: 401,
        message: "Unauthenticated.",
      });
    });
  });
});
