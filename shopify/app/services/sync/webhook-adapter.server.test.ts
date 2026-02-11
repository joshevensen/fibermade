import { describe, expect, it } from "vitest";
import { restProductToShopifyProduct } from "./webhook-adapter.server";

describe("restProductToShopifyProduct", () => {
  it("converts numeric id to GID", () => {
    const result = restProductToShopifyProduct({
      id: 1234567890,
      title: "Test Product",
    } as Record<string, unknown>);
    expect(result.id).toBe("gid://shopify/Product/1234567890");
  });

  it("maps body_html to descriptionHtml", () => {
    const result = restProductToShopifyProduct({
      id: 1,
      title: "T",
      body_html: "<p>Description</p>",
    } as Record<string, unknown>);
    expect(result.descriptionHtml).toBe("<p>Description</p>");
  });

  it("maps lowercase status to uppercase", () => {
    expect(
      restProductToShopifyProduct({
        id: 1,
        title: "T",
        status: "active",
      } as Record<string, unknown>).status
    ).toBe("ACTIVE");
    expect(
      restProductToShopifyProduct({
        id: 1,
        title: "T",
        status: "draft",
      } as Record<string, unknown>).status
    ).toBe("DRAFT");
    expect(
      restProductToShopifyProduct({
        id: 1,
        title: "T",
        status: "archived",
      } as Record<string, unknown>).status
    ).toBe("ARCHIVED");
  });

  it("preserves handle", () => {
    const result = restProductToShopifyProduct({
      id: 1,
      title: "T",
      handle: "my-product-handle",
    } as Record<string, unknown>);
    expect(result.handle).toBe("my-product-handle");
  });

  it("maps images[0].src to featuredImage.url", () => {
    const result = restProductToShopifyProduct({
      id: 1,
      title: "T",
      images: [
        {
          id: 111,
          src: "https://cdn.shopify.com/example.jpg",
          alt: "Alt text",
        },
      ],
    } as Record<string, unknown>);
    expect(result.featuredImage).toEqual({
      url: "https://cdn.shopify.com/example.jpg",
    });
  });

  it("converts flat variants array to edges/nodes", () => {
    const result = restProductToShopifyProduct({
      id: 1,
      title: "T",
      variants: [
        {
          id: 9876543210,
          title: "Default Title",
          sku: "SKU-001",
          price: "29.99",
        },
      ],
    } as Record<string, unknown>);
    expect(result.variants?.edges).toHaveLength(1);
    expect(result.variants?.edges?.[0].node).toMatchObject({
      id: "gid://shopify/ProductVariant/9876543210",
      title: "Default Title",
      sku: "SKU-001",
      price: "29.99",
    });
  });

  it("maps variant weight_unit to weightUnit", () => {
    const result = restProductToShopifyProduct({
      id: 1,
      title: "T",
      variants: [
        { id: 1, weight_unit: "g", weight: 100 },
        { id: 2, weight_unit: "kg" },
        { id: 3, weight_unit: "oz" },
        { id: 4, weight_unit: "lb" },
      ],
    } as Record<string, unknown>);
    expect(result.variants?.edges?.[0].node.weightUnit).toBe("GRAMS");
    expect(result.variants?.edges?.[1].node.weightUnit).toBe("KILOGRAMS");
    expect(result.variants?.edges?.[2].node.weightUnit).toBe("OUNCES");
    expect(result.variants?.edges?.[3].node.weightUnit).toBe("POUNDS");
  });

  it("handles missing fields with defaults", () => {
    const result = restProductToShopifyProduct({} as Record<string, unknown>);
    expect(result.id).toBe("");
    expect(result.title).toBe("");
    expect(result.descriptionHtml).toBeNull();
    expect(result.status).toBe("ACTIVE");
    expect(result.handle).toBeNull();
    expect(result.featuredImage).toBeNull();
    expect(result.variants?.edges).toEqual([]);
  });

  it("handles null values", () => {
    const result = restProductToShopifyProduct({
      id: 1,
      title: "T",
      body_html: null,
      handle: null,
      images: null,
      variants: null,
    } as Record<string, unknown>);
    expect(result.descriptionHtml).toBeNull();
    expect(result.handle).toBeNull();
    expect(result.featuredImage).toBeNull();
    expect(result.variants?.edges).toEqual([]);
  });

  it("handles empty variants array", () => {
    const result = restProductToShopifyProduct({
      id: 1,
      title: "T",
      variants: [],
    } as Record<string, unknown>);
    expect(result.variants?.edges).toEqual([]);
  });

  it("handles non-array variants", () => {
    const result = restProductToShopifyProduct({
      id: 1,
      title: "T",
      variants: "not-an-array",
    } as Record<string, unknown>);
    expect(result.variants?.edges).toEqual([]);
  });
});
