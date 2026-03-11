import type { LoaderFunctionArgs } from "react-router";
import { Link, redirect, useLoaderData, useNavigation } from "react-router";
import { useState, useMemo } from "react";
import { authenticate } from "../shopify.server";
import db from "../db.server";
import { FibermadeClient } from "../services/fibermade-client.server";
import type { IntegrationLogData } from "../services/fibermade-client.types";
import { formatSyncedAt, formatLoggableType } from "../utils/date";

export type SyncHistoryLoaderData = {
  logs: IntegrationLogData[];
  links?: {
    first: string;
    last: string;
    prev: string | null;
    next: string | null;
  };
  meta?: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
  loadError?: string;
};

export const loader = async ({
  request,
}: LoaderFunctionArgs): Promise<SyncHistoryLoaderData | Response> => {
  const { session } = await authenticate.admin(request);
  const connection = await db.fibermadeConnection.findUnique({
    where: { shop: session.shop },
  });
  if (!connection) {
    throw redirect("/app");
  }
  const baseUrl = process.env.FIBERMADE_API_URL;
  if (!baseUrl?.trim()) {
    throw redirect("/app");
  }
  const url = new URL(request.url);
  const page = Math.max(1, parseInt(url.searchParams.get("page") ?? "1", 10) || 1);
  const perPage = 50;

  const client = new FibermadeClient(baseUrl);
  client.setToken(connection.fibermadeApiToken);

  try {
    const response = await client.getIntegrationLogs(
      connection.fibermadeIntegrationId,
      { page, per_page: perPage }
    );
    return {
      logs: response.data,
      links: response.links,
      meta: response.meta,
    };
  } catch {
    return {
      logs: [],
      loadError:
        "Could not load sync history. Check your connection and try again.",
    };
  }
};

type StatusFilter = "all" | "success" | "error" | "warning";

function statusBadgeTone(
  status: string
): "success" | "critical" | "warning" | "info" {
  switch (status.toLowerCase()) {
    case "success":
      return "success";
    case "error":
      return "critical";
    case "warning":
      return "warning";
    default:
      return "info";
  }
}

export default function SyncHistoryRoute() {
  const { logs, links, meta, loadError } = useLoaderData<SyncHistoryLoaderData>();
  const navigation = useNavigation();
  const [statusFilter, setStatusFilter] = useState<StatusFilter>("all");

  const filteredLogs = useMemo(() => {
    if (statusFilter === "all") return logs;
    return logs.filter((log) => log.status === statusFilter);
  }, [logs, statusFilter]);

  const isLoading = navigation.state === "loading";
  const hasPrev = meta && meta.current_page > 1;
  const hasNext = meta && meta.current_page < meta.last_page;

  return (
    <s-page heading="Sync History">
      {loadError && (
        <s-banner tone="critical" slot="aside">
          {loadError}
        </s-banner>
      )}
      {isLoading && (
        <s-section>
          <s-paragraph>Loading…</s-paragraph>
        </s-section>
      )}
      {!isLoading && logs.length === 0 && (
        <s-section>
          <s-card>
            <s-paragraph>No sync history yet.</s-paragraph>
          </s-card>
        </s-section>
      )}
      {!isLoading && logs.length > 0 && (
        <>
          <s-section heading="Filter">
            <s-stack direction="inline" gap="base">
              <label>
                Status:{" "}
                <select
                  value={statusFilter}
                  onChange={(e) =>
                    setStatusFilter(e.target.value as StatusFilter)
                  }
                  aria-label="Filter by status"
                >
                  <option value="all">All</option>
                  <option value="success">Success</option>
                  <option value="error">Error</option>
                  <option value="warning">Warning</option>
                </select>
              </label>
            </s-stack>
          </s-section>
          <s-section heading="Sync history">
            <s-card>
              <table style={{ width: "100%", borderCollapse: "collapse" }}>
                <thead>
                  <tr>
                    <th style={{ textAlign: "left", padding: "0.5rem" }}>
                      Status
                    </th>
                    <th style={{ textAlign: "left", padding: "0.5rem" }}>
                      Resource Type
                    </th>
                    <th style={{ textAlign: "left", padding: "0.5rem" }}>
                      Message
                    </th>
                    <th style={{ textAlign: "left", padding: "0.5rem" }}>
                      Synced At
                    </th>
                  </tr>
                </thead>
                <tbody>
                  {filteredLogs.map((log) => (
                    <SyncHistoryRow key={log.id} log={log} />
                  ))}
                </tbody>
              </table>
              {filteredLogs.length === 0 && (
                <s-paragraph>No logs match the selected filter.</s-paragraph>
              )}
              {meta && meta.last_page > 1 && (
                <s-stack direction="inline" gap="base" style={{ marginTop: "1rem" }}>
                  {hasPrev ? (
                    <Link to={`/app/sync-history?page=${meta.current_page - 1}`}>
                      Previous
                    </Link>
                  ) : (
                    <span aria-hidden>Previous</span>
                  )}
                  <span>
                    Page {meta.current_page} of {meta.last_page}
                    {meta.total != null && ` (${meta.total} total)`}
                  </span>
                  {hasNext ? (
                    <Link to={`/app/sync-history?page=${meta.current_page + 1}`}>
                      Next
                    </Link>
                  ) : (
                    <span aria-hidden>Next</span>
                  )}
                </s-stack>
              )}
            </s-card>
          </s-section>
        </>
      )}
    </s-page>
  );
}

function SyncHistoryRow({ log }: { log: IntegrationLogData }) {
  const tone = statusBadgeTone(log.status);
  return (
    <tr>
      <td style={{ padding: "0.5rem" }}>
        <span data-status-tone={tone}>{log.status}</span>
      </td>
      <td style={{ padding: "0.5rem" }}>
        {formatLoggableType(log.loggable_type ?? null)}
      </td>
      <td style={{ padding: "0.5rem" }}>{log.message ?? "—"}</td>
      <td style={{ padding: "0.5rem" }}>
        {formatSyncedAt(log.synced_at)}
      </td>
    </tr>
  );
}
