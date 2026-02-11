import { METAFIELD_KEYS, METAFIELD_NAMESPACE } from "./constants";

const METAFIELDS_SET_MUTATION = `
  mutation metafieldsSet($metafields: [MetafieldsSetInput!]!) {
    metafieldsSet(metafields: $metafields) {
      metafields {
        id
        namespace
        key
        value
      }
      userErrors {
        field
        message
      }
    }
  }
`;

export type ShopifyGraphqlRunner = (
  query: string,
  variables: unknown
) => Promise<{ data?: unknown; errors?: unknown }>;

export interface VariantMetafieldInput {
  variantGid: string;
  baseId: number;
}

/**
 * Writes fibermade.colorway_id on the product and fibermade.base_id on each variant
 * via Shopify Admin GraphQL metafieldsSet. Does not throw on failure; logs and returns.
 */
export async function setProductAndVariantMetafields(
  graphql: ShopifyGraphqlRunner,
  productGid: string,
  colorwayId: number,
  variantInputs: VariantMetafieldInput[]
): Promise<void> {
  const metafields: Array<{
    ownerId: string;
    namespace: string;
    key: string;
    value: string;
    type: string;
  }> = [
    {
      ownerId: productGid,
      namespace: METAFIELD_NAMESPACE,
      key: METAFIELD_KEYS.COLORWAY_ID,
      value: String(colorwayId),
      type: "number_integer",
    },
    ...variantInputs.map(({ variantGid, baseId }) => ({
      ownerId: variantGid,
      namespace: METAFIELD_NAMESPACE,
      key: METAFIELD_KEYS.BASE_ID,
      value: String(baseId),
      type: "number_integer" as const,
    })),
  ];

  try {
    const result = await graphql(METAFIELDS_SET_MUTATION, { metafields });
    const setResult = (result.data as { metafieldsSet?: { userErrors?: Array<{ field?: string; message?: string }> } })
      ?.metafieldsSet;
    const userErrors = setResult?.userErrors ?? [];
    if (userErrors.length > 0) {
      const messages = userErrors.map((e) => e.message ?? "").join("; ");
      console.error(
        `metafields.server: metafieldsSet userErrors for ${productGid}:`,
        messages
      );
    }
  } catch (err) {
    console.error(
      `metafields.server: metafieldsSet failed for ${productGid}:`,
      err instanceof Error ? err.message : String(err)
    );
  }
}
