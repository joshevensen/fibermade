import type { HeadersFunction, LoaderFunctionArgs } from "react-router";
import { Navigate, useLoaderData } from "react-router";
import { authenticate } from "../shopify.server";
import db from "../db.server";
import { FibermadeClient } from "../services/fibermade-client.server";
import { FibermadeAuthError, FibermadeNotFoundError } from "../services/fibermade-client.types";
import { boundary } from "@shopify/shopify-app-react-router/server";

export type ConnectionStatus = {
  connected: boolean;
  connectionError?: "integration_inactive" | "token_invalid";
};

export const loader = async ({
  request,
}: LoaderFunctionArgs): Promise<ConnectionStatus> => {
  const { session } = await authenticate.admin(request);
  const connection = await db.fibermadeConnection.findUnique({
    where: { shop: session.shop },
  });

  if (!connection) {
    return { connected: false };
  }

  const baseUrl = process.env.FIBERMADE_API_URL;
  if (!baseUrl?.trim()) {
    return { connected: true };
  }

  try {
    const client = new FibermadeClient(baseUrl);
    client.setToken(connection.fibermadeApiToken);
    const integration = await client.getIntegration(connection.fibermadeIntegrationId);
    if (!integration.active) {
      return { connected: false, connectionError: "integration_inactive" };
    }
    return { connected: true };
  } catch (e) {
    if (e instanceof FibermadeAuthError) {
      return { connected: false, connectionError: "token_invalid" };
    }
    if (e instanceof FibermadeNotFoundError) {
      return { connected: false, connectionError: "integration_inactive" };
    }
    return { connected: true };
  }
};

export default function Index() {
  const { connected, connectionError } = useLoaderData<typeof loader>();

  if (!connected && !connectionError) {
    return <Navigate to="/app/connect" replace />;
  }

  const showDisconnected = !connected && !!connectionError;

  return (
    <s-page heading="Home">
      {showDisconnected && (
        <s-banner tone="critical" slot="aside">
          {connectionError === "token_invalid"
            ? "Your Fibermade API token is no longer valid. Please reconnect your account."
            : "Your Fibermade integration is no longer active. Please reconnect your account."}
        </s-banner>
      )}
      <s-section heading="Connected to Fibermade">
        <s-paragraph>
          This store is linked to your Fibermade account. You can manage
          products and orders from here.
        </s-paragraph>
      </s-section>
    </s-page>
  );
}

export const headers: HeadersFunction = (headersArgs) => {
  return boundary.headers(headersArgs);
};
