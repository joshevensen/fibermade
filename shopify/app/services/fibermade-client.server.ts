import type {
  ApiError,
  ApiResponse,
  BaseData,
  CollectionData,
  ColorwayData,
  CreateBasePayload,
  CreateCollectionPayload,
  CreateColorwayPayload,
  CreateCustomerPayload,
  CreateExternalIdentifierPayload,
  CreateIntegrationPayload,
  CreateInventoryPayload,
  CreateOrderItemPayload,
  CreateOrderPayload,
  CustomerData,
  ExternalIdentifierData,
  IntegrationData,
  IntegrationLogData,
  InventoryData,
  ListParams,
  LookupExternalIdentifierParams,
  OrderData,
  OrderItemData,
  PaginatedResponse,
  RateLimitInfo,
  UpdateBasePayload,
  UpdateCollectionPayload,
  UpdateColorwayPayload,
  UpdateCustomerPayload,
  UpdateIntegrationPayload,
  UpdateInventoryPayload,
  UpdateInventoryQuantityPayload,
  UpdateOrderItemPayload,
  UpdateOrderPayload,
  ValidationError,
} from "./fibermade-client.types";
import {
  FibermadeApiError,
  FibermadeAuthError,
  FibermadeForbiddenError,
  FibermadeNotFoundError,
  FibermadeRateLimitError,
  FibermadeValidationError,
} from "./fibermade-client.types";

export type {
  ApiError,
  ApiResponse,
  ListParams,
  PaginatedResponse,
  RateLimitInfo,
  ValidationError,
} from "./fibermade-client.types";
export type {
  BaseData,
  CollectionData,
  ColorwayData,
  CreateBasePayload,
  CreateCollectionPayload,
  CreateColorwayPayload,
  CreateCustomerPayload,
  CreateExternalIdentifierPayload,
  CreateIntegrationPayload,
  CreateInventoryPayload,
  CreateOrderItemPayload,
  CreateOrderPayload,
  CustomerData,
  ExternalIdentifierData,
  IntegrationData,
  IntegrationLogData,
  InventoryData,
  LookupExternalIdentifierParams,
  OrderData,
  OrderItemData,
  UpdateBasePayload,
  UpdateCollectionPayload,
  UpdateColorwayPayload,
  UpdateCustomerPayload,
  UpdateIntegrationPayload,
  UpdateInventoryPayload,
  UpdateInventoryQuantityPayload,
  UpdateOrderItemPayload,
  UpdateOrderPayload,
} from "./fibermade-client.types";
export {
  FibermadeApiError,
  FibermadeAuthError,
  FibermadeForbiddenError,
  FibermadeNotFoundError,
  FibermadeRateLimitError,
  FibermadeValidationError,
} from "./fibermade-client.types";

const DEFAULT_MAX_RETRIES = 3;
const RATE_LIMIT_HEADER_REMAINING = "x-ratelimit-remaining";
const RATE_LIMIT_HEADER_LIMIT = "x-ratelimit-limit";
const RATE_LIMIT_HEADER_RESET = "x-ratelimit-reset";
const RETRY_AFTER_HEADER = "retry-after";

function parseRateLimitFromResponse(response: Response): RateLimitInfo {
  const getNum = (name: string): number | null => {
    const v = response.headers.get(name);
    if (v === null) return null;
    const n = parseInt(v, 10);
    return Number.isNaN(n) ? null : n;
  };
  return {
    remaining: getNum(RATE_LIMIT_HEADER_REMAINING),
    reset: getNum(RATE_LIMIT_HEADER_RESET),
    limit: getNum(RATE_LIMIT_HEADER_LIMIT),
    retryAfter: getNum(RETRY_AFTER_HEADER),
  };
}

async function parseJsonBody<T>(response: Response): Promise<T> {
  const text = await response.text();
  if (!text.trim()) {
    return {} as T;
  }
  try {
    return JSON.parse(text) as T;
  } catch {
    throw new FibermadeApiError(
      `Invalid JSON in response: ${text.slice(0, 200)}`,
      response.status,
      null
    );
  }
}

function throwForStatus(
  status: number,
  body: ApiError | ValidationError | null,
  rateLimit: RateLimitInfo
): never {
  const message = body?.message ?? `Request failed with status ${status}`;
  if (status === 401) {
    throw new FibermadeAuthError(message, body);
  }
  if (status === 403) {
    throw new FibermadeForbiddenError(message, body);
  }
  if (status === 404) {
    throw new FibermadeNotFoundError(message, body);
  }
  if (status === 422) {
    throw new FibermadeValidationError(message, body as ValidationError | null);
  }
  if (status === 429) {
    throw new FibermadeRateLimitError(message, rateLimit, body);
  }
  throw new FibermadeApiError(message, status, body);
}

/**
 * Typed HTTP client for the Fibermade platform API.
 * Server-side only (.server.ts); use with Sanctum bearer token and FIBERMADE_API_URL.
 */
export class FibermadeClient {
  private token: string | null;

  constructor(
    private readonly baseUrl: string,
    token?: string
  ) {
    this.token = token ?? null;
  }

  setToken(token: string): void {
    this.token = token;
  }

  private headers(): Record<string, string> {
    const h: Record<string, string> = {
      "Content-Type": "application/json",
      Accept: "application/json",
    };
    if (this.token) {
      h["Authorization"] = `Bearer ${this.token}`;
    }
    return h;
  }

  private url(path: string): string {
    const base = this.baseUrl.replace(/\/$/, "");
    const p = path.startsWith("/") ? path : `/${path}`;
    return `${base}${p}`;
  }

  /**
   * Performs a request with optional 429 retry (exponential backoff, max 3 retries).
   */
  private async request<T>(
    path: string,
    init: RequestInit,
    options: { skipRetry?: boolean } = {}
  ): Promise<{ response: Response; data: T }> {
    const url = this.url(path);
    let last429Error: FibermadeRateLimitError | null = null;
    let attempt = 0;
    const maxAttempts = options.skipRetry ? 1 : 1 + DEFAULT_MAX_RETRIES;

    while (attempt < maxAttempts) {
      const response = await fetch(url, {
        ...init,
        headers: { ...this.headers(), ...(init.headers as Record<string, string>) },
      });
      const rateLimit = parseRateLimitFromResponse(response);
      const contentType = response.headers.get("content-type") ?? "";
      const isJson = contentType.includes("application/json");
      const body = isJson
        ? await parseJsonBody<ApiError | ValidationError | ApiResponse<T>>(response)
        : null;

      if (response.status === 429 && !options.skipRetry && attempt < maxAttempts - 1) {
        const retryAfter = rateLimit.retryAfter ?? Math.min(2 ** attempt, 60);
        last429Error = new FibermadeRateLimitError(
          "Rate limited",
          rateLimit,
          body && "message" in body ? (body as ApiError) : null
        );
        await new Promise((r) => setTimeout(r, retryAfter * 1000));
        attempt++;
        continue;
      }

      if (!response.ok) {
        throwForStatus(
          response.status,
          body && "message" in body ? (body as ApiError) : null,
          rateLimit
        );
      }

      return { response, data: body as T };
    }

    throw last429Error ?? new FibermadeApiError("Request failed", 0, null);
  }

  /** GET request. Returns the parsed JSON body. */
  private async get<T>(path: string): Promise<T> {
    const { data } = await this.request<T>(path, { method: "GET" });
    return data;
  }

  /** GET request that expects API envelope { data: T }. Returns T. */
  private async getResource<T>(path: string): Promise<T> {
    const { data } = await this.request<ApiResponse<T>>(path, { method: "GET" });
    return (data as ApiResponse<T>).data;
  }

  /** POST with optional JSON body. */
  private async post<T>(path: string, body?: unknown): Promise<T> {
    const { data } = await this.request<T>(path, {
      method: "POST",
      body: body !== undefined ? JSON.stringify(body) : undefined,
    });
    return data;
  }

  /** PATCH with optional JSON body. */
  private async patch<T>(path: string, body?: unknown): Promise<T> {
    const { data } = await this.request<T>(path, {
      method: "PATCH",
      body: body !== undefined ? JSON.stringify(body) : undefined,
    });
    return data;
  }

  /** DELETE request. Returns parsed JSON if any. */
  private async delete<T = unknown>(path: string): Promise<T> {
    const { data } = await this.request<T>(path, { method: "DELETE" });
    return data;
  }

  /** POST that expects API envelope { data: T }. Returns T. */
  private async postResource<T>(path: string, body?: unknown): Promise<T> {
    const { data } = await this.request<ApiResponse<T>>(path, {
      method: "POST",
      body: body !== undefined ? JSON.stringify(body) : undefined,
    });
    return (data as ApiResponse<T>).data;
  }

  /** PATCH that expects API envelope { data: T }. Returns T. */
  private async patchResource<T>(path: string, body?: unknown): Promise<T> {
    const { data } = await this.request<ApiResponse<T>>(path, {
      method: "PATCH",
      body: body !== undefined ? JSON.stringify(body) : undefined,
    });
    return (data as ApiResponse<T>).data;
  }

  private buildQuery(params: ListParams | undefined): string {
    if (!params || Object.keys(params).length === 0) return "";
    const search = new URLSearchParams();
    for (const [key, value] of Object.entries(params)) {
      if (value !== undefined && value !== "") {
        search.set(key, String(value));
      }
    }
    const q = search.toString();
    return q ? `?${q}` : "";
  }

  /**
   * GET /api/v1/health. Returns { status: "ok" } on success.
   * Useful to verify connectivity and auth.
   */
  async healthCheck(): Promise<{ status: string }> {
    const data = await this.get<{ status: string }>("/api/v1/health");
    return { status: (data as { status?: string }).status ?? "ok" };
  }

  // -------------------------------------------------------------------------
  // Integrations
  // -------------------------------------------------------------------------

  async listIntegrations(params?: ListParams): Promise<PaginatedResponse<IntegrationData>> {
    return this.get<PaginatedResponse<IntegrationData>>(
      `/api/v1/integrations${this.buildQuery(params)}`
    );
  }

  async createIntegration(data: CreateIntegrationPayload): Promise<IntegrationData> {
    return this.postResource<IntegrationData>("/api/v1/integrations", data);
  }

  async getIntegration(id: number): Promise<IntegrationData> {
    return this.getResource<IntegrationData>(`/api/v1/integrations/${id}`);
  }

  async updateIntegration(id: number, data: UpdateIntegrationPayload): Promise<IntegrationData> {
    return this.patchResource<IntegrationData>(`/api/v1/integrations/${id}`, data);
  }

  async deleteIntegration(id: number): Promise<void> {
    await this.delete(`/api/v1/integrations/${id}`);
  }

  async getIntegrationLogs(
    integrationId: number,
    params?: ListParams
  ): Promise<PaginatedResponse<IntegrationLogData>> {
    return this.get<PaginatedResponse<IntegrationLogData>>(
      `/api/v1/integrations/${integrationId}/logs${this.buildQuery(params)}`
    );
  }

  // -------------------------------------------------------------------------
  // External Identifiers
  // -------------------------------------------------------------------------

  async createExternalIdentifier(
    data: CreateExternalIdentifierPayload
  ): Promise<ExternalIdentifierData> {
    return this.postResource<ExternalIdentifierData>("/api/v1/external-identifiers", data);
  }

  async lookupExternalIdentifier(
    params: LookupExternalIdentifierParams
  ): Promise<PaginatedResponse<ExternalIdentifierData>> {
    return this.get<PaginatedResponse<ExternalIdentifierData>>(
      `/api/v1/external-identifiers${this.buildQuery(params)}`
    );
  }

  // -------------------------------------------------------------------------
  // Colorways
  // -------------------------------------------------------------------------

  async listColorways(params?: ListParams): Promise<PaginatedResponse<ColorwayData>> {
    return this.get<PaginatedResponse<ColorwayData>>(
      `/api/v1/colorways${this.buildQuery(params)}`
    );
  }

  async createColorway(data: CreateColorwayPayload): Promise<ColorwayData> {
    return this.postResource<ColorwayData>("/api/v1/colorways", data);
  }

  async getColorway(id: number): Promise<ColorwayData> {
    return this.getResource<ColorwayData>(`/api/v1/colorways/${id}`);
  }

  async updateColorway(id: number, data: UpdateColorwayPayload): Promise<ColorwayData> {
    return this.patchResource<ColorwayData>(`/api/v1/colorways/${id}`, data);
  }

  async deleteColorway(id: number): Promise<void> {
    await this.delete(`/api/v1/colorways/${id}`);
  }

  // -------------------------------------------------------------------------
  // Bases
  // -------------------------------------------------------------------------

  async listBases(params?: ListParams): Promise<PaginatedResponse<BaseData>> {
    return this.get<PaginatedResponse<BaseData>>(`/api/v1/bases${this.buildQuery(params)}`);
  }

  async createBase(data: CreateBasePayload): Promise<BaseData> {
    return this.postResource<BaseData>("/api/v1/bases", data);
  }

  async getBase(id: number): Promise<BaseData> {
    return this.getResource<BaseData>(`/api/v1/bases/${id}`);
  }

  async updateBase(id: number, data: UpdateBasePayload): Promise<BaseData> {
    return this.patchResource<BaseData>(`/api/v1/bases/${id}`, data);
  }

  async deleteBase(id: number): Promise<void> {
    await this.delete(`/api/v1/bases/${id}`);
  }

  // -------------------------------------------------------------------------
  // Collections
  // -------------------------------------------------------------------------

  async listCollections(params?: ListParams): Promise<PaginatedResponse<CollectionData>> {
    return this.get<PaginatedResponse<CollectionData>>(
      `/api/v1/collections${this.buildQuery(params)}`
    );
  }

  async createCollection(data: CreateCollectionPayload): Promise<CollectionData> {
    return this.postResource<CollectionData>("/api/v1/collections", data);
  }

  async getCollection(id: number): Promise<CollectionData> {
    return this.getResource<CollectionData>(`/api/v1/collections/${id}`);
  }

  async updateCollection(id: number, data: UpdateCollectionPayload): Promise<CollectionData> {
    return this.patchResource<CollectionData>(`/api/v1/collections/${id}`, data);
  }

  async deleteCollection(id: number): Promise<void> {
    await this.delete(`/api/v1/collections/${id}`);
  }

  // -------------------------------------------------------------------------
  // Inventory
  // -------------------------------------------------------------------------

  async listInventory(params?: ListParams): Promise<PaginatedResponse<InventoryData>> {
    return this.get<PaginatedResponse<InventoryData>>(
      `/api/v1/inventory${this.buildQuery(params)}`
    );
  }

  async createInventory(data: CreateInventoryPayload): Promise<InventoryData> {
    return this.postResource<InventoryData>("/api/v1/inventory", data);
  }

  async getInventory(id: number): Promise<InventoryData> {
    return this.getResource<InventoryData>(`/api/v1/inventory/${id}`);
  }

  async updateInventory(id: number, data: UpdateInventoryPayload): Promise<InventoryData> {
    return this.patchResource<InventoryData>(`/api/v1/inventory/${id}`, data);
  }

  async deleteInventory(id: number): Promise<void> {
    await this.delete(`/api/v1/inventory/${id}`);
  }

  async updateInventoryQuantity(
    id: number,
    data: UpdateInventoryQuantityPayload
  ): Promise<InventoryData> {
    return this.patchResource<InventoryData>(`/api/v1/inventory/${id}/quantity`, data);
  }

  // -------------------------------------------------------------------------
  // Orders
  // -------------------------------------------------------------------------

  async listOrders(params?: ListParams): Promise<PaginatedResponse<OrderData>> {
    return this.get<PaginatedResponse<OrderData>>(`/api/v1/orders${this.buildQuery(params)}`);
  }

  async createOrder(data: CreateOrderPayload): Promise<OrderData> {
    return this.postResource<OrderData>("/api/v1/orders", data);
  }

  async getOrder(id: number): Promise<OrderData> {
    return this.getResource<OrderData>(`/api/v1/orders/${id}`);
  }

  async updateOrder(id: number, data: UpdateOrderPayload): Promise<OrderData> {
    return this.patchResource<OrderData>(`/api/v1/orders/${id}`, data);
  }

  async deleteOrder(id: number): Promise<void> {
    await this.delete(`/api/v1/orders/${id}`);
  }

  // -------------------------------------------------------------------------
  // Order Items
  // -------------------------------------------------------------------------

  async listOrderItems(
    orderId: number,
    params?: ListParams
  ): Promise<PaginatedResponse<OrderItemData>> {
    return this.get<PaginatedResponse<OrderItemData>>(
      `/api/v1/orders/${orderId}/items${this.buildQuery(params)}`
    );
  }

  async createOrderItem(
    orderId: number,
    data: CreateOrderItemPayload
  ): Promise<OrderItemData> {
    return this.postResource<OrderItemData>(`/api/v1/orders/${orderId}/items`, data);
  }

  async getOrderItem(orderId: number, itemId: number): Promise<OrderItemData> {
    return this.getResource<OrderItemData>(`/api/v1/orders/${orderId}/items/${itemId}`);
  }

  async updateOrderItem(
    orderId: number,
    itemId: number,
    data: UpdateOrderItemPayload
  ): Promise<OrderItemData> {
    return this.patchResource<OrderItemData>(
      `/api/v1/orders/${orderId}/items/${itemId}`,
      data
    );
  }

  async deleteOrderItem(orderId: number, itemId: number): Promise<void> {
    await this.delete(`/api/v1/orders/${orderId}/items/${itemId}`);
  }

  // -------------------------------------------------------------------------
  // Customers
  // -------------------------------------------------------------------------

  async listCustomers(params?: ListParams): Promise<PaginatedResponse<CustomerData>> {
    return this.get<PaginatedResponse<CustomerData>>(
      `/api/v1/customers${this.buildQuery(params)}`
    );
  }

  async createCustomer(data: CreateCustomerPayload): Promise<CustomerData> {
    return this.postResource<CustomerData>("/api/v1/customers", data);
  }

  async getCustomer(id: number): Promise<CustomerData> {
    return this.getResource<CustomerData>(`/api/v1/customers/${id}`);
  }

  async updateCustomer(id: number, data: UpdateCustomerPayload): Promise<CustomerData> {
    return this.patchResource<CustomerData>(`/api/v1/customers/${id}`, data);
  }

  async deleteCustomer(id: number): Promise<void> {
    await this.delete(`/api/v1/customers/${id}`);
  }
}
