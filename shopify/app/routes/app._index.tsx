import { useEffect } from "react";
import type {
  ActionFunctionArgs,
  HeadersFunction,
  LoaderFunctionArgs,
} from "react-router";
import { Navigate, useFetcher, useLoaderData, useNavigate } from "react-router";
import { useAppBridge } from "@shopify/app-bridge-react";
import { authenticate } from "../shopify.server";
import db from "../db.server";
import { FibermadeClient } from "../services/fibermade-client.server";
import { FibermadeAuthError, FibermadeNotFoundError } from "../services/fibermade-client.types";
import type { BulkImportProgress } from "../services/sync/types";
import { formatConnectedAt } from "../utils/date";
import { boundary } from "@shopify/shopify-app-react-router/server";

function parseImportProgress(json: string | null): BulkImportProgress {
  if (!json?.trim()) return { total: 0, imported: 0, failed: 0 };
  try {
    const parsed = JSON.parse(json) as BulkImportProgress;
    return {
      total: Number(parsed.total) || 0,
      imported: Number(parsed.imported) || 0,
      failed: Number(parsed.failed) || 0,
      errors: Array.isArray(parsed.errors) ? parsed.errors : undefined,
    };
  } catch {
    return { total: 0, imported: 0, failed: 0 };
  }
}

export type ConnectionStatus = {
  connected: boolean;
  connectionError?: "integration_inactive" | "token_invalid";
  shop?: string;
  connectedAt?: string;
  initialImportStatus?: string;
  initialImportProgress?: BulkImportProgress;
};

export type DisconnectActionData = { success: true } | { success: false; error: string };

export const action = async ({
  request,
}: ActionFunctionArgs): Promise<DisconnectActionData> => {
  if (request.method !== "POST") {
    return { success: false, error: "Method not allowed" };
  }

  const formData = await request.formData();
  if (formData.get("intent") !== "disconnect") {
    return { success: false, error: "Invalid intent" };
  }

  const { session } = await authenticate.admin(request);
  const connection = await db.fibermadeConnection.findUnique({
    where: { shop: session.shop },
  });

  if (!connection) {
    return { success: true };
  }

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
        `[disconnect] Failed to deactivate Fibermade Integration ${connection.fibermadeIntegrationId}:`,
        e
      );
    }
  }

  await db.fibermadeConnection.delete({
    where: { id: connection.id },
  });

  return { success: true };
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
  const connectionPayload = {
    connected: true as const,
    shop: connection.shop,
    connectedAt: connection.connectedAt.toISOString(),
    initialImportStatus: connection.initialImportStatus,
    initialImportProgress: parseImportProgress(connection.initialImportProgress),
  };

  if (!baseUrl?.trim()) {
    return connectionPayload;
  }

  try {
    const client = new FibermadeClient(baseUrl);
    client.setToken(connection.fibermadeApiToken);
    const integration = await client.getIntegration(connection.fibermadeIntegrationId);
    if (!integration.active) {
      return {
        connected: false,
        connectionError: "integration_inactive",
        shop: connection.shop,
        connectedAt: connection.connectedAt.toISOString(),
      };
    }
    return connectionPayload;
  } catch (e) {
    if (e instanceof FibermadeAuthError) {
      return {
        connected: false,
        connectionError: "token_invalid",
        shop: connection.shop,
        connectedAt: connection.connectedAt.toISOString(),
      };
    }
    if (e instanceof FibermadeNotFoundError) {
      return {
        connected: false,
        connectionError: "integration_inactive",
        shop: connection.shop,
        connectedAt: connection.connectedAt.toISOString(),
      };
    }
    return connectionPayload;
  }
};

export default function Index() {
  const {
    connected,
    connectionError,
    shop,
    connectedAt,
    initialImportStatus,
    initialImportProgress,
  } = useLoaderData<typeof loader>();
  const fetcher = useFetcher<DisconnectActionData>();
  const importFetcher = useFetcher<{ error?: string }>();
  const navigate = useNavigate();
  const shopify = useAppBridge();

  useEffect(() => {
    const data = fetcher.data;
    if (data?.success) {
      shopify.toast.show("Disconnected from Fibermade");
      navigate("/app/connect", { replace: true });
    }
  }, [fetcher.data, navigate, shopify]);

  if (!connected && !connectionError) {
    return <Navigate to="/app/connect" replace />;
  }

  const showDisconnected = !connected && !!connectionError;
  const isDisconnecting =
    (fetcher.state === "loading" || fetcher.state === "submitting") &&
    fetcher.formMethod === "POST";
  const isImporting =
    importFetcher.state === "loading" || importFetcher.state === "submitting";

  const handleDisconnect = () => {
    fetcher.submit({ intent: "disconnect" }, { method: "POST" });
  };

  return (
    <s-page heading="Home">
      {!showDisconnected &&
        initialImportStatus === "pending" &&
        connected && (
          <s-section heading="Import your products">
            <s-paragraph>
              Import your existing Shopify products into Fibermade.
            </s-paragraph>
            {importFetcher.data?.error && (
              <s-banner tone="critical" slot="aside">
                {importFetcher.data.error}
              </s-banner>
            )}
            <importFetcher.Form method="post" action="/app/import">
              <s-button
                type="submit"
                variant="primary"
                disabled={isImporting}
                loading={isImporting}
              >
                {isImporting ? "Importing…" : "Import your products"}
              </s-button>
            </importFetcher.Form>
          </s-section>
        )}

      {!showDisconnected &&
        initialImportStatus === "in_progress" &&
        connected &&
        initialImportProgress && (
          <s-section heading="Import in progress">
            <s-paragraph>
              {initialImportProgress.imported + initialImportProgress.failed} of{" "}
              {initialImportProgress.total} products processed (
              {initialImportProgress.imported} imported,{" "}
              {initialImportProgress.failed} failed).
            </s-paragraph>
          </s-section>
        )}

      {!showDisconnected &&
        initialImportStatus === "complete" &&
        connected &&
        initialImportProgress && (
          <s-section heading="Import complete">
            <s-paragraph>
              {initialImportProgress.imported} products imported
              {initialImportProgress.failed > 0 &&
                `, ${initialImportProgress.failed} failed`}
              .
            </s-paragraph>
          </s-section>
        )}

      {!showDisconnected &&
        initialImportStatus === "failed" &&
        connected && (
          <s-section heading="Import failed">
            <s-paragraph>
              The import did not complete. You can retry; already-imported
              products will be skipped.
            </s-paragraph>
            {importFetcher.data?.error && (
              <s-banner tone="critical" slot="aside">
                {importFetcher.data.error}
              </s-banner>
            )}
            <importFetcher.Form method="post" action="/app/import">
              <s-button
                type="submit"
                variant="primary"
                disabled={isImporting}
                loading={isImporting}
              >
                {isImporting ? "Retrying…" : "Retry import"}
              </s-button>
            </importFetcher.Form>
          </s-section>
        )}

      {showDisconnected && (
        <s-banner
          heading={
            connectionError === "token_invalid"
              ? "API token no longer valid"
              : "Integration deactivated"
          }
          tone="critical"
          slot="aside"
        >
          Reconnect with a new API token from the Fibermade platform, or
          disconnect to remove the link.
          <s-button
            slot="secondary-actions"
            variant="secondary"
            onClick={() => handleDisconnect()}
            loading={isDisconnecting}
          >
            Reconnect
          </s-button>
          <s-button
            slot="secondary-actions"
            variant="secondary"
            tone="critical"
            commandFor="disconnect-modal"
            command="--show"
          >
            Disconnect
          </s-button>
        </s-banner>
      )}

      {!showDisconnected && (
        <s-section heading="Connected to Fibermade">
          {shop && (
            <s-paragraph>
              <strong>{shop}</strong>
              {connectedAt &&
                ` — connected ${formatConnectedAt(new Date(connectedAt))}`}
            </s-paragraph>
          )}
          <s-paragraph>
            This store is linked to your Fibermade account. You can manage
            products and orders from here.
          </s-paragraph>
          <s-button
            variant="secondary"
            tone="critical"
            commandFor="disconnect-modal"
            command="--show"
          >
            Disconnect
          </s-button>
        </s-section>
      )}

      <s-modal id="disconnect-modal" heading="Disconnect from Fibermade">
        <s-paragraph>
          Are you sure? This will remove the connection between your Shopify
          store and Fibermade account.
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

export const headers: HeadersFunction = (headersArgs) => {
  return boundary.headers(headersArgs);
};
