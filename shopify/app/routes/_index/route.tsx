import type { LoaderFunctionArgs } from "react-router";
import { redirect, Form, useLoaderData } from "react-router";

import { login } from "../../shopify.server";

import styles from "./styles.module.css";

export const loader = async ({ request }: LoaderFunctionArgs) => {
  const url = new URL(request.url);

  if (url.searchParams.get("shop")) {
    throw redirect(`/app?${url.searchParams.toString()}`);
  }

  return { showForm: Boolean(login) };
};

export default function App() {
  const { showForm } = useLoaderData<typeof loader>();

  return (
    <div className={styles.index}>
      <div className={styles.content}>
        <h1 className={styles.heading}>Connect your Shopify store to Fibermade</h1>
        <p className={styles.text}>
          Link your store to Fibermade to sync products, collections, and
          inventory with your wholesale platform.
        </p>
        {showForm && (
          <Form className={styles.form} method="post" action="/auth/login">
            <label className={styles.label}>
              <span>Shop domain</span>
              <input className={styles.input} type="text" name="shop" />
              <span>e.g: my-shop-domain.myshopify.com</span>
            </label>
            <button className={styles.button} type="submit">
              Log in
            </button>
          </Form>
        )}
        <ul className={styles.list}>
          <li>
            <strong>Connect once</strong> — Link your Shopify store to your
            Fibermade account; we create and manage the connection for you.
          </li>
          <li>
            <strong>Sync catalog</strong> — Keep products and collections in
            sync between Shopify and Fibermade automatically.
          </li>
          <li>
            <strong>Manage inventory</strong> — Use Fibermade for wholesale
            while your Shopify store stays up to date.
          </li>
        </ul>
      </div>
    </div>
  );
}
