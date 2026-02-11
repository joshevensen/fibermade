status: pending

# Story 2.5: Prompt 2 -- Image Push to Shopify

## Context

Prompt 1 creates Shopify products from Fibermade Colorways via the `productCreate` GraphQL mutation but does not push images. The Shopify Admin GraphQL API requires a separate `productCreateMedia` mutation to attach images to an existing product. On the Fibermade side, Colorways expose a `primary_image_url` field through the API -- this is a full URL to the image file served from the platform's public storage (local disk or S3). The `ProductPushService` from Prompt 1 already returns the Shopify product GID after creation, which is needed as the target for image attachment.

## Goal

Extend the product push flow to attach the Colorway's primary image to the newly created Shopify product. After `productCreate` succeeds, fetch the Colorway's `primary_image_url`, and if present, call `productCreateMedia` to attach it as the product's image. This runs as part of the same `pushColorway` flow -- not as a separate action.

## Non-Goals

- Do not push multiple images per Colorway (only primary image for Stage 1)
- Do not handle image updates (if the image changes on Fibermade, re-pushing is not in scope)
- Do not push variant-level images (only product-level)
- Do not download/re-upload the image binary -- use the source URL directly via `originalSource`
- Do not create Media records on the Fibermade side from Shopify (that's the import direction)

## Constraints

- Add the image push logic as a private method on `ProductPushService` called from `pushColorway` after successful product creation
- Use the `productCreateMedia` GraphQL mutation with `originalSource` set to the `primary_image_url` -- Shopify will fetch and host the image from this URL
- The `primary_image_url` must be publicly accessible for Shopify to fetch it. In local development this may not work (localhost URLs) -- handle gracefully by logging a warning and skipping if the URL is not routable
- If `primary_image_url` is null or empty, skip the image push silently (many Colorways won't have images yet)
- Image push failure should NOT fail the overall product push -- log the error and return the product result with an `imageError` field
- Follow the existing error logging pattern in `ProductPushService`

## Acceptance Criteria

- [ ] `ProductPushService` gains a private method `pushImage(productGid: string, imageUrl: string): Promise<ProductImageResult>` that:
  1. Calls `productCreateMedia` mutation with `originalSource` set to the image URL
  2. Returns success with the media GID, or error details
- [ ] `pushColorway` calls `pushImage` after successful product creation when `primary_image_url` is present
- [ ] `ProductPushResult` type extended with optional `imageGid?: string` and `imageError?: string` fields
- [ ] GraphQL mutation:
  ```graphql
  mutation productCreateMedia($productId: ID!, $media: [CreateMediaInput!]!) {
    productCreateMedia(productId: $productId, media: $media) {
      media {
        ... on MediaImage {
          id
          image {
            url
          }
        }
      }
      mediaUserErrors {
        field
        message
        code
      }
      product {
        id
      }
    }
  }
  ```
  With variables:
  ```json
  {
    "productId": "gid://shopify/Product/123",
    "media": [{ "originalSource": "https://fibermade.test/storage/colorways/image.jpg", "mediaContentType": "IMAGE" }]
  }
  ```
- [ ] If `primary_image_url` is null, `pushImage` is not called and `imageGid` is undefined in the result
- [ ] If `productCreateMedia` returns `mediaUserErrors`, the product push still succeeds but `imageError` is populated
- [ ] IntegrationLog metadata includes image result (GID or error) when an image push was attempted
- [ ] Tests in `shopify/app/services/sync/product-push.server.test.ts` (added to existing test file):
  - Test image push called after successful product creation when `primary_image_url` is present
  - Test image push NOT called when `primary_image_url` is null
  - Test `productCreateMedia` mutation receives correct `productId` and `originalSource`
  - Test image push failure does not fail overall product push -- result has `imageError` but product fields are still populated
  - Test `mediaUserErrors` from Shopify are captured in `imageError`
  - Test IntegrationLog metadata includes image result

---

## Tech Analysis

- **`productCreateMedia` mutation**: This is Shopify's recommended approach for attaching media to products via GraphQL. It accepts an array of `CreateMediaInput` objects. For images, set `mediaContentType: IMAGE` and `originalSource` to a publicly accessible URL. Shopify will download and host the image on its CDN.
- **`originalSource` URL requirement**: Shopify fetches the image from this URL server-side. The URL must be publicly accessible -- `localhost` or private network URLs will fail. In production with S3 or a public domain this works naturally. For local dev, the push will gracefully skip with a warning.
- **Media response types**: The `productCreateMedia` response uses a union type for media. Image results come back as `MediaImage` with an `id` and nested `image.url`. The inline fragment `... on MediaImage` is needed in the query.
- **Error isolation**: The image push is a "nice to have" on top of the core product creation. Keeping it non-blocking means the merchant gets their product in Shopify even if the image fails (they can always add it manually in Shopify admin).
- **`primary_image_url` availability**: The Colorway API response includes `primary_image_url` when media is loaded. The Prompt 1 `pushColorway` already fetches the Colorway via `client.getColorway(id)`. The ColorwayResource conditionally loads media, so the API controller's eager loading (`['collections', 'inventories', 'media']`) must include `media` for the URL to be present.
- **Single image only**: Colorways can have multiple media records but only one `primary_image_url`. Stage 1 pushes only this primary image. Multi-image push can be added later by iterating over a media collection endpoint.

## References

- `shopify/app/services/sync/product-push.server.ts` -- ProductPushService from Prompt 1 (add image method here)
- `shopify/app/services/sync/types.ts` -- ProductPushResult type to extend
- `shopify/app/services/fibermade-client.types.ts` -- ColorwayData type with `primary_image_url` field
- `platform/app/Http/Resources/Api/V1/ColorwayResource.php` -- how `primary_image_url` is computed from loaded media
- `platform/app/Models/Colorway.php` -- `getPrimaryImageUrlAttribute` accessor and media relationship

## Files

- Modify `shopify/app/services/sync/product-push.server.ts` -- add `pushImage` private method, call from `pushColorway`
- Modify `shopify/app/services/sync/types.ts` -- add `imageGid` and `imageError` to `ProductPushResult`
- Modify `shopify/app/services/sync/product-push.server.test.ts` -- add image push test cases
