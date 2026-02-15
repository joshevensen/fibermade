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
import { EXTERNAL_TYPES } from "../services/sync/constants";
import { mappingExists } from "../services/sync/mapping.server";
import type { ShopifyGraphqlRunner } from "../services/sync/metafields.server";
import { ProductSyncService } from "../services/sync/product-sync.server";
import type {
  BulkImportProgress,
  ShopifyProduct,
  ShopifyVariant,
  ShopifyWeightUnit,
} from "../services/sync/types";
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
  integrationActive?: boolean;
  integrationUpdatedAt?: string;
  initialImportStatus?: string;
  initialImportProgress?: BulkImportProgress;
};

export type DisconnectActionData = { success: true } | { success: false; error: string };

export type ResyncAllResult =
  | { success: true; progress: BulkImportProgress }
  | { success: false; error: string };

export type SyncProductResult =
  | { success: true; productTitle: string }
  | { success: false; error: string };

export type IndexActionData =
  | DisconnectActionData
  | ResyncAllResult
  | SyncProductResult;

const PRODUCT_BY_ID_QUERY = `#graphql
  query GetProduct($id: ID!) {
    product(id: $id) {
      id
      title
      descriptionHtml
      status
      handle
      featuredImage { url }
      variants(first: 100) {
        edges {
          node {
            id
            title
            sku
            price
            weight
            weightUnit
          }
        }
      }
      images(first: 10) {
        edges {
          node {
            id
            url
            altText
          }
        }
      }
    }
  }
`;

function parseProductIdToGid(value: string | null): string | null {
  const v = (value ?? "").trim();
  if (!v) return null;
  const gidMatch = v.match(/^gid:\/\/shopify\/Product\/(\d+)$/);
  if (gidMatch) return gidMatch[0];
  if (/^\d+$/.test(v)) return `gid://shopify/Product/${v}`;
  const urlMatch = v.match(/\/admin\/products\/(\d+)/);
  if (urlMatch) return `gid://shopify/Product/${urlMatch[1]}`;
  return null;
}

function normalizeProductNode(node: unknown): ShopifyProduct {
  const n = node as Record<string, unknown>;
  const variants = n.variants as { edges?: Array<{ node: Record<string, unknown> }> } | undefined;
  const variantEdges = variants?.edges ?? [];
  return {
    id: String(n.id ?? ""),
    title: String(n.title ?? ""),
    descriptionHtml: n.descriptionHtml != null ? String(n.descriptionHtml) : null,
    status: (n.status as ShopifyProduct["status"]) ?? "ACTIVE",
    handle: n.handle != null ? String(n.handle) : null,
    featuredImage:
      n.featuredImage != null && typeof n.featuredImage === "object" && "url" in n.featuredImage
        ? { url: String((n.featuredImage as { url: string }).url) }
        : null,
    variants: {
      edges: variantEdges.map((e) => ({
        node: normalizeVariantNode(e.node),
      })),
    },
  };
}

function normalizeVariantNode(node: Record<string, unknown>): ShopifyVariant {
  return {
    id: String(node.id ?? ""),
    title: String(node.title ?? ""),
    sku: node.sku != null ? String(node.sku) : null,
    price: String(node.price ?? ""),
    weight: typeof node.weight === "number" ? node.weight : null,
    weightUnit: (node.weightUnit as ShopifyWeightUnit | null) ?? null,
  };
}

async function ensureConnectionConnected(
  connection: { fibermadeIntegrationId: number; fibermadeApiToken: string } | null
): Promise<{ connected: boolean }> {
  if (!connection) return { connected: false };
  const baseUrl = process.env.FIBERMADE_API_URL;
  if (!baseUrl?.trim()) return { connected: true };
  try {
    const client = new FibermadeClient(baseUrl);
    client.setToken(connection.fibermadeApiToken);
    const integration = await client.getIntegration(connection.fibermadeIntegrationId);
    return { connected: integration.active === true };
  } catch (e) {
    if (e instanceof FibermadeAuthError || e instanceof FibermadeNotFoundError) {
      return { connected: false };
    }
    return { connected: true };
  }
}

export const action = async ({
  request,
}: ActionFunctionArgs): Promise<IndexActionData> => {
  if (request.method !== "POST") {
    return { success: false, error: "Method not allowed" };
  }

  const formData = await request.formData();
  const intent = formData.get("intent");
  if (intent !== "disconnect" && intent !== "resync-all" && intent !== "sync-product") {
    return { success: false, error: "Invalid intent" };
  }

  if (intent === "disconnect") {
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
  }

  const { session, admin } = await authenticate.admin(request);
  const connection = await db.fibermadeConnection.findUnique({
    where: { shop: session.shop },
  });

  const { connected } = await ensureConnectionConnected(connection);
  if (!connection || !connected) {
    return { success: false, error: "Not connected to Fibermade." };
  }

  const baseUrl = process.env.FIBERMADE_API_URL;
  if (!baseUrl?.trim()) {
    return { success: false, error: "Fibermade API is not configured." };
  }

  if (intent === "resync-all") {
    if (connection.initialImportStatus === "in_progress") {
      return { success: false, error: "Import is already in progress" };
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
  }

  if (intent === "sync-product") {
    const productIdInput = formData.get("productId");
    const productGid = parseProductIdToGid(
      typeof productIdInput === "string" ? productIdInput : null
    );
    if (!productGid) {
      return { success: false, error: "Invalid product ID or URL" };
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
      return { data: json.data, errors: json.errors };
    };

    const result = await graphqlRunner(PRODUCT_BY_ID_QUERY, { id: productGid });
    const data = result.data as { product?: unknown } | null;
    const productNode = data?.product;
    if (!productNode) {
      return { success: false, error: "Product not found" };
    }

    const product = normalizeProductNode(productNode);

    const client = new FibermadeClient(baseUrl);
    client.setToken(connection.fibermadeApiToken);
    const productSync = new ProductSyncService(
      client,
      connection.fibermadeIntegrationId,
      session.shop,
      graphqlRunner
    );

    try {
      const exists = await mappingExists(
        client,
        connection.fibermadeIntegrationId,
        EXTERNAL_TYPES.SHOPIFY_PRODUCT,
        productGid
      );
      if (exists) {
        await productSync.updateProduct(product);
      } else {
        await productSync.importProduct(product);
      }
      return { success: true, productTitle: product.title ?? "Product" };
    } catch (err) {
      const message = err instanceof Error ? err.message : String(err);
      return { success: false, error: message };
    }
  }

  return { success: false, error: "Invalid intent" };
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
    return {
      ...connectionPayload,
      integrationActive: integration.active,
      integrationUpdatedAt: integration.updated_at,
    };
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
    integrationActive,
    integrationUpdatedAt,
    initialImportStatus,
    initialImportProgress,
  } = useLoaderData<typeof loader>();
  const fetcher = useFetcher<IndexActionData>();
  const importFetcher = useFetcher<{ error?: string }>();
  const resyncFetcher = useFetcher<IndexActionData>();
  const syncProductFetcher = useFetcher<IndexActionData>();
  const navigate = useNavigate();
  const shopify = useAppBridge();

  useEffect(() => {
    const data = fetcher.data;
    const isDisconnectSuccess =
      data &&
      "success" in data &&
      data.success === true &&
      !("progress" in data) &&
      !("productTitle" in data);
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
  const isImporting =
    importFetcher.state === "loading" || importFetcher.state === "submitting";
  const isResyncing =
    resyncFetcher.state === "loading" || resyncFetcher.state === "submitting";
  const isSyncingProduct =
    syncProductFetcher.state === "loading" ||
    syncProductFetcher.state === "submitting";

  const handleDisconnect = () => {
    fetcher.submit({ intent: "disconnect" }, { method: "POST" });
  };

  const handleResyncConfirm = () => {
    resyncFetcher.submit({ intent: "resync-all" }, { method: "POST" });
  };

  const integrationStatusText =
    integrationActive === true
      ? "Active"
      : integrationActive === false
        ? "Inactive"
        : "—";
  const integrationUpdatedText = integrationUpdatedAt
    ? formatConnectedAt(new Date(integrationUpdatedAt))
    : "—";

  return (
    <s-page heading="Home">
      {!showDisconnected && (
        <s-section heading="Connection status">
          {shop && (
            <s-paragraph>
              <strong>Shopify store domain:</strong> {shop}
            </s-paragraph>
          )}
          <s-paragraph>
            <strong>Integration status:</strong> {integrationStatusText}
          </s-paragraph>
          {connectedAt && (
            <s-paragraph>
              <strong>Connected since:</strong>{" "}
              {formatConnectedAt(new Date(connectedAt))}
            </s-paragraph>
          )}
          <s-paragraph>
            <strong>Integration last updated:</strong> {integrationUpdatedText}
          </s-paragraph>
        </s-section>
      )}

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

      {!showDisconnected && connected && (
        <s-section heading="Sync">
          {resyncFetcher.data && "success" in resyncFetcher.data && !resyncFetcher.data.success && (
            <s-banner tone="critical" slot="aside">
              {resyncFetcher.data.error}
            </s-banner>
          )}
          {resyncFetcher.data &&
            "success" in resyncFetcher.data &&
            resyncFetcher.data.success &&
            "progress" in resyncFetcher.data &&
            resyncFetcher.data.progress && (
              <s-banner tone="success" slot="aside">
                Re-sync complete. {resyncFetcher.data.progress.imported} imported
                {resyncFetcher.data.progress.failed > 0 &&
                  `, ${resyncFetcher.data.progress.failed} failed`}
                .
              </s-banner>
            )}
          <s-paragraph>
            Re-import all products and collections from Shopify, or sync a single
            product by ID or GID.
          </s-paragraph>
          <s-button
            variant="secondary"
            disabled={initialImportStatus === "in_progress" || isResyncing}
            loading={isResyncing}
            commandFor="resync-modal"
            command="--show"
          >
            {isResyncing ? "Re-syncing…" : "Re-sync All Products"}
          </s-button>

          {syncProductFetcher.data &&
            "success" in syncProductFetcher.data &&
            !syncProductFetcher.data.success && (
              <s-banner tone="critical" slot="aside">
                {syncProductFetcher.data.error}
              </s-banner>
            )}
          {syncProductFetcher.data &&
            "success" in syncProductFetcher.data &&
            syncProductFetcher.data.success &&
            "productTitle" in syncProductFetcher.data && (
              <s-banner tone="success" slot="aside">
                Synced product: {syncProductFetcher.data.productTitle}
              </s-banner>
            )}
          <syncProductFetcher.Form method="post">
            <input type="hidden" name="intent" value="sync-product" />
            <input
              type="text"
              name="productId"
              placeholder="Product ID or GID"
              aria-label="Product ID or GID"
            />
            <s-button
              type="submit"
              variant="secondary"
              disabled={isSyncingProduct}
              loading={isSyncingProduct}
            >
              {isSyncingProduct ? "Syncing…" : "Sync Product"}
            </s-button>
          </syncProductFetcher.Form>
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

      <s-modal id="resync-modal" heading="Re-sync all products">
        <s-paragraph>
          This will re-import all products and collections from Shopify into
          Fibermade. Continue?
        </s-paragraph>
        <s-button
          slot="primary-action"
          variant="primary"
          onClick={() => handleResyncConfirm()}
          loading={isResyncing}
        >
          Re-sync All
        </s-button>
        <s-button
          slot="secondary-actions"
          variant="secondary"
          commandFor="resync-modal"
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
