import type { FibermadeClient } from "../fibermade-client.server";
import type { BaseData, ColorwayData } from "../fibermade-client.types";
import {
  EXTERNAL_TYPES,
  IDENTIFIABLE_TYPES,
  METAFIELD_KEYS,
  METAFIELD_NAMESPACE,
} from "./constants";
import type { ShopifyGraphqlRunner } from "./metafields.server";
import {
  createMapping,
  findShopifyGidByFibermadeId,
} from "./mapping.server";
import { parseNumericIdFromGid } from "./product-sync.server";
import type { ProductImageResult, ProductPushResult } from "./types";

const STATUS_MAP: Record<string, "ACTIVE" | "DRAFT" | "ARCHIVED"> = {
  active: "ACTIVE",
  idea: "DRAFT",
  retired: "ARCHIVED",
};

const PRODUCT_CREATE_MUTATION = `
  mutation productCreate($product: ProductCreateInput!) {
    productCreate(product: $product) {
      product {
        id
        handle
        variants(first: 100) {
          edges {
            node {
              id
            }
          }
        }
      }
      userErrors {
        field
        message
      }
    }
  }
`;

const PRODUCT_CREATE_MEDIA_MUTATION = `
  mutation productCreateMedia($productId: ID!, $media: [CreateMediaInput!]!) {
    productCreateMedia(productId: $productId, media: $media) {
      media {
        ... on MediaImage {
          id
          image {
            url
          }
        }
      }
      mediaUserErrors {
        field
        message
        code
      }
      product {
        id
      }
    }
  }
`;

interface ProductCreateVariantInput {
  optionValues?: { optionName: string; name: string }[];
  sku?: string;
  price: string;
  metafields?: { namespace: string; key: string; value: string; type: string }[];
}

interface ProductCreateInput {
  title: string;
  descriptionHtml: string;
  status: "ACTIVE" | "DRAFT" | "ARCHIVED";
  metafields?: { namespace: string; key: string; value: string; type: string }[];
  productOptions?: { name: string; values: { name: string }[] }[];
  variants?: ProductCreateVariantInput[];
}

function mapStatus(status: string): "ACTIVE" | "DRAFT" | "ARCHIVED" {
  return status in STATUS_MAP ? STATUS_MAP[status] : "ACTIVE";
}

function isPubliclyAccessibleUrl(url: string): boolean {
  try {
    const imageUrl = new URL(url);
    const hostname = imageUrl.hostname.toLowerCase();

    // Reject localhost variants
    if (
      hostname === "localhost" ||
      hostname === "127.0.0.1" ||
      hostname === "::1" ||
      hostname.startsWith("127.")
    ) {
      return false;
    }

    // Reject private IP ranges
    if (hostname.startsWith("10.")) {
      return false;
    }
    if (hostname.startsWith("192.168.")) {
      return false;
    }
    if (hostname.startsWith("172.")) {
      const parts = hostname.split(".");
      if (parts.length >= 2) {
        const secondOctet = parseInt(parts[1], 10);
        if (secondOctet >= 16 && secondOctet <= 31) {
          return false;
        }
      }
    }

    // Allow URLs matching Fibermade platform domain
    const fibermadeApiUrl = process.env.FIBERMADE_API_URL;
    if (fibermadeApiUrl) {
      try {
        const fibermadeUrl = new URL(fibermadeApiUrl);
        if (hostname === fibermadeUrl.hostname.toLowerCase()) {
          return true;
        }
      } catch {
        // Invalid FIBERMADE_API_URL, continue with other checks
      }
    }

    // If it's not localhost/private IP and has a valid domain, allow it
    return true;
  } catch {
    // Invalid URL format
    return false;
  }
}

function buildProductInput(colorway: ColorwayData, inventories: { inventoryId: number; base: BaseData }[]): ProductCreateInput {
  const title = colorway.name?.trim() || "Untitled";
  const descriptionHtml = colorway.description ?? "";
  const status = mapStatus(colorway.status);

  const productInput: ProductCreateInput = {
    title,
    descriptionHtml,
    status,
    metafields: [
      {
        namespace: METAFIELD_NAMESPACE,
        key: METAFIELD_KEYS.COLORWAY_ID,
        value: String(colorway.id),
        type: "number_integer",
      },
    ],
  };

  if (inventories.length === 0) {
    const variantTitle = colorway.name?.trim() || "Default";
    productInput.productOptions = [{ name: "Base", values: [{ name: variantTitle }] }];
    productInput.variants = [
      {
        optionValues: [{ optionName: "Base", name: variantTitle }],
        sku: "",
        price: "0",
        metafields: [],
      },
    ];
  } else {
    const optionValues = inventories.map(({ base }) => ({ name: base.descriptor?.trim() || "Default" }));
    productInput.productOptions = [{ name: "Base", values: optionValues }];
    productInput.variants = inventories.map(({ base }) => ({
      optionValues: [
        {
          optionName: "Base",
          name: base.descriptor?.trim() || "Default",
        },
      ],
      sku: base.code ?? "",
      price: base.retail_price ?? "0",
      metafields: [
        {
          namespace: METAFIELD_NAMESPACE,
          key: METAFIELD_KEYS.BASE_ID,
          value: String(base.id),
          type: "number_integer",
        },
      ],
    }));
  }

  return productInput;
}

export class ProductPushService {
  constructor(
    private readonly client: FibermadeClient,
    private readonly integrationId: number,
    private readonly shopDomain: string,
    private readonly graphql: ShopifyGraphqlRunner
  ) {}

  async pushColorway(colorwayId: number): Promise<ProductPushResult> {
    const existingGid = await findShopifyGidByFibermadeId(
      this.client,
      this.integrationId,
      IDENTIFIABLE_TYPES.COLORWAY,
      colorwayId,
      EXTERNAL_TYPES.SHOPIFY_PRODUCT
    );
    if (existingGid) {
      return {
        shopifyProductGid: existingGid,
        colorwayId,
        variantMappings: [],
        skipped: true,
      };
    }

    const colorway = await this.client.getColorway(colorwayId);
    const inventories = colorway.inventories ?? [];

    const inventoryBases: { inventoryId: number; base: BaseData }[] = [];
    for (const inv of inventories) {
      const base = inv.base;
      if (!base) {
        const baseData = await this.client.getBase(inv.base_id);
        inventoryBases.push({ inventoryId: inv.id, base: baseData });
      } else {
        inventoryBases.push({ inventoryId: inv.id, base });
      }
    }

    const productInput = buildProductInput(colorway, inventoryBases);
    const result = await this.graphql(PRODUCT_CREATE_MUTATION, { product: productInput });

    const productCreate = (result.data as { productCreate?: { product?: { id: string; handle?: string; variants?: { edges: { node: { id: string } }[] } }; userErrors?: Array<{ field?: string[]; message: string }> } })?.productCreate;
    const userErrors = productCreate?.userErrors ?? [];
    if (userErrors.length > 0) {
      const message = userErrors.map((e) => e.message).join("; ");
      await this.logIntegration(colorwayId, "error", message, {
        colorway_id: colorwayId,
        user_errors: userErrors,
      });
      throw new Error(`productCreate failed: ${message}`);
    }

    const product = productCreate?.product;
    if (!product?.id) {
      await this.logIntegration(colorwayId, "error", "productCreate returned no product", {
        colorway_id: colorwayId,
      });
      throw new Error("productCreate returned no product");
    }

    const variantEdges = product.variants?.edges ?? [];
    const productNumericId = parseNumericIdFromGid(product.id);

    await createMapping(
      this.client,
      this.integrationId,
      IDENTIFIABLE_TYPES.COLORWAY,
      colorwayId,
      EXTERNAL_TYPES.SHOPIFY_PRODUCT,
      product.id,
      {
        admin_url: `https://${this.shopDomain}/admin/products/${productNumericId}`,
        shopify_handle: product.handle ?? undefined,
      }
    );

    const variantMappings: { variantGid: string; inventoryId: number }[] = [];
    const productNumericIdForVariant = parseNumericIdFromGid(product.id);

    for (let i = 0; i < variantEdges.length; i++) {
      const variantNode = variantEdges[i]?.node;
      if (!variantNode) continue;

      const inventory = inventoryBases[i];
      if (inventory) {
        const variantNumericId = parseNumericIdFromGid(variantNode.id);
        await createMapping(
          this.client,
          this.integrationId,
          IDENTIFIABLE_TYPES.INVENTORY,
          inventory.inventoryId,
          EXTERNAL_TYPES.SHOPIFY_VARIANT,
          variantNode.id,
          {
            admin_url: `https://${this.shopDomain}/admin/products/${productNumericIdForVariant}/variants/${variantNumericId}`,
          }
        );
        variantMappings.push({ variantGid: variantNode.id, inventoryId: inventory.inventoryId });
      }
    }

    // Push image if available
    let imageGid: string | undefined;
    let imageError: string | undefined;

    if (colorway.primary_image_url) {
      const imagePushResult = await this.pushImage(product.id, colorway.primary_image_url);
      if (imagePushResult.success) {
        imageGid = imagePushResult.mediaGid;
      } else {
        imageError = imagePushResult.error;
        // If URL validation failed, log a warning
        if (imagePushResult.error === "Image URL not publicly accessible (localhost)") {
          await this.logIntegration(
            colorwayId,
            "warning",
            `Image URL is not publicly accessible (localhost detected). Skipping image push.`,
            {
              colorway_id: colorwayId,
              shopify_gid: product.id,
              image_error: imagePushResult.error,
            }
          );
        }
      }
    }

    const productName = colorway.name?.trim() || "Untitled";
    const message = `Pushed Colorway '${productName}' (#${colorwayId}) to Shopify as product ${product.id}`;
    const metadata: Record<string, unknown> = {
      shopify_gid: product.id,
      variant_count: variantMappings.length,
    };

    // Include image result in metadata if image push was attempted
    if (colorway.primary_image_url) {
      metadata.image_result = {
        success: imageGid !== undefined,
        ...(imageGid && { media_gid: imageGid }),
        ...(imageError && { error: imageError }),
      };
    }

    await this.logIntegration(colorwayId, "success", message, metadata);

    return {
      shopifyProductGid: product.id,
      colorwayId,
      variantMappings,
      ...(imageGid && { imageGid }),
      ...(imageError && { imageError }),
    };
  }

  private async pushImage(productGid: string, imageUrl: string): Promise<ProductImageResult> {
    // Validate URL is publicly accessible
    if (!isPubliclyAccessibleUrl(imageUrl)) {
      return {
        success: false,
        error: "Image URL not publicly accessible (localhost)",
      };
    }

    try {
      const result = await this.graphql(PRODUCT_CREATE_MEDIA_MUTATION, {
        productId: productGid,
        media: [{ originalSource: imageUrl, mediaContentType: "IMAGE" }],
      });

      const productCreateMedia = (result.data as {
        productCreateMedia?: {
          media?: Array<{ id: string; image?: { url: string } }>;
          mediaUserErrors?: Array<{ field?: string[]; message: string; code?: string }>;
          product?: { id: string };
        };
      })?.productCreateMedia;

      const mediaUserErrors = productCreateMedia?.mediaUserErrors ?? [];
      if (mediaUserErrors.length > 0) {
        const errorMessages = mediaUserErrors.map(
          (e) => `Shopify error: ${e.code ?? "UNKNOWN"} - ${e.message}`
        );
        return {
          success: false,
          error: errorMessages.join("; "),
        };
      }

      const media = productCreateMedia?.media;
      if (!media || media.length === 0) {
        return {
          success: false,
          error: "productCreateMedia returned no media",
        };
      }

      const mediaGid = media[0]?.id;
      if (!mediaGid) {
        return {
          success: false,
          error: "productCreateMedia returned media without id",
        };
      }

      return {
        success: true,
        mediaGid,
      };
    } catch (error) {
      const errorMessage = error instanceof Error ? error.message : String(error);
      return {
        success: false,
        error: errorMessage,
      };
    }
  }

  private async logIntegration(
    colorwayId: number,
    status: "success" | "error" | "warning",
    message: string,
    metadata: Record<string, unknown>
  ): Promise<void> {
    await this.client.createIntegrationLog(this.integrationId, {
      loggable_type: IDENTIFIABLE_TYPES.COLORWAY,
      loggable_id: colorwayId,
      status,
      message,
      metadata,
      synced_at: new Date().toISOString(),
    });
  }
}
