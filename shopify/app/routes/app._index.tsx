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
  options: { method?: string; body?: unknown } = {}
): Promise<{ ok: boolean; status: number; data: unknown }> {
  const url = `${baseUrl.replace(/\/$/, "")}${path}`;
  const response = await fetch(url, {
    method: options.method ?? "GET",
    headers: {
      "Content-Type": "application/json",
      Accept: "application/json",
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
  | { connected: false; connectionError?: "integration_inactive"; shop?: string; connectedAt?: string; fibermadeUrl: string }
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
      `/api/v1/shopify/status?connect_token=${connection.connectToken}&shop=${session.shop}`
    );

    if (!result.ok) {
      return connectedPayload;
    }

    const status = (result.data as { data?: { active?: boolean } } | null)?.data;
    if (status?.active === false) {
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

    const connectToken = formData.get("connectToken");
    if (typeof connectToken !== "string" || !connectToken.trim()) {
      return { intent: "connect", success: false, error: "Connect token is required.", field: "connectToken" };
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

    const connectResult = await fibermadeRequest(baseUrl, "/api/v1/shopify/connect", {
      method: "POST",
      body: {
        connect_token: connectToken.trim(),
        shop: session.shop,
        shopify_access_token: shopifyAccessToken,
      },
    });

    if (!connectResult.ok) {
      if (connectResult.status === 422 || connectResult.status === 404) {
        return {
          intent: "connect",
          success: false,
          error: "Invalid connect token. Check the token in Fibermade → Settings → Shopify API and try again.",
          field: "connectToken",
        };
      }
      const message =
        (connectResult.data as { message?: string } | null)?.message ?? "Could not reach the Fibermade API. Check your connection and try again.";
      return { intent: "connect", success: false, error: message };
    }

    const integrationId = (connectResult.data as { data?: { integration_id?: number } } | null)?.data?.integration_id;
    if (!integrationId) {
      return { intent: "connect", success: false, error: "Failed to connect: unexpected response." };
    }

    await db.fibermadeConnection.create({
      data: {
        shop: session.shop,
        connectToken: connectToken.trim(),
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
        await fibermadeRequest(baseUrl, "/api/v1/shopify/disconnect", {
          method: "POST",
          body: {
            connect_token: connection.connectToken,
            shop: connection.shop,
          },
        });
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
  const [connectToken, setConnectToken] = useState("");

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
    data?.intent === "connect" && !data.success && data.field === "connectToken"
      ? data.error
      : undefined;
  const connectError =
    data?.intent === "connect" && !data.success && data.field !== "connectToken"
      ? data.error
      : undefined;
  const disconnectError =
    data?.intent === "disconnect" && !data.success ? data.error : undefined;

  const handleConnect = (e: React.FormEvent) => {
    e.preventDefault();
    const formData = new FormData();
    formData.set("intent", "connect");
    formData.set("connectToken", connectToken);
    fetcher.submit(formData, { method: "POST" });
  };

  const handleDisconnect = () => {
    fetcher.submit({ intent: "disconnect" }, { method: "POST" });
  };

  if (connected) {
    return (
      <s-page heading="Fibermade">
        <s-section>
          <s-stack direction="block" gap="large">
            <img
              src="/logo.png"
              alt="Fibermade"
              style={{ height: "48px", display: "block" }}
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
          </s-stack>
        </s-section>

        <s-section heading="Connected to Fibermade">
          {disconnectError && (
            <s-banner tone="critical" slot="aside">
              {disconnectError}
            </s-banner>
          )}
          <s-stack direction="block" gap="large">
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
          </s-stack>
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
            style={{ height: "48px", marginBottom: "12px", display: "block" }}
          />
          <s-banner
            heading="Integration deactivated"
            tone="critical"
            slot="aside"
          >
            Reconnect using your connect token from Fibermade → Settings → Shopify, or disconnect to remove the link.
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
        <s-stack direction="block" gap="large">
          <img
            src="/logo.png"
            alt="Fibermade"
            style={{ height: "48px", display: "block" }}
          />
          <s-heading>Manage your fiber business from one place</s-heading>
          <s-unordered-list>
            <s-list-item>Keep your colorways and inventory in sync</s-list-item>
            <s-list-item>Manage collections across Shopify and Fibermade</s-list-item>
            <s-list-item>Changes in Shopify automatically reflect in Fibermade</s-list-item>
          </s-unordered-list>
        </s-stack>
      </s-section>

      <s-section heading="Connect your account">
        {connectError && (
          <s-banner tone="critical" slot="aside">
            {connectError}
          </s-banner>
        )}
        <s-stack direction="block" gap="large">
          <s-paragraph>
            You&apos;ll need your Fibermade Connect Token to link this store.{" "}
            {fibermadeUrl ? (
              <a
                href={`${fibermadeUrl}/creator/settings?tab=shopify-api`}
                target="_blank"
                rel="noreferrer"
              >
                Find it in Fibermade → Settings → Shopify →
              </a>
            ) : (
              <>Find it in Fibermade under Settings → Shopify.</>
            )}
          </s-paragraph>
          <form onSubmit={handleConnect}>
            <s-stack direction="block" gap="base">
              <s-text-field
                name="connectToken"
                label="Fibermade Connect Token"
                details="Find this in Fibermade → Settings → Shopify"
                value={connectToken}
                onChange={(e) => setConnectToken(e.currentTarget?.value ?? "")}
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
        </s-stack>
      </s-section>
    </s-page>
  );
}
