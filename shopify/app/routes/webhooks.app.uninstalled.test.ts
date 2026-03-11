import { beforeEach, describe, expect, it, vi } from "vitest";
import { action } from "./webhooks.app.uninstalled";

vi.mock("../shopify.server", () => ({
  authenticate: {
    webhook: vi.fn(),
  },
}));

vi.mock("../db.server", () => ({
  default: {
    fibermadeConnection: {
      findUnique: vi.fn(),
      delete: vi.fn(),
    },
    session: {
      deleteMany: vi.fn(),
    },
  },
}));

const mockUpdateIntegration = vi.fn().mockResolvedValue({});

vi.mock("../services/fibermade-client.server", () => ({
  FibermadeClient: vi.fn().mockImplementation(function (this: {
    setToken: ReturnType<typeof vi.fn>;
    updateIntegration: ReturnType<typeof vi.fn>;
  }) {
    this.setToken = vi.fn();
    this.updateIntegration = mockUpdateIntegration;
    return this;
  }),
}));

import { authenticate } from "../shopify.server";
import db from "../db.server";

describe("webhooks.app.uninstalled", () => {
  const mockConnection = {
    id: 1,
    shop: "test.myshopify.com",
    fibermadeApiToken: "token",
    fibermadeIntegrationId: 42,
    connectedAt: new Date(),
  };

  beforeEach(() => {
    vi.clearAllMocks();
    vi.mocked(authenticate.webhook).mockResolvedValue({
      shop: "test.myshopify.com",
      session: { id: "sess-1" },
      topic: "app/uninstalled",
    } as never);
    vi.mocked(db.fibermadeConnection.findUnique).mockResolvedValue(mockConnection as never);
    vi.mocked(db.fibermadeConnection.delete).mockResolvedValue({} as never);
    vi.mocked(db.session.deleteMany).mockResolvedValue({ count: 1 } as never);
    process.env.FIBERMADE_API_URL = "https://api.fibermade.test";
  });

  it("deactivates integration and deletes connection and sessions", async () => {
    const request = new Request("http://localhost/webhooks/app/uninstalled", {
      method: "POST",
    });

    const response = await action({
      request,
      params: {},
      context: {},
      unstable_pattern: "/",
    });

    expect(response.status).toBe(200);
    expect(mockUpdateIntegration).toHaveBeenCalledWith(42, { active: false });
    expect(db.fibermadeConnection.delete).toHaveBeenCalledWith({
      where: { id: mockConnection.id },
    });
    expect(db.session.deleteMany).toHaveBeenCalledWith({
      where: { shop: "test.myshopify.com" },
    });
  });

  it("returns 200 when no connection exists", async () => {
    vi.mocked(db.fibermadeConnection.findUnique).mockResolvedValue(null);

    const request = new Request("http://localhost/webhooks/app/uninstalled", {
      method: "POST",
    });

    const response = await action({
      request,
      params: {},
      context: {},
      unstable_pattern: "/",
    });

    expect(response.status).toBe(200);
    expect(mockUpdateIntegration).not.toHaveBeenCalled();
    expect(db.fibermadeConnection.delete).not.toHaveBeenCalled();
  });
});
