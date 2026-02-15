import type { LoaderFunctionArgs } from "react-router";
import { redirect, useLoaderData, useNavigation } from "react-router";
import { useState, useMemo } from "react";
import { authenticate } from "../shopify.server";
import db from "../db.server";
import { FibermadeClient } from "../services/fibermade-client.server";
import type { IntegrationLogData } from "../services/fibermade-client.types";
import { formatSyncedAt, formatLoggableType } from "../utils/date";

export type SyncHistoryLoaderData = {
  logs: IntegrationLogData[];
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
  const client = new FibermadeClient(baseUrl);
  client.setToken(connection.fibermadeApiToken);
  const response = await client.getIntegrationLogs(
    connection.fibermadeIntegrationId,
    { limit: 100 }
  );
  return { logs: response.data };
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
  const { logs } = useLoaderData<SyncHistoryLoaderData>();
  const navigation = useNavigation();
  const [statusFilter, setStatusFilter] = useState<StatusFilter>("all");

  const filteredLogs = useMemo(() => {
    if (statusFilter === "all") return logs;
    return logs.filter((log) => log.status === statusFilter);
  }, [logs, statusFilter]);

  const isLoading = navigation.state === "loading";

  return (
    <s-page heading="Sync History">
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
