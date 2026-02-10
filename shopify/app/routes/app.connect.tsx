import { useEffect, useState } from "react";
import type {
  ActionFunctionArgs,
  LoaderFunctionArgs,
} from "react-router";
import { redirect, useFetcher, useNavigate } from "react-router";
import { useAppBridge } from "@shopify/app-bridge-react";
import { authenticate } from "../shopify.server";
import db from "../db.server";
import { FibermadeClient } from "../services/fibermade-client.server";
import {
  FibermadeApiError,
  FibermadeAuthError,
  FibermadeForbiddenError,
} from "../services/fibermade-client.types";

export const FORM_FIELD_API_TOKEN = "apiToken";

export type ConnectActionSuccess = {
  success: true;
  integrationId: number;
};

export type ConnectActionError = {
  success: false;
  error: string;
  field?: string;
};

export type ConnectActionData = ConnectActionSuccess | ConnectActionError;

function errorResponse(error: string, field?: string): ConnectActionError {
  return { success: false, error, ...(field ? { field } : {}) };
}

export const loader = async ({ request }: LoaderFunctionArgs) => {
  const { session } = await authenticate.admin(request);
  const existing = await db.fibermadeConnection.findUnique({
    where: { shop: session.shop },
  });
  if (existing) {
    throw redirect("/app");
  }
  return null;
};

export const action = async ({
  request,
}: ActionFunctionArgs): Promise<ConnectActionData> => {
  if (request.method !== "POST") {
    return errorResponse("Method not allowed");
  }

  const { session } = await authenticate.admin(request);
  const shop = session.shop;
  const shopifyAccessToken = session.accessToken;
  if (typeof shopifyAccessToken !== "string" || !shopifyAccessToken) {
    return errorResponse("Shopify session is missing access token.");
  }

  const formData = await request.formData();
  const apiToken = formData.get(FORM_FIELD_API_TOKEN);
  if (typeof apiToken !== "string" || !apiToken.trim()) {
    return errorResponse("API token is required", FORM_FIELD_API_TOKEN);
  }

  const existing = await db.fibermadeConnection.findUnique({
    where: { shop },
  });
  if (existing) {
    return errorResponse(
      "This shop is already linked to a Fibermade account. Disconnect first to link a different account.",
      "shop"
    );
  }

  const baseUrl = process.env.FIBERMADE_API_URL;
  if (!baseUrl?.trim()) {
    return errorResponse(
      "Fibermade API is not configured. Please contact support."
    );
  }

  const client = new FibermadeClient(baseUrl);
  client.setToken(apiToken.trim());

  try {
    await client.healthCheck();
  } catch (e) {
    if (e instanceof FibermadeAuthError || e instanceof FibermadeForbiddenError) {
      return errorResponse(
        "Invalid Fibermade API token. Check your credentials and try again.",
        FORM_FIELD_API_TOKEN
      );
    }
    if (e instanceof FibermadeApiError) {
      return errorResponse(
        e.body?.message ?? e.message ?? "Fibermade API error"
      );
    }
    return errorResponse(
      "Could not reach the Fibermade API. Check your connection and try again."
    );
  }

  let integration;
  try {
    integration = await client.createIntegration({
      type: "shopify",
      credentials: shopifyAccessToken,
      settings: { shop },
      active: true,
    });
  } catch (e) {
    if (e instanceof FibermadeApiError) {
      return errorResponse(
        e.body?.message ?? e.message ?? "Failed to create integration"
      );
    }
    return errorResponse(
      "Could not reach the Fibermade API. Check your connection and try again."
    );
  }

  await db.fibermadeConnection.create({
    data: {
      shop,
      fibermadeApiToken: apiToken.trim(),
      fibermadeIntegrationId: integration.id,
      connectedAt: new Date(),
    },
  });

  return { success: true, integrationId: integration.id };
};

export default function ConnectRoute() {
  const fetcher = useFetcher<ConnectActionData>();
  const navigate = useNavigate();
  const shopify = useAppBridge();
  const [token, setToken] = useState("");

  const data = fetcher.data;
  const isSubmitting =
    (fetcher.state === "loading" || fetcher.state === "submitting") &&
    fetcher.formMethod === "POST";

  const tokenError =
    data && !data.success && data.field === FORM_FIELD_API_TOKEN
      ? data.error
      : undefined;
  const generalError =
    data && !data.success && data.field !== FORM_FIELD_API_TOKEN
      ? data.error
      : undefined;

  useEffect(() => {
    if (data && data.success) {
      shopify.toast.show("Connected to Fibermade");
      navigate("/app");
    }
  }, [data, navigate, shopify]);

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    const formData = new FormData();
    formData.set(FORM_FIELD_API_TOKEN, token);
    fetcher.submit(formData, { method: "POST" });
  };

  return (
    <s-page heading="Connect to Fibermade">
      <form onSubmit={handleSubmit}>
        <s-section heading="Link your store">
          <s-paragraph>
            Get your API token from the Fibermade platform, then paste it below
            to connect this Shopify store. The token is used to securely link
            your store with your Fibermade account.
          </s-paragraph>
          {generalError && (
            <s-banner tone="critical" slot="aside">
              {generalError}
            </s-banner>
          )}
          <s-stack direction="block" gap="base">
            <s-text-field
              name={FORM_FIELD_API_TOKEN}
              label="Fibermade API token"
              value={token}
              onChange={(e) => setToken(e.currentTarget?.value ?? "")}
              autocomplete="off"
              error={tokenError}
              disabled={isSubmitting}
            />
            <s-button type="submit" loading={isSubmitting}>
              Connect
            </s-button>
          </s-stack>
        </s-section>
      </form>
    </s-page>
  );
}
