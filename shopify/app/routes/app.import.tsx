import type { ActionFunctionArgs, LoaderFunctionArgs } from "react-router";
import { redirect, useFetcher, useLoaderData } from "react-router";
import { authenticate } from "../shopify.server";
import db from "../db.server";
import { FibermadeClient } from "../services/fibermade-client.server";
import { BulkImportService } from "../services/sync/bulk-import.server";
import type { BulkImportProgress } from "../services/sync/types";
import type { ShopifyGraphqlRunner } from "../services/sync/metafields.server";

function parseProgress(json: string | null): BulkImportProgress {
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

export type ImportLoaderData = {
  initialImportStatus: string;
  initialImportProgress: BulkImportProgress;
};

export const loader = async ({
  request,
}: LoaderFunctionArgs): Promise<ImportLoaderData | Response> => {
  const { session } = await authenticate.admin(request);
  const connection = await db.fibermadeConnection.findUnique({
    where: { shop: session.shop },
  });
  if (!connection) {
    throw redirect("/app");
  }
  return {
    initialImportStatus: connection.initialImportStatus,
    initialImportProgress: parseProgress(connection.initialImportProgress),
  };
};

export const action = async ({
  request,
}: ActionFunctionArgs): Promise<Response | { error: string }> => {
  if (request.method !== "POST") {
    return { error: "Method not allowed" };
  }

  const { session, admin } = await authenticate.admin(request);
  const connection = await db.fibermadeConnection.findUnique({
    where: { shop: session.shop },
  });
  if (!connection) {
    throw redirect("/app");
  }

  if (connection.initialImportStatus === "in_progress") {
    return { error: "Import is already in progress" };
  }

  const baseUrl = process.env.FIBERMADE_API_URL;
  if (!baseUrl?.trim()) {
    return { error: "Fibermade API is not configured." };
  }

  const graphqlRunner: ShopifyGraphqlRunner = async (query, variables) => {
    const response = await admin.graphql(query, { variables });
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

  await bulkImport.runImport();
  throw redirect("/app");
};

export default function ImportRoute() {
  const { initialImportStatus, initialImportProgress } =
    useLoaderData<ImportLoaderData>();
  const fetcher = useFetcher<{ error?: string }>();
  const isSubmitting =
    fetcher.state === "loading" || fetcher.state === "submitting";

  return (
    <s-page heading="Import products">
      {fetcher.data?.error && (
        <s-banner tone="critical">{fetcher.data.error}</s-banner>
      )}
      {initialImportStatus === "pending" && (
        <s-card>
          <p>Import your Shopify products into Fibermade.</p>
          <fetcher.Form method="post">
            <s-button type="submit" disabled={isSubmitting}>
              {isSubmitting ? "Importing…" : "Start import"}
            </s-button>
          </fetcher.Form>
        </s-card>
      )}
      {initialImportStatus === "in_progress" && (
        <s-card>
          <p>Import in progress…</p>
          <p>
            {initialImportProgress.imported + initialImportProgress.failed} of{" "}
            {initialImportProgress.total} processed ({initialImportProgress.imported}{" "}
            imported, {initialImportProgress.failed} failed).
          </p>
        </s-card>
      )}
      {initialImportStatus === "complete" && (
        <s-card>
          <p>Import complete.</p>
          <p>
            {initialImportProgress.imported} imported,{" "}
            {initialImportProgress.failed} failed.
          </p>
        </s-card>
      )}
      {initialImportStatus === "failed" && (
        <s-card>
          <p>Import failed.</p>
          <fetcher.Form method="post">
            <s-button type="submit" disabled={isSubmitting}>
              {isSubmitting ? "Retrying…" : "Retry"}
            </s-button>
          </fetcher.Form>
        </s-card>
      )}
    </s-page>
  );
}
