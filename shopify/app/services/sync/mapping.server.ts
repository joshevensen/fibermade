import type { FibermadeClient } from "../fibermade-client.server";
import type { ExternalIdentifierData } from "../fibermade-client.types";

export interface FibermadeIdResult {
  identifiableType: string;
  identifiableId: number;
}

/**
 * Looks up the Fibermade model (identifiable_type, identifiable_id) for a given Shopify GID.
 * Returns null when no mapping exists.
 */
export async function findFibermadeIdByShopifyGid(
  client: FibermadeClient,
  integrationId: number,
  externalType: string,
  shopifyGid: string
): Promise<FibermadeIdResult | null> {
  try {
    const response = await client.lookupExternalIdentifier({
      integration_id: integrationId,
      external_type: externalType,
      external_id: shopifyGid,
    });
    const first = response.data?.[0];
    if (!first) return null;
    return {
      identifiableType: first.identifiable_type,
      identifiableId: first.identifiable_id,
    };
  } catch (err) {
    const message =
      err instanceof Error ? err.message : String(err);
    throw new Error(
      `findFibermadeIdByShopifyGid failed (integrationId=${integrationId}, externalType=${externalType}, shopifyGid=${shopifyGid}): ${message}`,
      { cause: err }
    );
  }
}

/**
 * Looks up the Shopify GID for a given Fibermade model and external type.
 * Returns null when no mapping exists.
 */
export async function findShopifyGidByFibermadeId(
  client: FibermadeClient,
  integrationId: number,
  identifiableType: string,
  identifiableId: number,
  externalType: string
): Promise<string | null> {
  try {
    const response = await client.lookupExternalIdentifierByIdentifiable(
      integrationId,
      identifiableType,
      identifiableId
    );
    const data = response.data ?? [];
    const match = data.find((r) => r.external_type === externalType);
    return match?.external_id ?? null;
  } catch (err) {
    const message =
      err instanceof Error ? err.message : String(err);
    throw new Error(
      `findShopifyGidByFibermadeId failed (integrationId=${integrationId}, identifiableType=${identifiableType}, identifiableId=${identifiableId}, externalType=${externalType}): ${message}`,
      { cause: err }
    );
  }
}

/**
 * Creates an ExternalIdentifier mapping. Returns the created record.
 */
export async function createMapping(
  client: FibermadeClient,
  integrationId: number,
  identifiableType: string,
  identifiableId: number,
  externalType: string,
  shopifyGid: string,
  data?: Record<string, unknown>
): Promise<ExternalIdentifierData> {
  try {
    return await client.createExternalIdentifier({
      integration_id: integrationId,
      identifiable_type: identifiableType,
      identifiable_id: identifiableId,
      external_type: externalType,
      external_id: shopifyGid,
      ...(data && { data }),
    });
  } catch (err) {
    const message =
      err instanceof Error ? err.message : String(err);
    throw new Error(
      `createMapping failed (integrationId=${integrationId}, identifiableType=${identifiableType}, identifiableId=${identifiableId}, externalType=${externalType}, shopifyGid=${shopifyGid}): ${message}`,
      { cause: err }
    );
  }
}

/**
 * Returns true if a mapping exists for the given integration, external type, and Shopify GID.
 */
export async function mappingExists(
  client: FibermadeClient,
  integrationId: number,
  externalType: string,
  shopifyGid: string
): Promise<boolean> {
  try {
    const response = await client.lookupExternalIdentifier({
      integration_id: integrationId,
      external_type: externalType,
      external_id: shopifyGid,
    });
    return (response.data?.length ?? 0) > 0;
  } catch (err) {
    const message =
      err instanceof Error ? err.message : String(err);
    throw new Error(
      `mappingExists failed (integrationId=${integrationId}, externalType=${externalType}, shopifyGid=${shopifyGid}): ${message}`,
      { cause: err }
    );
  }
}
