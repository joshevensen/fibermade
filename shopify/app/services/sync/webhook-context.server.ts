import type { ShopifyGraphqlRunner } from "./metafields.server";

export interface WebhookAdminContext {
  graphql: (
    query: string,
    options?: { variables?: unknown }
  ) => Promise<Response>;
}

export function getWebhookGraphqlRunner(
  admin: WebhookAdminContext | undefined
): ShopifyGraphqlRunner | undefined {
  if (!admin?.graphql) return undefined;
  return async (query, variables) => {
    const response = await admin.graphql(query, { variables });
    const json = (await response.json()) as {
      data?: unknown;
      errors?: unknown;
    };
    if (!response.ok) {
      const err = new Error(
        typeof json?.errors === "string" ? json.errors : "GraphQL request failed"
      ) as Error & { status?: number };
      (err as Error & { status?: number }).status = response.status;
      throw err;
    }
    return { data: json.data, errors: json.errors };
  };
}
