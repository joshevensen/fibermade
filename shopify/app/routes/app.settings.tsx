import type {
  ActionFunctionArgs,
  LoaderFunctionArgs,
} from "react-router";
import { redirect, useFetcher, useLoaderData } from "react-router";
import { authenticate } from "../shopify.server";
import db from "../db.server";
import { FibermadeClient } from "../services/fibermade-client.server";
import type { CollectionData, IntegrationData } from "../services/fibermade-client.types";

export type SettingsActionData =
  | { success: true }
  | { success: false; error: string };

export type SettingsLoaderData = {
  integration: IntegrationData;
  collections: CollectionData[];
  autoSync: boolean;
  excludedCollectionIds: number[];
};

function parseSettings(integration: IntegrationData | null) {
  const settings = integration?.settings ?? ({} as Record<string, unknown>);
  const autoSync = settings.auto_sync !== false;
  const excludedCollectionIds = Array.isArray(settings.excluded_collection_ids)
    ? (settings.excluded_collection_ids as unknown[]).map(Number).filter(Number.isInteger)
    : [];
  return { autoSync, excludedCollectionIds };
}

export const loader = async ({
  request,
}: LoaderFunctionArgs): Promise<SettingsLoaderData | Response> => {
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

  const [integration, collectionsResponse] = await Promise.all([
    client.getIntegration(connection.fibermadeIntegrationId),
    client.listCollections({ limit: 100 }),
  ]);

  const { autoSync, excludedCollectionIds } = parseSettings(integration);

  return {
    integration,
    collections: collectionsResponse.data,
    autoSync,
    excludedCollectionIds,
  };
};

export const action = async ({
  request,
}: ActionFunctionArgs): Promise<SettingsActionData> => {
  if (request.method !== "POST") {
    return { success: false, error: "Method not allowed" };
  }

  const formData = await request.formData();
  const intent = formData.get("intent");
  if (intent !== "save-settings") {
    return { success: false, error: "Invalid intent" };
  }

  const { session } = await authenticate.admin(request);
  const connection = await db.fibermadeConnection.findUnique({
    where: { shop: session.shop },
  });
  if (!connection) {
    return { success: false, error: "Not connected to Fibermade." };
  }

  const baseUrl = process.env.FIBERMADE_API_URL;
  if (!baseUrl?.trim()) {
    return { success: false, error: "Fibermade API is not configured." };
  }

  const client = new FibermadeClient(baseUrl);
  client.setToken(connection.fibermadeApiToken);

  const [integration, collectionsResponse] = await Promise.all([
    client.getIntegration(connection.fibermadeIntegrationId),
    client.listCollections({ limit: 100 }),
  ]);

  const currentSettings = (integration?.settings ?? {}) as Record<string, unknown>;
  const autoSync = formData.get("autoSync") === "on";
  const collections = collectionsResponse.data;
  const excludedCollectionIds = collections
    .map((c) => c.id)
    .filter((id) => formData.get(`include_${id}`) !== "on");

  const mergedSettings: Record<string, unknown> = {
    ...currentSettings,
    auto_sync: autoSync,
    excluded_collection_ids: excludedCollectionIds,
  };

  try {
    await client.updateIntegration(connection.fibermadeIntegrationId, {
      settings: mergedSettings,
    });
    return { success: true };
  } catch (e) {
    const message = e instanceof Error ? e.message : "Failed to save settings";
    return { success: false, error: message };
  }
};

export default function SettingsRoute() {
  const { collections, autoSync, excludedCollectionIds } =
    useLoaderData<SettingsLoaderData>();
  const fetcher = useFetcher<SettingsActionData>();
  const isSaving =
    fetcher.state === "loading" || fetcher.state === "submitting";

  const showSuccess =
    fetcher.data && "success" in fetcher.data && fetcher.data.success === true;
  const showError =
    fetcher.data && "success" in fetcher.data && fetcher.data.success === false;

  return (
    <s-page heading="Settings">
      {showSuccess && (
        <s-banner tone="success" slot="aside">
          Settings saved successfully.
        </s-banner>
      )}
      {showError && fetcher.data && "error" in fetcher.data && (
        <s-banner tone="critical" slot="aside">
          {fetcher.data.error}
        </s-banner>
      )}

      <fetcher.Form method="post">
        <input type="hidden" name="intent" value="save-settings" />
        <s-section heading="Sync preferences">
          <s-card>
            <label style={{ display: "flex", alignItems: "center", gap: "0.5rem", marginBottom: "1rem" }}>
              <input
                type="checkbox"
                name="autoSync"
                value="on"
                defaultChecked={autoSync}
                aria-label="Enable auto-sync on product webhook changes"
              />
              <span>Auto-sync on product webhook changes</span>
            </label>
            <s-paragraph>
              When enabled, product changes from Shopify will automatically sync
              to Fibermade.
            </s-paragraph>
          </s-card>
        </s-section>

        <s-section heading="Collections to sync">
          <s-paragraph>
            Select which collections to sync. Uncheck a collection to exclude it
            from sync (opt-out model; all are synced by default).
          </s-paragraph>
          {collections.length === 0 ? (
            <s-card>
              <s-paragraph>No collections found. Sync collections first.</s-paragraph>
            </s-card>
          ) : (
            <s-card>
              <div style={{ display: "flex", flexDirection: "column", gap: "0.5rem" }}>
                {collections.map((c) => (
                  <label
                    key={c.id}
                    style={{ display: "flex", alignItems: "center", gap: "0.5rem" }}
                  >
                    <input
                      type="checkbox"
                      name={`include_${c.id}`}
                      value="on"
                      defaultChecked={!excludedCollectionIds.includes(c.id)}
                      aria-label={`Include ${c.name} in sync`}
                    />
                    <span>
                      {c.name}
                      {c.colorways_count != null && (
                        <span style={{ color: "var(--p-color-text-subdued)", marginLeft: "0.25rem" }}>
                          ({c.colorways_count} colorways)
                        </span>
                      )}
                    </span>
                  </label>
                ))}
              </div>
            </s-card>
          )}
        </s-section>

        <s-section>
          <s-button
            type="submit"
            variant="primary"
            disabled={isSaving}
            loading={isSaving}
          >
            {isSaving ? "Saving…" : "Save settings"}
          </s-button>
        </s-section>
      </fetcher.Form>
    </s-page>
  );
}
