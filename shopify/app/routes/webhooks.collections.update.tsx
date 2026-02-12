import type { ActionFunctionArgs } from "react-router";
import { authenticate } from "../shopify.server";
import db from "../db.server";
import { FibermadeClient } from "../services/fibermade-client.server";
import { CollectionSyncService } from "../services/sync/collection-sync.server";
import { convertRestCollection } from "../services/sync/webhook-adapter.server";
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

    const graphqlRunner =
      session && admin ? getWebhookGraphqlRunner(admin) : undefined;
    const client = new FibermadeClient(baseUrl);
    client.setToken(connection.fibermadeApiToken);

    const shopifyCollection = convertRestCollection(
      payload as Record<string, unknown>
    );
    const collectionSync = new CollectionSyncService(
      client,
      connection.fibermadeIntegrationId,
      shop,
      graphqlRunner
    );

    await collectionSync.updateCollection(shopifyCollection);
  } catch (err) {
    console.error(
      "[webhooks.collections.update] Error:",
      err instanceof Error ? err.message : String(err)
    );
  }
  return new Response();
};
