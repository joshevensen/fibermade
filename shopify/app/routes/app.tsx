import type { HeadersFunction, LoaderFunctionArgs } from "react-router";
import { Outlet, useLoaderData, useRouteError } from "react-router";
import { boundary } from "@shopify/shopify-app-react-router/server";
import { AppProvider } from "@shopify/shopify-app-react-router/react";

import { authenticate, sessionStorage } from "../shopify.server";
import db from "../db.server";

export const loader = async ({ request }: LoaderFunctionArgs) => {
  const { session } = await authenticate.admin(request);

  const baseUrl = process.env.FIBERMADE_API_URL?.replace(/\/$/, "");
  if (baseUrl) {
    const connection = await db.fibermadeConnection.findUnique({
      where: { shop: session.shop },
    });
    if (connection) {
      // Prefer the long-lived offline token; fall back to the online session
      // token so Fibermade always has something valid even before the offline
      // session is written to storage (e.g. after a fresh deploy).
      const offlineSession = await sessionStorage.loadSession(`offline_${session.shop}`);
      const currentToken = offlineSession?.accessToken ?? session.accessToken;
      if (currentToken) {
        fetch(`${baseUrl}/api/v1/shopify/refresh-token`, {
          method: "POST",
          headers: { "Content-Type": "application/json", Accept: "application/json" },
          body: JSON.stringify({
            connect_token: connection.connectToken,
            shop: session.shop,
            shopify_access_token: currentToken,
            shopify_refresh_token: offlineSession?.refreshToken ?? null,
          }),
        }).catch(() => {
          // Non-fatal — token will be refreshed on the next page load
        });
      }
    }
  }

  return {
    apiKey: process.env.SHOPIFY_API_KEY || "",
  };
};

export default function App() {
  const { apiKey } = useLoaderData<typeof loader>();

  return (
    <AppProvider embedded apiKey={apiKey}>
      <Outlet />
    </AppProvider>
  );
}

// Shopify needs React Router to catch some thrown responses, so that their headers are included in the response.
export function ErrorBoundary() {
  return boundary.error(useRouteError());
}

export const headers: HeadersFunction = (headersArgs) => {
  return boundary.headers(headersArgs);
};
