import { beforeEach, describe, expect, it, vi } from "vitest";
import { action } from "./webhooks.app.scopes_update";

vi.mock("../shopify.server", () => ({
  authenticate: {
    webhook: vi.fn(),
  },
}));

vi.mock("../db.server", () => ({
  default: {
    session: {
      update: vi.fn(),
    },
  },
}));

import { authenticate } from "../shopify.server";
import db from "../db.server";

const makeRequest = () =>
  new Request("http://localhost/webhooks/app/scopes_update", { method: "POST" });

const makeActionArgs = (request: Request) => ({
  request,
  params: {},
  context: {},
  unstable_pattern: "/",
});

describe("webhooks.app.scopes_update", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    vi.mocked(db.session.update).mockResolvedValue({} as never);
  });

  it("updates session scope when payload is valid", async () => {
    vi.mocked(authenticate.webhook).mockResolvedValue({
      shop: "test.myshopify.com",
      session: { id: "sess-1" },
      topic: "APP_SCOPES_UPDATE",
      payload: { current: ["read_products", "write_products"] },
    } as never);

    const response = await action(makeActionArgs(makeRequest()));

    expect(response.status).toBe(200);
    expect(db.session.update).toHaveBeenCalledWith({
      where: { id: "sess-1" },
      data: { scope: "read_products,write_products" },
    });
  });

  it("returns 200 without updating when payload.current is missing", async () => {
    vi.mocked(authenticate.webhook).mockResolvedValue({
      shop: "test.myshopify.com",
      session: { id: "sess-1" },
      topic: "APP_SCOPES_UPDATE",
      payload: {},
    } as never);

    const response = await action(makeActionArgs(makeRequest()));

    expect(response.status).toBe(200);
    expect(db.session.update).not.toHaveBeenCalled();
  });

  it("returns 200 without updating when payload.current is not an array", async () => {
    vi.mocked(authenticate.webhook).mockResolvedValue({
      shop: "test.myshopify.com",
      session: { id: "sess-1" },
      topic: "APP_SCOPES_UPDATE",
      payload: { current: "read_products,write_products" },
    } as never);

    const response = await action(makeActionArgs(makeRequest()));

    expect(response.status).toBe(200);
    expect(db.session.update).not.toHaveBeenCalled();
  });

  it("returns 200 without updating when session is null", async () => {
    vi.mocked(authenticate.webhook).mockResolvedValue({
      shop: "test.myshopify.com",
      session: null,
      topic: "APP_SCOPES_UPDATE",
      payload: { current: ["read_products"] },
    } as never);

    const response = await action(makeActionArgs(makeRequest()));

    expect(response.status).toBe(200);
    expect(db.session.update).not.toHaveBeenCalled();
  });
});
