import type { HeadersFunction, LoaderFunctionArgs } from "react-router";
import { Navigate, useLoaderData } from "react-router";
import { authenticate } from "../shopify.server";
import db from "../db.server";
import { boundary } from "@shopify/shopify-app-react-router/server";

export const loader = async ({ request }: LoaderFunctionArgs) => {
  const { session } = await authenticate.admin(request);
  const connection = await db.fibermadeConnection.findUnique({
    where: { shop: session.shop },
  });
  return { connected: !!connection };
};

export default function Index() {
  const { connected } = useLoaderData<typeof loader>();

  if (!connected) {
    return <Navigate to="/app/connect" replace />;
  }

  return (
    <s-page heading="Home">
      <s-section heading="Connected to Fibermade">
        <s-paragraph>
          This store is linked to your Fibermade account. You can manage
          products and orders from here.
        </s-paragraph>
      </s-section>
    </s-page>
  );
}

export const headers: HeadersFunction = (headersArgs) => {
  return boundary.headers(headersArgs);
};
