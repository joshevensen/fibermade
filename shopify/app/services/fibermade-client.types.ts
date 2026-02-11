/**
 * Types for the Fibermade platform API client.
 * Matches Laravel API response envelopes and error shapes.
 */

/** Success envelope for a single resource: { data: T } */
export interface ApiResponse<T> {
  data: T;
}

/** Paginated list envelope: { data: T[], links: {...}, meta: {...} } */
export interface PaginatedResponse<T> {
  data: T[];
  links?: {
    first: string;
    last: string;
    prev: string | null;
    next: string | null;
  };
  meta?: {
    current_page: number;
    from: number | null;
    last_page: number;
    path: string;
    per_page: number;
    to: number | null;
    total: number;
  };
}

/** Generic API error body: { message: string } */
export interface ApiError {
  message: string;
}

/** Validation error body (422): { message: string, errors?: { [field]: string[] } } */
export interface ValidationError extends ApiError {
  errors?: Record<string, string[]>;
}

/** Rate limit data parsed from response headers */
export interface RateLimitInfo {
  /** Requests remaining in the current window */
  remaining: number | null;
  /** Unix timestamp when the rate limit resets (from X-RateLimit-Reset if present) */
  reset: number | null;
  /** Max requests per window (from X-RateLimit-Limit) */
  limit: number | null;
  /** Seconds to wait before retry (from Retry-After on 429) */
  retryAfter: number | null;
}

/** Base class for all Fibermade API errors (network, auth, validation, etc.) */
export class FibermadeApiError extends Error {
  constructor(
    message: string,
    public readonly status: number,
    public readonly body: ApiError | ValidationError | null = null
  ) {
    super(message);
    this.name = "FibermadeApiError";
    Object.setPrototypeOf(this, FibermadeApiError.prototype);
  }
}

/** 401 Unauthorized — invalid or missing bearer token */
export class FibermadeAuthError extends FibermadeApiError {
  constructor(message: string, body: ApiError | null = null) {
    super(message, 401, body);
    this.name = "FibermadeAuthError";
    Object.setPrototypeOf(this, FibermadeAuthError.prototype);
  }
}

/** 403 Forbidden — token valid but not allowed for this action */
export class FibermadeForbiddenError extends FibermadeApiError {
  constructor(message: string, body: ApiError | null = null) {
    super(message, 403, body);
    this.name = "FibermadeForbiddenError";
    Object.setPrototypeOf(this, FibermadeForbiddenError.prototype);
  }
}

/** 404 Not Found */
export class FibermadeNotFoundError extends FibermadeApiError {
  constructor(message: string, body: ApiError | null = null) {
    super(message, 404, body);
    this.name = "FibermadeNotFoundError";
    Object.setPrototypeOf(this, FibermadeNotFoundError.prototype);
  }
}

/** 422 Unprocessable Entity — validation failed with field-level details */
export class FibermadeValidationError extends FibermadeApiError {
  declare body: ValidationError | null;

  constructor(message: string, body: ValidationError | null = null) {
    super(message, 422, body);
    this.name = "FibermadeValidationError";
    Object.setPrototypeOf(this, FibermadeValidationError.prototype);
  }

  get errors(): Record<string, string[]> {
    return this.body?.errors ?? {};
  }
}

/** 429 Too Many Requests — rate limited; includes retry/backoff info */
export class FibermadeRateLimitError extends FibermadeApiError {
  constructor(
    message: string,
    public readonly rateLimit: RateLimitInfo,
    body: ApiError | null = null
  ) {
    super(message, 429, body);
    this.name = "FibermadeRateLimitError";
    Object.setPrototypeOf(this, FibermadeRateLimitError.prototype);
  }
}

// ---------------------------------------------------------------------------
// List / query params
// ---------------------------------------------------------------------------

export interface ListParams {
  page?: number;
  per_page?: number;
  [key: string]: string | number | undefined;
}

// ---------------------------------------------------------------------------
// Integration
// ---------------------------------------------------------------------------

export interface IntegrationData {
  id: number;
  type: string;
  settings: Record<string, unknown> | null;
  active: boolean;
  created_at: string;
  updated_at: string;
  logs?: IntegrationLogData[];
}

export interface CreateIntegrationPayload {
  type: string;
  credentials: string;
  settings?: Record<string, unknown>;
  active: boolean;
}

export interface UpdateIntegrationPayload {
  account_id?: number;
  type?: string;
  credentials?: string;
  settings?: Record<string, unknown>;
  active?: boolean;
}

// ---------------------------------------------------------------------------
// Integration Log
// ---------------------------------------------------------------------------

export interface IntegrationLogData {
  id: number;
  integration_id: number;
  loggable_type: string;
  loggable_id: number;
  status: string;
  message: string | null;
  metadata: Record<string, unknown> | null;
  synced_at: string | null;
  created_at: string;
  updated_at: string;
}

export interface CreateIntegrationLogPayload {
  loggable_type: string;
  loggable_id: number;
  status: "success" | "error" | "warning";
  message: string;
  metadata?: Record<string, unknown> | null;
  synced_at?: string | null;
}

// ---------------------------------------------------------------------------
// External Identifier
// ---------------------------------------------------------------------------

export interface ExternalIdentifierData {
  id: number;
  integration_id: number;
  identifiable_type: string;
  identifiable_id: number;
  external_type: string;
  external_id: string;
  data: Record<string, unknown> | null;
  created_at: string;
  updated_at: string;
}

export interface CreateExternalIdentifierPayload {
  integration_id: number;
  identifiable_type: string;
  identifiable_id: number;
  external_type: string;
  external_id: string;
  data?: Record<string, unknown>;
}

export interface LookupExternalIdentifierParams extends ListParams {
  external_type: string;
  external_id: string;
}

// ---------------------------------------------------------------------------
// Colorway
// ---------------------------------------------------------------------------

export interface ColorwayData {
  id: number;
  name: string;
  description: string | null;
  technique: string | null;
  colors: string[];
  per_pan: number;
  status: string;
  created_at: string;
  updated_at: string;
  collections?: CollectionData[];
  inventories?: InventoryData[];
  primary_image_url?: string | null;
}

export interface CreateColorwayPayload {
  name: string;
  description?: string | null;
  technique?: string | null;
  colors?: string[];
  per_pan: number;
  recipe?: string | null;
  notes?: string | null;
  status: string;
  created_by?: number | null;
  updated_by?: number | null;
}

export interface UpdateColorwayPayload {
  account_id?: number;
  name?: string;
  description?: string | null;
  technique?: string | null;
  colors?: string[];
  per_pan?: number;
  recipe?: string | null;
  notes?: string | null;
  status?: string;
  created_by?: number | null;
  updated_by?: number | null;
}

// ---------------------------------------------------------------------------
// Base
// ---------------------------------------------------------------------------

export interface BaseData {
  id: number;
  descriptor: string;
  description: string | null;
  code: string | null;
  status: string;
  weight: string | null;
  size: number | null;
  cost: string | null;
  retail_price: string | null;
  wool_percent: string | null;
  nylon_percent: string | null;
  alpaca_percent: string | null;
  yak_percent: string | null;
  camel_percent: string | null;
  cotton_percent: string | null;
  bamboo_percent: string | null;
  silk_percent: string | null;
  linen_percent: string | null;
  created_at: string;
  updated_at: string;
  inventories?: InventoryData[];
}

export interface CreateBasePayload {
  descriptor: string;
  description?: string | null;
  status: string;
  weight?: string | null;
  size?: number | null;
  cost?: number | null;
  retail_price?: number | null;
  wool_percent?: number | null;
  nylon_percent?: number | null;
  alpaca_percent?: number | null;
  yak_percent?: number | null;
  camel_percent?: number | null;
  cotton_percent?: number | null;
  bamboo_percent?: number | null;
  silk_percent?: number | null;
  linen_percent?: number | null;
}

export interface UpdateBasePayload {
  account_id?: number;
  descriptor?: string;
  description?: string | null;
  code?: string | null;
  status?: string;
  weight?: string | null;
  size?: number | null;
  cost?: number | null;
  retail_price?: number | null;
  wool_percent?: number | null;
  nylon_percent?: number | null;
  alpaca_percent?: number | null;
  yak_percent?: number | null;
  camel_percent?: number | null;
  cotton_percent?: number | null;
  bamboo_percent?: number | null;
  silk_percent?: number | null;
  linen_percent?: number | null;
}

// ---------------------------------------------------------------------------
// Collection
// ---------------------------------------------------------------------------

export interface CollectionData {
  id: number;
  name: string;
  description: string | null;
  status: string;
  created_at: string;
  updated_at: string;
  colorways_count?: number;
  colorways?: ColorwayData[];
}

export interface CreateCollectionPayload {
  name: string;
  description?: string | null;
  status: string;
}

export interface UpdateCollectionPayload {
  account_id?: number;
  name?: string;
  description?: string | null;
  status?: string;
}

// ---------------------------------------------------------------------------
// Inventory
// ---------------------------------------------------------------------------

export interface InventoryData {
  id: number;
  colorway_id: number;
  base_id: number;
  quantity: number;
  created_at: string;
  updated_at: string;
  colorway?: ColorwayData;
  base?: BaseData;
}

export interface CreateInventoryPayload {
  colorway_id: number;
  base_id: number;
  quantity: number;
}

export interface UpdateInventoryPayload {
  account_id?: number;
  colorway_id?: number;
  base_id?: number;
  quantity?: number;
}

export interface UpdateInventoryQuantityPayload {
  colorway_id: number;
  base_id: number;
  quantity: number;
}

// ---------------------------------------------------------------------------
// Order
// ---------------------------------------------------------------------------

export interface OrderData {
  id: number;
  type: string;
  status: string;
  order_date: string;
  subtotal_amount: string | null;
  shipping_amount: string | null;
  discount_amount: string | null;
  tax_amount: string | null;
  total_amount: string | null;
  refunded_amount: string | null;
  payment_method: string | null;
  source: string | null;
  notes: string | null;
  orderable_type: string | null;
  orderable_id: number | null;
  taxes: unknown;
  cancelled_at: string | null;
  created_at: string;
  updated_at: string;
  order_items?: OrderItemData[];
  orderable?: Record<string, unknown>;
}

export interface CreateOrderPayload {
  type: string;
  status: string;
  orderable_id?: number | null;
  order_date: string;
  subtotal_amount?: number | null;
  shipping_amount?: number | null;
  discount_amount?: number | null;
  tax_amount?: number | null;
  total_amount?: number | null;
  notes?: string | null;
  created_by?: number | null;
  updated_by?: number | null;
}

export interface UpdateOrderPayload {
  type?: string;
  status?: string;
  orderable_id?: number | null;
  order_date?: string;
  subtotal_amount?: number | null;
  shipping_amount?: number | null;
  discount_amount?: number | null;
  tax_amount?: number | null;
  total_amount?: number | null;
  notes?: string | null;
  created_by?: number | null;
  updated_by?: number | null;
}

// ---------------------------------------------------------------------------
// Order Item
// ---------------------------------------------------------------------------

export interface OrderItemData {
  id: number;
  order_id: number;
  colorway_id: number;
  base_id: number;
  quantity: number;
  unit_price: string | null;
  line_total: string | null;
  created_at: string;
  updated_at: string;
  colorway?: ColorwayData;
  base?: BaseData;
}

export interface CreateOrderItemPayload {
  colorway_id: number;
  base_id: number;
  quantity: number;
  unit_price?: number | null;
  line_total?: number | null;
}

export interface UpdateOrderItemPayload {
  order_id?: number;
  colorway_id?: number;
  base_id?: number;
  quantity?: number;
  unit_price?: number | null;
  line_total?: number | null;
}

// ---------------------------------------------------------------------------
// Customer
// ---------------------------------------------------------------------------

export interface CustomerData {
  id: number;
  name: string;
  email: string | null;
  phone: string | null;
  address_line1: string | null;
  address_line2: string | null;
  city: string | null;
  state_region: string | null;
  postal_code: string | null;
  country_code: string | null;
  notes: string | null;
  created_at: string;
  updated_at: string;
  orders?: OrderData[];
}

export interface CreateCustomerPayload {
  account_id: number;
  name: string;
  email?: string | null;
  phone?: string | null;
  address_line1?: string | null;
  address_line2?: string | null;
  city?: string | null;
  state_region?: string | null;
  postal_code?: string | null;
  country_code?: string | null;
  notes?: string | null;
}

export interface UpdateCustomerPayload {
  account_id?: number;
  name?: string;
  email?: string | null;
  phone?: string | null;
  address_line1?: string | null;
  address_line2?: string | null;
  city?: string | null;
  state_region?: string | null;
  postal_code?: string | null;
  country_code?: string | null;
  notes?: string | null;
}
