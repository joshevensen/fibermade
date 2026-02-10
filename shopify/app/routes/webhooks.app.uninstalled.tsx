import type { ActionFunctionArgs } from "react-router";
import { authenticate } from "../shopify.server";
import db from "../db.server";
import { FibermadeClient } from "../services/fibermade-client.server";

export const action = async ({ request }: ActionFunctionArgs) => {
  const { shop, session, topic } = await authenticate.webhook(request);

  console.log(`Received ${topic} webhook for ${shop}`);

  const connection = await db.fibermadeConnection.findUnique({
    where: { shop },
  });

  if (connection) {
    const baseUrl = process.env.FIBERMADE_API_URL;
    if (baseUrl?.trim()) {
      try {
        const client = new FibermadeClient(baseUrl);
        client.setToken(connection.fibermadeApiToken);
        await client.updateIntegration(connection.fibermadeIntegrationId, {
          active: false,
        });
      } catch (e) {
        console.error(
          `[webhooks.app.uninstalled] Failed to deactivate Fibermade Integration ${connection.fibermadeIntegrationId} for ${shop}:`,
          e
        );
      }
    }
    await db.fibermadeConnection.delete({
      where: { id: connection.id },
    });
  }

  if (session) {
    await db.session.deleteMany({ where: { shop } });
  }

  return new Response();
};
