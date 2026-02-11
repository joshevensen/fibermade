import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import type { FibermadeClient } from "../fibermade-client.server";
import {
  createMapping,
  findFibermadeIdByShopifyGid,
  findShopifyGidByFibermadeId,
  mappingExists,
} from "./mapping.server";

describe("mapping.server", () => {
  let mockClient: {
    lookupExternalIdentifier: ReturnType<typeof vi.fn>;
    lookupExternalIdentifierByIdentifiable: ReturnType<typeof vi.fn>;
    createExternalIdentifier: ReturnType<typeof vi.fn>;
  };
  let client: FibermadeClient;

  beforeEach(() => {
    mockClient = {
      lookupExternalIdentifier: vi.fn(),
      lookupExternalIdentifierByIdentifiable: vi.fn(),
      createExternalIdentifier: vi.fn(),
    };
    client = mockClient as unknown as FibermadeClient;
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });
  const integrationId = 1;
  const externalType = "shopify_product";
  const shopifyGid = "gid://shopify/Product/123";
  const identifiableType = "App\\Models\\Colorway";
  const identifiableId = 42;

  const sampleRecord = {
    id: 10,
    integration_id: integrationId,
    identifiable_type: identifiableType,
    identifiable_id: identifiableId,
    external_type: externalType,
    external_id: shopifyGid,
    data: null,
    created_at: "2024-01-01T00:00:00Z",
    updated_at: "2024-01-01T00:00:00Z",
  };

  describe("findFibermadeIdByShopifyGid", () => {
    it("returns identifiableType and identifiableId when mapping exists", async () => {
      mockClient.lookupExternalIdentifier.mockResolvedValueOnce({
        data: [sampleRecord],
      });

      const result = await findFibermadeIdByShopifyGid(
        client,
        integrationId,
        externalType,
        shopifyGid
      );

      expect(result).toEqual({
        identifiableType,
        identifiableId,
      });
      expect(mockClient.lookupExternalIdentifier).toHaveBeenCalledTimes(1);
      expect(mockClient.lookupExternalIdentifier).toHaveBeenCalledWith({
        integration_id: integrationId,
        external_type: externalType,
        external_id: shopifyGid,
      });
    });

    it("returns null when no mapping exists", async () => {
      mockClient.lookupExternalIdentifier.mockResolvedValueOnce({ data: [] });

      const result = await findFibermadeIdByShopifyGid(
        client,
        integrationId,
        externalType,
        shopifyGid
      );

      expect(result).toBeNull();
      expect(mockClient.lookupExternalIdentifier).toHaveBeenCalledTimes(1);
    });

    it("rethrows with context when API call fails", async () => {
      mockClient.lookupExternalIdentifier.mockRejectedValue(
        new Error("API error")
      );

      await expect(
        findFibermadeIdByShopifyGid(client, integrationId, externalType, shopifyGid)
      ).rejects.toThrow(/findFibermadeIdByShopifyGid failed.*API error/);
    });
  });

  describe("findShopifyGidByFibermadeId", () => {
    it("returns GID string when mapping exists for the external type", async () => {
      mockClient.lookupExternalIdentifierByIdentifiable.mockResolvedValueOnce({
        data: [sampleRecord],
      });

      const result = await findShopifyGidByFibermadeId(
        client,
        integrationId,
        identifiableType,
        identifiableId,
        externalType
      );

      expect(result).toBe(shopifyGid);
      expect(
        mockClient.lookupExternalIdentifierByIdentifiable
      ).toHaveBeenCalledTimes(1);
      expect(
        mockClient.lookupExternalIdentifierByIdentifiable
      ).toHaveBeenCalledWith(integrationId, identifiableType, identifiableId);
    });

    it("returns null when no mapping exists", async () => {
      mockClient.lookupExternalIdentifierByIdentifiable.mockResolvedValueOnce({
        data: [],
      });

      const result = await findShopifyGidByFibermadeId(
        client,
        integrationId,
        identifiableType,
        identifiableId,
        externalType
      );

      expect(result).toBeNull();
    });

    it("returns null when data has no record matching external_type", async () => {
      mockClient.lookupExternalIdentifierByIdentifiable.mockResolvedValueOnce({
        data: [
          {
            ...sampleRecord,
            external_type: "shopify_variant",
            external_id: "gid://shopify/ProductVariant/999",
          },
        ],
      });

      const result = await findShopifyGidByFibermadeId(
        client,
        integrationId,
        identifiableType,
        identifiableId,
        externalType
      );

      expect(result).toBeNull();
    });

    it("rethrows with context when API call fails", async () => {
      mockClient.lookupExternalIdentifierByIdentifiable.mockRejectedValue(
        new Error("Network error")
      );

      await expect(
        findShopifyGidByFibermadeId(
          client,
          integrationId,
          identifiableType,
          identifiableId,
          externalType
        )
      ).rejects.toThrow(/findShopifyGidByFibermadeId failed.*Network error/);
    });
  });

  describe("createMapping", () => {
    it("calls client.createExternalIdentifier with correct payload and returns created record", async () => {
      mockClient.createExternalIdentifier.mockResolvedValueOnce(sampleRecord);

      const result = await createMapping(
        client,
        integrationId,
        identifiableType,
        identifiableId,
        externalType,
        shopifyGid
      );

      expect(result).toEqual(sampleRecord);
      expect(mockClient.createExternalIdentifier).toHaveBeenCalledTimes(1);
      expect(mockClient.createExternalIdentifier).toHaveBeenCalledWith({
        integration_id: integrationId,
        identifiable_type: identifiableType,
        identifiable_id: identifiableId,
        external_type: externalType,
        external_id: shopifyGid,
      });
    });

    it("forwards optional data to createExternalIdentifier", async () => {
      const extraData = { admin_url: "https://shop.myshopify.com/admin/products/123" };
      mockClient.createExternalIdentifier.mockResolvedValueOnce({
        ...sampleRecord,
        data: extraData,
      });

      await createMapping(
        client,
        integrationId,
        identifiableType,
        identifiableId,
        externalType,
        shopifyGid,
        extraData
      );

      expect(mockClient.createExternalIdentifier).toHaveBeenCalledWith(
        expect.objectContaining({
          integration_id: integrationId,
          external_id: shopifyGid,
          data: extraData,
        })
      );
    });

    it("rethrows with context when API call fails", async () => {
      mockClient.createExternalIdentifier.mockRejectedValue(
        new Error("422 validation failed")
      );

      await expect(
        createMapping(
          client,
          integrationId,
          identifiableType,
          identifiableId,
          externalType,
          shopifyGid
        )
      ).rejects.toThrow(/createMapping failed.*422 validation failed/);
    });
  });

  describe("mappingExists", () => {
    it("returns true when mapping exists", async () => {
      mockClient.lookupExternalIdentifier.mockResolvedValueOnce({
        data: [sampleRecord],
      });

      const result = await mappingExists(
        client,
        integrationId,
        externalType,
        shopifyGid
      );

      expect(result).toBe(true);
      expect(mockClient.lookupExternalIdentifier).toHaveBeenCalledWith({
        integration_id: integrationId,
        external_type: externalType,
        external_id: shopifyGid,
      });
    });

    it("returns false when no mapping exists", async () => {
      mockClient.lookupExternalIdentifier.mockResolvedValueOnce({ data: [] });

      const result = await mappingExists(
        client,
        integrationId,
        externalType,
        shopifyGid
      );

      expect(result).toBe(false);
    });

    it("rethrows with context when API call fails", async () => {
      mockClient.lookupExternalIdentifier.mockRejectedValue(
        new Error("401 Unauthorized")
      );

      await expect(
        mappingExists(client, integrationId, externalType, shopifyGid)
      ).rejects.toThrow(/mappingExists failed.*401 Unauthorized/);
    });
  });
});
