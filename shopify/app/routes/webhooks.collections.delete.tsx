import type { ActionFunctionArgs } from "react-router";
import { authenticate } from "../shopify.server";
import db from "../db.server";
import { FibermadeClient } from "../services/fibermade-client.server";
import { EXTERNAL_TYPES, IDENTIFIABLE_TYPES } from "../services/sync/constants";
import { findFibermadeIdByShopifyGid } from "../services/sync/mapping.server";

export const action = async ({ request }: ActionFunctionArgs) => {
  try {
    const { shop, payload } = await authenticate.webhook(request);

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

    const rawPayload = payload as { id?: number };
    const collectionId = rawPayload?.id;
    if (collectionId == null) {
      return new Response();
    }

    const collectionGid = `gid://shopify/Collection/${collectionId}`;
    const client = new FibermadeClient(baseUrl);
    client.setToken(connection.fibermadeApiToken);

    const result = await findFibermadeIdByShopifyGid(
      client,
      connection.fibermadeIntegrationId,
      EXTERNAL_TYPES.SHOPIFY_COLLECTION,
      collectionGid
    );

    if (!result || result.identifiableType !== IDENTIFIABLE_TYPES.COLLECTION) {
      return new Response();
    }

    await client.updateCollection(result.identifiableId, {
      status: "retired",
    });

    await client.createIntegrationLog(connection.fibermadeIntegrationId, {
      loggable_type: IDENTIFIABLE_TYPES.COLLECTION,
      loggable_id: result.identifiableId,
      status: "success",
      message: `Retired Collection #${result.identifiableId} (Shopify collection ${collectionId} deleted)`,
      metadata: {
        shopify_collection_id: collectionId,
        shopify_gid: collectionGid,
      },
      synced_at: new Date().toISOString(),
    });
  } catch (err) {
    console.error(
      "[webhooks.collections.delete] Error:",
      err instanceof Error ? err.message : String(err)
    );
  }
  return new Response();
};
