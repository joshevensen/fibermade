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
import { BulkImportService } from "../services/sync/bulk-import.server";
import {
  assertNoGraphqlErrors,
  type ShopifyGraphqlRunner,
} from "../services/sync/metafields.server";
import type { BulkImportProgress } from "../services/sync/types";
import { formatConnectedAt } from "../utils/date";
import { boundary } from "@shopify/shopify-app-react-router/server";

export type ConnectionStatus = {
  connected: boolean;
  connectionError?: "integration_inactive" | "token_invalid";
  shop?: string;
  connectedAt?: string;
  fibermadeUrl: string;
};

export type DisconnectActionData = { success: true } | { success: false; error: string };

export type SyncAllResult =
  | { success: true; progress: BulkImportProgress }
  | { success: false; error: string };

export type IndexActionData = DisconnectActionData | SyncAllResult;

export const headers: HeadersFunction = (headersArgs) => {
  return boundary.headers(headersArgs);
};

export const loader = async ({
  request,
}: LoaderFunctionArgs): Promise<ConnectionStatus> => {
  const { session } = await authenticate.admin(request);
  const fibermadeUrl = process.env.FIBERMADE_URL?.replace(/\/$/, "") || "";

  const connection = await db.fibermadeConnection.findUnique({
    where: { shop: session.shop },
  });

  if (!connection) {
    return { connected: false, fibermadeUrl };
  }

  const baseUrl = process.env.FIBERMADE_API_URL;
  const connectionPayload = {
    connected: true as const,
    shop: connection.shop,
    connectedAt: connection.connectedAt.toISOString(),
    fibermadeUrl,
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
        fibermadeUrl,
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
        fibermadeUrl,
      };
    }
    if (e instanceof FibermadeNotFoundError) {
      return {
        connected: false,
        connectionError: "integration_inactive",
        shop: connection.shop,
        connectedAt: connection.connectedAt.toISOString(),
        fibermadeUrl,
      };
    }
    return connectionPayload;
  }
};

export const action = async ({
  request,
}: ActionFunctionArgs): Promise<IndexActionData> => {
  if (request.method !== "POST") {
    return { success: false, error: "Method not allowed" };
  }

  const formData = await request.formData();
  const intent = formData.get("intent");

  if (intent !== "disconnect" && intent !== "sync-all") {
    return { success: false, error: "Invalid intent" };
  }

  const { session, admin } = await authenticate.admin(request);
  const connection = await db.fibermadeConnection.findUnique({
    where: { shop: session.shop },
  });

  if (intent === "disconnect") {
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
  }

  // sync-all
  if (!connection) {
    return { success: false, error: "Not connected to Fibermade." };
  }

  const baseUrl = process.env.FIBERMADE_API_URL;
  if (!baseUrl?.trim()) {
    return { success: false, error: "Fibermade API is not configured." };
  }

  const graphqlRunner: ShopifyGraphqlRunner = async (query, variables) => {
    const response = await admin.graphql(query, {
      variables: variables as Record<string, unknown>,
    });
    const json = (await response.json()) as { data?: unknown; errors?: unknown };
    if (!response.ok) {
      const err = new Error(
        typeof json?.errors === "string" ? json.errors : "GraphQL request failed"
      ) as Error & { status?: number };
      err.status = response.status;
      throw err;
    }
    assertNoGraphqlErrors(json);
    return { data: json.data, errors: json.errors };
  };

  const client = new FibermadeClient(baseUrl);
  client.setToken(connection.fibermadeApiToken);

  const updateConnection = async (data: {
    initialImportStatus: string;
    initialImportProgress?: string | null;
  }) => {
    await db.fibermadeConnection.update({
      where: { id: connection.id },
      data: {
        initialImportStatus: data.initialImportStatus,
        initialImportProgress: data.initialImportProgress ?? undefined,
      },
    });
  };

  const bulkImport = new BulkImportService(
    client,
    connection.fibermadeIntegrationId,
    session.shop,
    graphqlRunner,
    updateConnection
  );

  try {
    const progress = await bulkImport.runImport();
    return { success: true, progress };
  } catch (err) {
    const message = err instanceof Error ? err.message : String(err);
    return { success: false, error: message };
  }
};

export default function Index() {
  const { connected, connectionError, shop, connectedAt, fibermadeUrl } =
    useLoaderData<typeof loader>();
  const fetcher = useFetcher<IndexActionData>();
  const syncFetcher = useFetcher<IndexActionData>();
  const navigate = useNavigate();
  const shopify = useAppBridge();

  useEffect(() => {
    const data = fetcher.data;
    const isDisconnectSuccess =
      data &&
      "success" in data &&
      data.success === true &&
      !("progress" in data);
    if (isDisconnectSuccess) {
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
  const isSyncing =
    syncFetcher.state === "loading" || syncFetcher.state === "submitting";

  const handleDisconnect = () => {
    fetcher.submit({ intent: "disconnect" }, { method: "POST" });
  };

  const handleSyncConfirm = () => {
    syncFetcher.submit({ intent: "sync-all" }, { method: "POST" });
  };

  const syncResult = syncFetcher.data;
  const syncSuccess =
    syncResult && "success" in syncResult && syncResult.success && "progress" in syncResult
      ? syncResult
      : null;
  const syncError =
    syncResult && "success" in syncResult && !syncResult.success && "error" in syncResult
      ? syncResult.error
      : null;

  return (
    <s-page heading="Fibermade">
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
            onClick={() => navigate("/app/connect", { replace: true })}
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

      <s-section>
        <img
          src="/logo.png"
          alt="Fibermade"
          style={{ height: "40px", marginBottom: "12px", display: "block" }}
        />
        <s-paragraph>
          Fibermade is a commerce platform built for the fiber community. Manage
          your colorways, bases, and inventory — then sync your products directly
          to your Shopify store.
        </s-paragraph>
        {fibermadeUrl && (
          <s-paragraph>
            <a href={`${fibermadeUrl}/login`} target="_blank" rel="noreferrer">
              Log in to Fibermade →
            </a>
          </s-paragraph>
        )}

        {syncError && (
          <s-banner tone="critical" slot="aside">
            Sync failed: {syncError}
          </s-banner>
        )}
        {syncSuccess && (
          <s-banner tone="success" slot="aside">
            Sync complete — {syncSuccess.progress.imported} products imported
            {syncSuccess.progress.failed > 0 &&
              `, ${syncSuccess.progress.failed} failed`}
            .
          </s-banner>
        )}

        {!showDisconnected && (
          <s-button
            variant="primary"
            disabled={isSyncing}
            loading={isSyncing}
            commandFor="sync-modal"
            command="--show"
          >
            {isSyncing ? "Syncing…" : "Sync Products to Fibermade"}
          </s-button>
        )}
      </s-section>

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
            This store is linked to your Fibermade account.
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

      <s-modal id="sync-modal" heading="Sync products to Fibermade">
        <s-paragraph>
          This will import all products and collections from your Shopify store
          into Fibermade. Already-imported products will be updated.
        </s-paragraph>
        <s-button
          slot="primary-action"
          variant="primary"
          onClick={() => handleSyncConfirm()}
          loading={isSyncing}
        >
          Sync Products
        </s-button>
        <s-button
          slot="secondary-actions"
          variant="secondary"
          commandFor="sync-modal"
          command="--hide"
        >
          Cancel
        </s-button>
      </s-modal>

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
