import { describe, expect, it, vi } from "vitest";
import type { ShopifyGraphqlRunner } from "./metafields.server";
import {
  setProductAndVariantMetafields,
} from "./metafields.server";
import { METAFIELD_KEYS, METAFIELD_NAMESPACE } from "./constants";

describe("setProductAndVariantMetafields", () => {
  it("calls GraphQL runner once with metafieldsSet mutation and correct variables", async () => {
    const graphql = vi.fn().mockResolvedValue({
      data: {
        metafieldsSet: {
          metafields: [],
          userErrors: [],
        },
      },
    });

    await setProductAndVariantMetafields(
      graphql as ShopifyGraphqlRunner,
      "gid://shopify/Product/123",
      42,
      [
        { variantGid: "gid://shopify/ProductVariant/1", baseId: 10 },
        { variantGid: "gid://shopify/ProductVariant/2", baseId: 11 },
      ]
    );

    expect(graphql).toHaveBeenCalledTimes(1);
    const [query, variables] = graphql.mock.calls[0];
    expect(query).toContain("metafieldsSet");
    expect(query).toContain("$metafields: [MetafieldsSetInput!]!");
    expect(variables).toHaveProperty("metafields");
    const metafields = (variables as { metafields: Array<Record<string, string>> }).metafields;
    expect(metafields).toHaveLength(3);

    expect(metafields[0]).toMatchObject({
      ownerId: "gid://shopify/Product/123",
      namespace: METAFIELD_NAMESPACE,
      key: METAFIELD_KEYS.COLORWAY_ID,
      value: "42",
      type: "number_integer",
    });
    expect(metafields[1]).toMatchObject({
      ownerId: "gid://shopify/ProductVariant/1",
      namespace: METAFIELD_NAMESPACE,
      key: METAFIELD_KEYS.BASE_ID,
      value: "10",
      type: "number_integer",
    });
    expect(metafields[2]).toMatchObject({
      ownerId: "gid://shopify/ProductVariant/2",
      namespace: METAFIELD_NAMESPACE,
      key: METAFIELD_KEYS.BASE_ID,
      value: "11",
      type: "number_integer",
    });
  });

  it("uses fibermade namespace and colorway_id / base_id keys", async () => {
    const graphql = vi.fn().mockResolvedValue({
      data: { metafieldsSet: { metafields: [], userErrors: [] } },
    });

    await setProductAndVariantMetafields(
      graphql as ShopifyGraphqlRunner,
      "gid://shopify/Product/1",
      99,
      [{ variantGid: "gid://shopify/ProductVariant/1", baseId: 5 }]
    );

    const { metafields } = (graphql.mock.calls[0][1] as { metafields: Array<Record<string, string>> });
    expect(metafields[0].namespace).toBe("fibermade");
    expect(metafields[0].key).toBe("colorway_id");
    expect(metafields[1].namespace).toBe("fibermade");
    expect(metafields[1].key).toBe("base_id");
  });

  it("does not throw when runner throws", async () => {
    const graphql = vi.fn().mockRejectedValue(new Error("Network error"));

    await expect(
      setProductAndVariantMetafields(
        graphql as ShopifyGraphqlRunner,
        "gid://shopify/Product/1",
        1,
        []
      )
    ).resolves.toBeUndefined();
  });

  it("does not throw when response contains userErrors", async () => {
    const graphql = vi.fn().mockResolvedValue({
      data: {
        metafieldsSet: {
          metafields: [],
          userErrors: [{ field: "metafields", message: "Invalid value" }],
        },
      },
    });

    await expect(
      setProductAndVariantMetafields(
        graphql as ShopifyGraphqlRunner,
        "gid://shopify/Product/1",
        1,
        []
      )
    ).resolves.toBeUndefined();
  });

  it("batches product and all variant metafields in one call", async () => {
    const graphql = vi.fn().mockResolvedValue({
      data: { metafieldsSet: { metafields: [], userErrors: [] } },
    });
    const variants = [
      { variantGid: "gid://shopify/ProductVariant/1", baseId: 10 },
      { variantGid: "gid://shopify/ProductVariant/2", baseId: 20 },
      { variantGid: "gid://shopify/ProductVariant/3", baseId: 30 },
    ];

    await setProductAndVariantMetafields(
      graphql as ShopifyGraphqlRunner,
      "gid://shopify/Product/100",
      7,
      variants
    );

    expect(graphql).toHaveBeenCalledTimes(1);
    const { metafields } = (graphql.mock.calls[0][1] as { metafields: unknown[] });
    expect(metafields).toHaveLength(4);
  });
});
