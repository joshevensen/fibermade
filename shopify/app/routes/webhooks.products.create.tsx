import type { ActionFunctionArgs } from "react-router";
import { authenticate } from "../shopify.server";
import db from "../db.server";
import { FibermadeClient } from "../services/fibermade-client.server";
import { ProductSyncService } from "../services/sync/product-sync.server";
import { restProductToShopifyProduct } from "../services/sync/webhook-adapter.server";
import { getWebhookGraphqlRunner } from "../services/sync/webhook-context.server";

export const action = async ({ request }: ActionFunctionArgs) => {
  try {
    const { shop, session, admin, payload } =
      await authenticate.webhook(request);

    const connection = await db.fibermadeConnection.findUnique({
      where: { shop },
    });
    if (!connection) {
      return new Response();
    }

    const baseUrl = process.env.FIBERMADE_API_URL;
    if (!baseUrl?.trim()) {
      return new Response();
    }

    const graphqlRunner = session && admin ? getWebhookGraphqlRunner(admin) : undefined;
    const client = new FibermadeClient(baseUrl);
    client.setToken(connection.fibermadeApiToken);

    const product = restProductToShopifyProduct(
      payload as Record<string, unknown>
    );
    const productSync = new ProductSyncService(
      client,
      connection.fibermadeIntegrationId,
      shop,
      graphqlRunner
    );

    await productSync.importProduct(product);
  } catch (err) {
    console.error(
      "[webhooks.products.create] Error:",
      err instanceof Error ? err.message : String(err)
    );
  }
  return new Response();
};
