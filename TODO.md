# Fibermade TODO

## Orders & Customers

Pull Shopify retail orders into Fibermade. Customers come along as a dependency of orders.

- CustomerSyncService: map Shopify customers to Fibermade Customers
- OrderSyncService: map Shopify orders to Fibermade Orders (type: retail), line items to OrderItems
- Link OrderItems to Colorway + Base combinations via ExternalIdentifiers
- ExternalIdentifier records for orders and customers
- Webhook handlers: `orders/paid`, `customers/create`, `customers/update`

## Creator Onboarding

Guided onboarding flow for new creators so they can self-serve instead of being personally walked through setup.

- Welcome flow after registration
- Shopify connection prompt
- First product sync walkthrough

## Store Onboarding

Guided onboarding flow for stores accepting creator invites.

- Invite acceptance flow polish
- First catalog browse walkthrough

## Admin Panel


