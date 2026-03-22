import { useEffect, useState } from "react";
import type {
  ActionFunctionArgs,
  HeadersFunction,
  LoaderFunctionArgs,
} from "react-router";
import { useFetcher, useLoaderData, useNavigate } from "react-router";
import { useAppBridge } from "@shopify/app-bridge-react";
import { authenticate } from "../shopify.server";
import db from "../db.server";
import { boundary } from "@shopify/shopify-app-react-router/server";

export const headers: HeadersFunction = (headersArgs) => {
  return boundary.headers(headersArgs);
};

async function fibermadeRequest(
  baseUrl: string,
  path: string,
  token: string,
  options: { method?: string; body?: unknown } = {}
): Promise<{ ok: boolean; status: number; data: unknown }> {
  const url = `${baseUrl.replace(/\/$/, "")}${path}`;
  const response = await fetch(url, {
    method: options.method ?? "GET",
    headers: {
      "Content-Type": "application/json",
      Accept: "application/json",
      Authorization: `Bearer ${token}`,
    },
    ...(options.body !== undefined ? { body: JSON.stringify(options.body) } : {}),
  });
  let data: unknown = null;
  const contentType = response.headers.get("content-type") ?? "";
  if (contentType.includes("application/json")) {
    const text = await response.text();
    if (text.trim()) {
      try {
        data = JSON.parse(text);
      } catch {
        // ignore parse errors
      }
    }
  }
  return { ok: response.ok, status: response.status, data };
}

function formatConnectedAt(date: Date): string {
  return new Intl.DateTimeFormat(undefined, { dateStyle: "medium" }).format(date);
}

export type ConnectionStatus =
  | { connected: false; connectionError?: "integration_inactive" | "token_invalid"; shop?: string; connectedAt?: string; fibermadeUrl: string }
  | { connected: true; shop: string; connectedAt: string; fibermadeUrl: string };

export type ConnectActionData =
  | { intent: "connect"; success: true }
  | { intent: "connect"; success: false; error: string; field?: string }
  | { intent: "disconnect"; success: true }
  | { intent: "disconnect"; success: false; error: string };

export const loader = async ({ request }: LoaderFunctionArgs): Promise<ConnectionStatus> => {
  const { session } = await authenticate.admin(request);
  const fibermadeUrl = process.env.FIBERMADE_URL?.replace(/\/$/, "") ?? "";

  const connection = await db.fibermadeConnection.findUnique({
    where: { shop: session.shop },
  });

  if (!connection) {
    return { connected: false, fibermadeUrl };
  }

  const connectedPayload = {
    connected: true as const,
    shop: connection.shop,
    connectedAt: connection.connectedAt.toISOString(),
    fibermadeUrl,
  };

  const baseUrl = process.env.FIBERMADE_API_URL;
  if (!baseUrl?.trim()) {
    return connectedPayload;
  }

  try {
    const result = await fibermadeRequest(
      baseUrl,
      `/api/v1/integrations/${connection.fibermadeIntegrationId}`,
      connection.fibermadeApiToken
    );

    if (result.status === 401 || result.status === 403) {
      return {
        connected: false,
        connectionError: "token_invalid",
        shop: connection.shop,
        connectedAt: connection.connectedAt.toISOString(),
        fibermadeUrl,
      };
    }

    if (result.status === 404) {
      return {
        connected: false,
        connectionError: "integration_inactive",
        shop: connection.shop,
        connectedAt: connection.connectedAt.toISOString(),
        fibermadeUrl,
      };
    }

    if (!result.ok) {
      return connectedPayload;
    }

    const integration = (result.data as { data?: { active?: boolean } } | null)?.data;
    if (integration && integration.active === false) {
      return {
        connected: false,
        connectionError: "integration_inactive",
        shop: connection.shop,
        connectedAt: connection.connectedAt.toISOString(),
        fibermadeUrl,
      };
    }

    return connectedPayload;
  } catch {
    return connectedPayload;
  }
};

export const action = async ({ request }: ActionFunctionArgs): Promise<ConnectActionData> => {
  if (request.method !== "POST") {
    return { intent: "disconnect", success: false, error: "Method not allowed" };
  }

  const { session } = await authenticate.admin(request);
  const formData = await request.formData();
  const intent = formData.get("intent");

  if (intent === "connect") {
    const shopifyAccessToken = session.accessToken;
    if (typeof shopifyAccessToken !== "string" || !shopifyAccessToken) {
      return { intent: "connect", success: false, error: "Shopify session is missing access token." };
    }

    const apiToken = formData.get("apiToken");
    if (typeof apiToken !== "string" || !apiToken.trim()) {
      return { intent: "connect", success: false, error: "API token is required.", field: "apiToken" };
    }

    const existing = await db.fibermadeConnection.findUnique({ where: { shop: session.shop } });
    if (existing) {
      return {
        intent: "connect",
        success: false,
        error: "This shop is already linked to a Fibermade account. Disconnect first to link a different account.",
        field: "shop",
      };
    }

    const baseUrl = process.env.FIBERMADE_API_URL;
    if (!baseUrl?.trim()) {
      return { intent: "connect", success: false, error: "Fibermade API is not configured. Please contact support." };
    }

    const healthResult = await fibermadeRequest(baseUrl, "/api/v1/health", apiToken.trim());
    if (healthResult.status === 401 || healthResult.status === 403) {
      return {
        intent: "connect",
        success: false,
        error: "Invalid Fibermade API token. Check your credentials and try again.",
        field: "apiToken",
      };
    }
    if (!healthResult.ok) {
      return { intent: "connect", success: false, error: "Could not reach the Fibermade API. Check your connection and try again." };
    }

    const createResult = await fibermadeRequest(baseUrl, "/api/v1/integrations", apiToken.trim(), {
      method: "POST",
      body: {
        type: "shopify",
        credentials: shopifyAccessToken,
        settings: { shop: session.shop },
        active: true,
      },
    });

    if (!createResult.ok) {
      const message =
        (createResult.data as { message?: string } | null)?.message ?? "Failed to create integration.";
      return { intent: "connect", success: false, error: message };
    }

    const integrationId = (createResult.data as { data?: { id?: number } } | null)?.data?.id;
    if (!integrationId) {
      return { intent: "connect", success: false, error: "Failed to create integration: unexpected response." };
    }

    await db.fibermadeConnection.create({
      data: {
        shop: session.shop,
        fibermadeApiToken: apiToken.trim(),
        fibermadeIntegrationId: integrationId,
        connectedAt: new Date(),
      },
    });

    return { intent: "connect", success: true };
  }

  if (intent === "disconnect") {
    const connection = await db.fibermadeConnection.findUnique({ where: { shop: session.shop } });
    if (!connection) {
      return { intent: "disconnect", success: true };
    }

    const baseUrl = process.env.FIBERMADE_API_URL;
    if (baseUrl?.trim()) {
      try {
        await fibermadeRequest(
          baseUrl,
          `/api/v1/integrations/${connection.fibermadeIntegrationId}`,
          connection.fibermadeApiToken,
          { method: "PATCH", body: { active: false } }
        );
      } catch (e) {
        console.error(
          `[disconnect] Failed to deactivate integration ${connection.fibermadeIntegrationId}:`,
          e
        );
      }
    }

    await db.fibermadeConnection.delete({ where: { id: connection.id } });
    return { intent: "disconnect", success: true };
  }

  return { intent: "disconnect", success: false, error: "Invalid intent." };
};

export default function Index() {
  const loaderData = useLoaderData<typeof loader>();
  const fetcher = useFetcher<ConnectActionData>();
  const navigate = useNavigate();
  const shopify = useAppBridge();
  const [token, setToken] = useState("");

  const data = fetcher.data;
  const submittingIntent =
    fetcher.state !== "idle" ? fetcher.formData?.get("intent") : null;
  const isConnecting = submittingIntent === "connect";
  const isDisconnecting = submittingIntent === "disconnect";

  useEffect(() => {
    if (!data?.success) return;
    if (data.intent === "connect") {
      shopify.toast.show("Connected to Fibermade");
      navigate("/app", { replace: true });
    } else if (data.intent === "disconnect") {
      shopify.toast.show("Disconnected from Fibermade");
      navigate("/app", { replace: true });
    }
  }, [data, navigate, shopify]);

  const { connected, fibermadeUrl } = loaderData;
  const connectionError = !connected ? loaderData.connectionError : undefined;
  const shop = loaderData.shop;
  const connectedAt = loaderData.connectedAt;

  const tokenError =
    data?.intent === "connect" && !data.success && data.field === "apiToken"
      ? data.error
      : undefined;
  const connectError =
    data?.intent === "connect" && !data.success && data.field !== "apiToken"
      ? data.error
      : undefined;
  const disconnectError =
    data?.intent === "disconnect" && !data.success ? data.error : undefined;

  const handleConnect = (e: React.FormEvent) => {
    e.preventDefault();
    const formData = new FormData();
    formData.set("intent", "connect");
    formData.set("apiToken", token);
    fetcher.submit(formData, { method: "POST" });
  };

  const handleDisconnect = () => {
    fetcher.submit({ intent: "disconnect" }, { method: "POST" });
  };

  if (connected) {
    return (
      <s-page heading="Fibermade">
        <s-section>
          <img
            src="/logo.png"
            alt="Fibermade"
            style={{ height: "40px", marginBottom: "12px", display: "block" }}
          />
          <s-paragraph>
            Fibermade is a commerce platform built for the fiber community.
          </s-paragraph>
          {fibermadeUrl && (
            <s-paragraph>
              <a
                href={`${fibermadeUrl}/creator/settings?tab=shopify-api`}
                target="_blank"
                rel="noreferrer"
              >
                Log in to Fibermade →
              </a>
            </s-paragraph>
          )}
        </s-section>

        <s-section heading="Connected to Fibermade">
          {disconnectError && (
            <s-banner tone="critical" slot="aside">
              {disconnectError}
            </s-banner>
          )}
          <s-paragraph>
            <strong>{shop}</strong>
            {connectedAt && ` — connected ${formatConnectedAt(new Date(connectedAt))}`}
          </s-paragraph>
          <s-paragraph>This store is linked to your Fibermade account.</s-paragraph>
          <s-button
            variant="secondary"
            tone="critical"
            commandFor="disconnect-modal"
            command="--show"
          >
            Disconnect
          </s-button>
        </s-section>

        <s-modal id="disconnect-modal" heading="Disconnect from Fibermade">
          <s-paragraph>
            Are you sure? This will remove the connection between your Shopify store and Fibermade
            account.
          </s-paragraph>
          <s-button
            slot="primary-action"
            variant="primary"
            tone="critical"
            onClick={() => handleDisconnect()}
            loading={isDisconnecting}
          >
            Disconnect
          </s-button>
          <s-button
            slot="secondary-actions"
            variant="secondary"
            commandFor="disconnect-modal"
            command="--hide"
          >
            Cancel
          </s-button>
        </s-modal>
      </s-page>
    );
  }

  if (connectionError) {
    return (
      <s-page heading="Fibermade">
        <s-section>
          <img
            src="/logo.png"
            alt="Fibermade"
            style={{ height: "40px", marginBottom: "12px", display: "block" }}
          />
          <s-banner
            heading={
              connectionError === "token_invalid"
                ? "API token no longer valid"
                : "Integration deactivated"
            }
            tone="critical"
            slot="aside"
          >
            Reconnect with a new API token from the Fibermade platform, or disconnect to remove the
            link.
          </s-banner>
          <s-paragraph>
            {shop} — disconnected
          </s-paragraph>
          <s-button
            variant="secondary"
            tone="critical"
            onClick={() => handleDisconnect()}
            loading={isDisconnecting}
          >
            Disconnect
          </s-button>
        </s-section>
      </s-page>
    );
  }

  return (
    <s-page heading="Fibermade">
      <s-section>
        <img
          src="/logo.png"
          alt="Fibermade"
          style={{ height: "40px", marginBottom: "12px", display: "block" }}
        />
        <s-text>Manage your fiber business from one place</s-text>
        <s-list>
          <s-list-item>Keep your colorways and inventory in sync</s-list-item>
          <s-list-item>Manage collections across Shopify and Fibermade</s-list-item>
          <s-list-item>Changes in Shopify automatically reflect in Fibermade</s-list-item>
        </s-list>
        {connectError && (
          <s-banner tone="critical" slot="aside">
            {connectError}
          </s-banner>
        )}
        <form onSubmit={handleConnect}>
          <s-stack direction="block" gap="base">
            <s-text-field
              name="apiToken"
              label="Fibermade API token"
              value={token}
              onChange={(e) => setToken(e.currentTarget?.value ?? "")}
              autocomplete="off"
              error={tokenError}
              disabled={isConnecting}
            />
            <s-button type="submit" variant="primary" loading={isConnecting}>
              Connect Fibermade account
            </s-button>
          </s-stack>
        </form>
        {fibermadeUrl && (
          <s-paragraph>
            Don&apos;t have an account?{" "}
            <a href={`${fibermadeUrl}/register`} target="_blank" rel="noreferrer">
              Sign up at fibermade.app →
            </a>
          </s-paragraph>
        )}
      </s-section>
    </s-page>
  );
}
