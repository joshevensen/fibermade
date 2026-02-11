import type { ActionFunctionArgs, LoaderFunctionArgs } from "react-router";
import { redirect, useFetcher, useLoaderData } from "react-router";
import { authenticate } from "../shopify.server";
import db from "../db.server";
import { FibermadeClient } from "../services/fibermade-client.server";
import { FibermadeNotFoundError } from "../services/fibermade-client.types";
import { ProductPushService } from "../services/sync/product-push.server";
import type { ShopifyGraphqlRunner } from "../services/sync/metafields.server";
import { parseNumericIdFromGid } from "../services/sync/product-sync.server";

export type PushLoaderData = {
  connected: boolean;
  shop?: string;
};

export type PushActionSuccess = {
  success: true;
  shopifyProductGid: string;
  adminUrl: string;
  colorwayId: number;
};

export type PushActionError = {
  success: false;
  error: string;
};

export type PushActionData = PushActionSuccess | PushActionError;

export const loader = async ({
  request,
}: LoaderFunctionArgs): Promise<PushLoaderData | Response> => {
  const { session } = await authenticate.admin(request);
  const connection = await db.fibermadeConnection.findUnique({
    where: { shop: session.shop },
  });
  if (!connection) {
    throw redirect("/app");
  }
  return {
    connected: true,
    shop: session.shop,
  };
};

export const action = async ({
  request,
}: ActionFunctionArgs): Promise<PushActionData> => {
  if (request.method !== "POST") {
    return { success: false, error: "Method not allowed" };
  }

  const formData = await request.formData();
  const colorwayIdRaw = formData.get("colorwayId");
  if (typeof colorwayIdRaw !== "string" || !colorwayIdRaw.trim()) {
    return { success: false, error: "Colorway ID is required" };
  }
  const colorwayId = parseInt(colorwayIdRaw.trim(), 10);
  if (!Number.isInteger(colorwayId) || colorwayId < 1) {
    return { success: false, error: "Colorway ID must be a positive integer" };
  }

  const { session, admin } = await authenticate.admin(request);
  const connection = await db.fibermadeConnection.findUnique({
    where: { shop: session.shop },
  });
  if (!connection) {
    throw redirect("/app");
  }

  const baseUrl = process.env.FIBERMADE_API_URL;
  if (!baseUrl?.trim()) {
    return { success: false, error: "Fibermade API is not configured." };
  }

  const graphqlRunner: ShopifyGraphqlRunner = async (query, variables) => {
    const response = await admin.graphql(query, { variables });
    const json = (await response.json()) as { data?: unknown; errors?: unknown };
    if (!response.ok) {
      const err = new Error(
        typeof json?.errors === "string" ? json.errors : "GraphQL request failed"
      ) as Error & { status?: number };
      (err as Error & { status?: number }).status = response.status;
      throw err;
    }
    return { data: json.data, errors: json.errors };
  };

  const client = new FibermadeClient(baseUrl);
  client.setToken(connection.fibermadeApiToken);

  const pushService = new ProductPushService(
    client,
    connection.fibermadeIntegrationId,
    session.shop,
    graphqlRunner
  );

  try {
    const result = await pushService.pushColorway(colorwayId);
    const productNumericId = parseNumericIdFromGid(result.shopifyProductGid);
    const adminUrl = `https://${session.shop}/admin/products/${productNumericId}`;
    return {
      success: true,
      shopifyProductGid: result.shopifyProductGid,
      adminUrl,
      colorwayId: result.colorwayId,
    };
  } catch (err) {
    if (err instanceof FibermadeNotFoundError) {
      return { success: false, error: `Colorway #${colorwayId} not found` };
    }
    const message =
      err instanceof Error ? err.message : String(err);
    return { success: false, error: message };
  }
};

export default function PushRoute() {
  useLoaderData<PushLoaderData>();
  const fetcher = useFetcher<PushActionData>();
  const isSubmitting =
    fetcher.state === "loading" || fetcher.state === "submitting";
  const data = fetcher.data;

  return (
    <s-page heading="Push to Shopify">
      <s-section heading="Push to Shopify">
        <s-paragraph>
          Create a Shopify product from a Fibermade Colorway. Enter the Colorway
          ID to push.
        </s-paragraph>
        {data && !data.success && (
          <s-banner tone="critical" slot="aside">
            {data.error}
          </s-banner>
        )}
        {data?.success && (
          <s-banner tone="success" slot="aside">
            Product created successfully.{" "}
            <a href={data.adminUrl} target="_blank" rel="noreferrer">
              Open in Shopify Admin
            </a>
          </s-banner>
        )}
        <fetcher.Form method="post">
          <label htmlFor="colorwayId">Colorway ID</label>
          <input
            id="colorwayId"
            name="colorwayId"
            type="number"
            min={1}
            required
            placeholder="e.g. 42"
          />
          <s-button
            type="submit"
            variant="primary"
            disabled={isSubmitting}
            loading={isSubmitting}
          >
            {isSubmitting ? "Pushing…" : "Push to Shopify"}
          </s-button>
        </fetcher.Form>
      </s-section>
    </s-page>
  );
}
