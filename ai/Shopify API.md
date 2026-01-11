# Shopify API Integration for Fibermade

**Overview:** You can use Shopify’s Admin API to sync key store data with your Fibermade platform. Given that Fibermade will be the **source of truth** for products, variants, and inventory – and Shopify remains the source of truth for orders (except wholesale) – you’ll primarily push product data from Fibermade to Shopify, and pull order/customer data from Shopify into Fibermade. Below is a breakdown of the API capabilities for each area, as well as relevant settings and future considerations.

## Data Flow and Source-of-Truth Strategy

Having a single source of truth for each data domain is wise. In practice, this means:

- **Products, Variants, and Inventory:** Maintain these in Fibermade and update Shopify via API. Whenever a product is created or changed in Fibermade, use the Shopify API to create/update the corresponding product record and its variants in the Shopify store. The same applies to inventory levels (Fibermade should push stock updates to Shopify). This one-direction sync avoids conflicts.
    
- **Orders:** Regular customer orders (e.g. online store sales) are authoritative in Shopify. Fibermade should fetch these orders via the API (or receive them via webhooks) to keep an internal copy. If Fibermade also handles wholesale orders separately, those might be kept out of Shopify, as you indicated.
    
- **Customers:** If customers are created in Shopify (through checkout account creation or admin), treat Shopify as the source and import them into Fibermade. Fibermade can maintain a **copy** of the customer data for future use (especially if you plan to migrate off Shopify eventually), but changes in Shopify (new customers, updates to emails/addresses) should flow into Fibermade regularly.
    
- **Avoiding Dual Writes:** Because each piece of data has one primary system, you’ll generally avoid updating the same record in both places. For example, you wouldn’t edit product info in Shopify’s admin – you’d do it in Fibermade and then sync via API – to ensure Fibermade remains the authoritative source. Conversely, you wouldn’t input orders in Fibermade that originate from Shopify’s checkout – instead, always capture them from Shopify.
    

Setting up clear integration flows (and possibly Shopify webhooks) for creation/update events will help keep the two systems in sync. For instance, when inventory changes in Fibermade, you’d call the Shopify API to update stock; when an order is created in Shopify, a webhook can notify Fibermade to import that order. This way each user’s store remains consistent with their Fibermade data without manual intervention.

## Managing Products and Variants via API

Shopify’s Admin API allows full CRUD operations on products and their variants. Using the API, you can **create new products, update existing ones, retrieve product details, and delete products** as needed. The Product resource represents the core product info (title, description, images, pricing, etc.), and each product can have multiple variants for its options (size, color, etc.). The API supports linking products with variants and images as well:

- **Product Creation/Update:** With the proper scope (`products` access), you can programmatically create products or modify them. For example, there is a REST endpoint `POST /admin/api/latest/products.json` to create a product, and `PUT /admin/api/latest/products/{id}.json` to update one. The Shopify docs confirm: _“The Product resource lets you update and create products in a merchant's store. You can use product variants with the Product resource to create or update different versions of the same product. You can also add or update product images.”_. In practice, Fibermade can call these endpoints whenever a product is added or edited so Shopify stays up to date with Fibermade’s product catalog. If a product has images, you can upload those via the API as well (either by providing an image URL or base64 data in the product create/update call). Variants can be managed either inline when creating a product or via separate Variant APIs.
    
- **Variants:** Every Shopify product has at least one variant. The API provides endpoints to create additional variants or update variant details (like SKU, price, barcode, etc.). For example, `POST /admin/api/latest/products/{product_id}/variants.json` creates a new variant for a product, and `PUT /admin/api/latest/variants/{variant_id}.json` updates an existing variant. (Under the hood, Shopify has recently migrated to a new product model, and direct variant creation via REST was deprecated in mid-2024, with GraphQL becoming the preferred way. But as long as you use a supported API version, you can still manage variants – just note this for future-proofing.) In Fibermade’s context, whenever you add a new option or variant (say a new size or color) to a product, you would use these API calls to reflect that in Shopify.
    
- **Product Tags:** Tags are simple text labels on products (and other resources) used for organization and filtering. In Shopify’s API, product tags are typically managed by including a comma-separated `tags` string when creating or updating a product. You can set or change a product’s tags via the product update endpoint. In GraphQL, there are also specific mutations like `tagsAdd` and `tagsRemove` for more granular tag management. Tags are very useful for grouping and searching products on Shopify (e.g. you can tag products and then create automated collections based on those tags). The API documentation notes: _“Tags help merchants organize and filter resources.”_ You can add tags to Products, Customers, Orders, etc. via the API. For example, you might tag products in Fibermade (e.g. “Spring 2024” or “Clearance”) and sync those tags to Shopify so the online store can use them for category pages or filters.
    
- **Collections:** Collections in Shopify are groups of products. You mentioned wanting to integrate collections as well – this is definitely possible via the API. Shopify supports two types of collections: **Custom Collections** (manual grouping of products) and **Smart Collections** (automatic rules-based grouping). The API allows creating and updating both types. Smart collections have conditions (like “product tag equals X”) and Shopify will auto-include products matching those. Custom collections are more like folders where you manually add specific products. The docs explain: _“A collection is a grouping of products... Merchants can create collections by selecting products individually or by defining rules that automatically determine inclusion. There are two types: custom collections (manually added products) and smart collections (automatically added based on conditions).”_. If Fibermade is the source for organizing products, you could either (a) maintain rules/tags in Fibermade and create corresponding Smart Collections in Shopify with those rules, or (b) explicitly manage which products belong in which Custom Collection and use the Collect API to add/remove products. Shopify’s API has a **Collect** resource (essentially a many-to-many join between products and custom collections) – to add a product to a custom collection, you create a Collect record linking them. In summary, Fibermade can create collections (e.g. “Summer Sale”) via API and then either assign products by creating Collects or set up conditions if it’s a smart collection.
    
- **Product Types and Other Attributes:** In addition to tags and collections, Shopify products have attributes like `product_type` (a category label), `vendor` (brand), and options. These too can be set via the API when creating or updating products. For instance, if Fibermade tracks a product category or type, you may want to populate Shopify’s `product_type` or use tags accordingly so that the online store has the same categorization.
    

Overall, **Fibermade should push any change in products or variants to Shopify** immediately or in batches. It’s important to also capture the Shopify product IDs that are created, and store them in Fibermade, so you have a mapping between Fibermade’s records and Shopify’s records. This mapping will be needed when updating or referencing specific products (since Shopify’s API will use their IDs).

## Inventory Synchronization

Inventory is a critical piece to sync, and Shopify’s API provides a specialized mechanism for it. In Shopify’s model, inventory is decoupled from the product/variant itself – it’s managed via **InventoryItem** and **InventoryLevel** records tied to **Locations**. Here’s a quick summary of how it works:

- **InventoryItem:** Every product variant has an associated InventoryItem (think of it as the stock-keeping unit info). The InventoryItem holds properties like SKU, and it’s what gets inventory tracked. Shopify automatically creates an InventoryItem for each variant. All product variants have a 1-to-1 relationship with an InventoryItem (each variant has one inventory item, unique to that variant).
    
- **Location:** Your Shopify store can have one or multiple locations (e.g. warehouses, retail stores, etc. where stock is kept). Even if you don’t use multi-location, Shopify treats your store as having at least a “default location.”
    
- **InventoryLevel:** This represents the actual stock quantity of an InventoryItem at a particular Location. If you have multiple locations, one InventoryItem can have multiple InventoryLevels (one per location). _“An inventory level represents the quantities of an inventory item for a location… An inventory item will have an inventory level for each location where the item is stocked.”_.
    

![https://shopify.dev/docs/api/admin-rest/latest/resources/inventorylevel](blob:https://chatgpt.com/a31e3afb-d1cc-47d1-bd9d-0c48ed3fedc7)

_Shopify’s inventory model:_ Each **Product Variant** maps to one **InventoryItem** (SKU level), and that item can have an **InventoryLevel** per **Location** where it’s stocked. Fibermade will primarily interact with InventoryLevels to set or adjust quantities.

 

Because Fibermade is the master for inventory, you will **update Shopify’s inventory levels via the API whenever stock changes** in Fibermade. **Do not** try to set the old `inventory_quantity` field on the Variant directly – Shopify deprecated that approach. In fact, the REST API no longer allows writing inventory by editing the variant; you must use the dedicated Inventory APIs. (Shopify’s docs caution: _“Apps can no longer set inventory using `inventory_quantity`. For more information, refer to the Inventory Level resource.”_.)

 

**Key Inventory API endpoints and usage:**

- **Adjusting Inventory:** If you want to increment or decrement stock (say an order is placed or a restock happens), you can use the endpoint `POST /admin/api/latest/inventory_levels/adjust.json` to adjust the available count at a location by a certain amount.
    
- **Setting Inventory:** To override/set an absolute quantity, use `POST /admin/api/latest/inventory_levels/set.json` with a specific location ID and inventory item ID, plus the new quantity. This will set that item’s stock to the given number (and optionally you can disconnect any other locations if needed).
    
- **Connecting/Disconnecting Inventory:** `POST /admin/api/latest/inventory_levels/connect.json` or `.../inventory_levels/delete.json` are used if you need to associate or dissociate an item with a location (e.g. if you start stocking an item at a new warehouse, or drop an item from a location). In many cases, simply setting a level will auto-connect an item to that location.
    
- **Retrieving Inventory Info:** You can query current inventory levels via GET requests, e.g. `GET /admin/api/latest/inventory_levels.json?inventory_item_ids=...` or by location, etc., to see the current quantities. However, if Fibermade is always pushing updates and keeping track, you might not need to pull this often except for verification.
    

In practice, when a user connects their store, you’d likely fetch all current inventory once (so Fibermade knows the baseline), then going forward Fibermade can **push** any changes: e.g. if a sale happens (Shopify will decrement its inventory – more on that in orders section), Fibermade might get a webhook and then adjust its own records. Conversely, if stock is manually adjusted in Fibermade (e.g. an admin updates quantity or receives new stock), Fibermade should call the Shopify API to set the new inventory level for that item.

 

Keep in mind Shopify’s behavior on order creation: by default, when an order is created for a tracked product, Shopify will decrement inventory from the “location with the lowest ID” (if you haven’t specified fulfillment location yet). When the order is fulfilled at a specific location, Shopify may adjust the inventory between locations if needed. If you have only one location, you don’t need to worry about that nuance. But it means that as orders come in, Shopify is automatically adjusting inventory levels. Fibermade should capture those adjustments (via order webhooks or polling) so that it can update its own inventory counts accordingly. Since Fibermade is the supposed source of truth, you’ll essentially **mirror** Shopify’s automatic deductions (or you could disable Shopify’s inventory tracking and do it all via Fibermade, but it’s usually easier to let Shopify decrement stock on order and just sync that info back).

 

**Summary:** Use the InventoryLevel API to keep Shopify’s stock in sync with Fibermade. Whenever inventory changes on Fibermade’s side (manual adjustment or via receiving stock), push an update to Shopify (`set` or `adjust`). And whenever an order is placed on Shopify, pull the updated stock (or rely on a webhook) to update Fibermade’s records. This ensures both systems reflect the same available quantities at all times.

## Orders and Fulfillment via API

For orders, Shopify is your system of record (except any Fibermade-specific wholesale orders). This means Fibermade will primarily **read** order data from Shopify. Here’s how you can handle orders and fulfillment:

- **Retrieving Orders:** Shopify’s Orders API allows you to fetch orders, either individually or in bulk. You can retrieve all orders (with pagination), or filter by date, status, etc. Typically, one would set up a webhook for the `orders/create` event so that whenever a new order comes in, Fibermade gets notified and can pull the order data immediately. Alternatively, Fibermade could poll the orders endpoint periodically, but webhooks are more real-time and efficient. An important note: By default, API access to orders is limited to the last 60 days **unless** you request the `read_all_orders` scope on your Shopify app. So make sure your app scopes include `read_orders` (and if needed, `read_all_orders` to get older orders) so you can fetch the entire order history for initial sync or whenever required. Each order object will have details like line items, customer info, totals, etc., which Fibermade can store or use as needed.
    
- **Order Creation/Updates:** It sounds like you don’t plan to create orders in Shopify via Fibermade (since orders flow from the online store into Shopify, and maybe wholesale orders remain separate). But for completeness, the API _can_ create orders (there’s an endpoint to create draft orders or regular orders) – this is often used for migrating orders or creating phone orders, etc. In your case, you probably won’t use that right now. If you ever needed to push an order from Fibermade to Shopify (for example, if a wholesale order placed in Fibermade should also be recorded in Shopify for consistency), you could utilize the Draft Orders API or Orders API to create it. Draft orders allow creation of an order that can later be completed/paid. But again, if not needed, you can ignore this.
    
- **Fulfillment Operations:** Currently, you plan to fulfill orders through Shopify. That means when an order is ready to ship, you or the merchant would normally fulfill it in Shopify’s admin (capture payment, mark as fulfilled, enter tracking, etc.). The good news is **you can automate this via API** if you want to handle it from Fibermade’s interface. Shopify has a Fulfillment API that lets you fulfill orders or individual line items. In the newer API (2023+), fulfillments are managed via **Fulfillment Orders**. Essentially, each order is broken into one or more “fulfillment orders” depending on locations or fulfillment services. But to keep it simple: you can call the API to create a Fulfillment, which will mark items as fulfilled and (optionally) notify the customer.
    
     
    
    For example, the endpoint `POST /admin/api/latest/fulfillments.json` with the right payload will create a fulfillment for given line items of a fulfillment order. This will reduce the remaining open quantity on that order’s fulfillment order and mark those items as fulfilled. You can also include tracking numbers and shipping carrier details in this API call. Shopify’s docs describe: _“You can use the Fulfillment resource to manage fulfillments for fulfillment orders… typically used in apps that perform shipping-related actions, such as making tracking and delivery updates, or creating additional shipments as required for an order.”_. Each Fulfillment object you create can contain a single tracking number (if an order has multiple packages with different tracking, you create multiple fulfillment records). There are also endpoints to update tracking info or cancel a fulfillment if needed.
    
     
    
    In practice, if Fibermade wanted to provide a “Fulfill” button to the user, you’d do something like: Fibermade calls Shopify to fulfill the order (possibly specifying the location ID or letting Shopify choose default), and Shopify will respond with the fulfillment record. This would mark the order as fulfilled in Shopify (and trigger Shopify to send the usual “Your order has shipped” email, unless you suppress notifications in the API call). Fibermade could then show the tracking number in its UI if it was entered or returned. So yes, it’s entirely possible to **handle fulfillment via the API**. This means the merchant wouldn’t have to switch to Shopify admin to fulfill; they could do it from Fibermade if you build that UI around the API.
    
- **Printing Shipping Labels:** This part is a bit tricky. You asked if there’s a way to print shipping labels inside Fibermade via the API. Shopify’s own Shipping Label purchasing (Shopify Shipping) is not exposed through a public API at the moment. In other words, **Shopify does not provide an Admin API endpoint to generate or purchase shipping labels** programmatically – that functionality is confined to the Shopify Admin interface. Other developers have inquired about this and confirmed it’s not available: _“Ideally, I would like to purchase a shipping label via the Shopify API… But it looks like this functionality is not available.”_. The Shopify developer forums and documentation suggest using a third-party shipping service if you need to programmatically get labels (e.g. services like ShipStation, Shippo, Easypost, etc., have APIs that can buy postage and return label PDFs). So, while you **can** mark an order as fulfilled via Shopify’s API and attach a tracking number, you **cannot** directly get a carrier label PDF from Shopify via API.
    
     
    
    **Workaround:** If printing labels within Fibermade is a priority, you’d likely integrate with a shipping API outside of Shopify. For example, Fibermade could use the order’s shipping address to request rates and buy a label from UPS, FedEx, USPS via a service like Shippo; then you could store that label and also call Shopify’s Fulfillment API to fulfill the order with the tracking number from that label. This way, Shopify gets updated that the order is fulfilled (with tracking), and you have the label to print in Fibermade. But this is outside Shopify’s API itself. If the merchant prefers using Shopify’s discounted shipping rates, they would have to continue buying labels in Shopify’s admin for now (or you operate a headless browser or some hack, which is not recommended or officially supported).
    
- **Order Editing and Refunds:** Just for completeness, note that the API also allows certain post-order actions like adding/removing items (order editing API) or issuing refunds via the Refunds API. These are advanced use-cases you might not need immediately. Order editing could be used if Fibermade wanted to modify an order (e.g. apply a discount or change an item) after it’s placed – Shopify does allow this via API with some rules. Refunds/returns can also be handled via the API if needed. For now, it sounds like these are not priorities, but it’s good to know they exist if stage 2 or 3 of your project needs them.
    

In summary, Fibermade will **pull orders** from Shopify (likely via webhooks for new orders, and maybe a manual sync for historical ones) to maintain an order list. Fibermade can also optionally facilitate fulfilling those orders through the Shopify API (marking shipped, adding tracking). The critical part is to ensure Fibermade captures all order updates from Shopify – not just creation, but also fulfillment status or cancellations (webhooks for `orders/paid`, `orders/fulfilled`, `orders/cancelled` might be useful). That way Fibermade’s copy of each order stays current (e.g. knows if an order was canceled or refunded).

## Customer Data Synchronization

If you want customers in Fibermade, you can leverage Shopify’s Customers API to fetch and even create customers. Each Shopify store’s customer list can be retrieved via API, and you can maintain a parallel customer database in Fibermade. Since you’re unsure which should be the source of truth, a common approach is to consider Shopify as the primary for customer info for now (because customers might sign up or update their details through Shopify’s storefront or customer account area). Fibermade would regularly import those details so you have a copy. In the future, if you migrate off Shopify, Fibermade’s copy could become primary.

 

**Capabilities of the Customers API:**

- **Retrieve Customers:** You can fetch a list of all customers with `GET /admin/api/latest/customers.json` (this returns customers with pagination). You can also query customers by parameters or search for specific ones. The response will include their details like name, email, addresses, phone, tags, note, and so on. For example, `GET /customers.json?since_id=LASTID` can pull in batches. Also `GET /customers/{id}.json` to get a single customer by ID. The API will give you each customer’s unique ID, which you should store if you want to later update that customer.
    
- **Create/Update Customers:** Fibermade can also create new customers in Shopify via `POST /admin/api/latest/customers.json`. This could be useful if, say, you have a scenario where a merchant adds a customer in Fibermade (maybe for a wholesale client) and you want to push that to Shopify. You can include all relevant info (name, email, address, etc.) in the create call. Similarly, `PUT /admin/api/latest/customers/{id}.json` updates an existing customer’s information (e.g. update address or note).
    
- **Customer Data Fields:** The customer object in Shopify contains contact info and metadata. The docs say: _“The Customer resource stores information about a shop's customers, such as their contact details, their order history, and whether they've agreed to receive email marketing.”_. It includes default address, an array of addresses, phone, first/last name, email, marketing opt-in, tags (merchants can tag customers too), and state (enabled/disabled if they have an account). One thing to note: if the store uses customer accounts, the API can also send account invite emails or generate activation URLs for new accounts – useful if you create a customer and want them to set up a login.
    
- **Source of Truth Consideration:** If Fibermade is just mirroring Shopify’s customer data, you’d primarily use `GET` calls (or webhooks like `customers/create` and `customers/update`) to know when to update Fibermade’s copy. You might not need to push many changes to Shopify’s customers unless Fibermade is creating customers or modifying them. One scenario for pushing could be if you intend to manage customer tags or notes from Fibermade; you could use the API to update those in Shopify. But generally, **Shopify will naturally accumulate customer data** from store activity (people signing up or checking out as a guest). Make sure to request the `read_customers` (and possibly `write_customers`) scope in your app so you can access this data.
    

Lastly, ensure you handle GDPR webhooks (Shopify will send `customers/redact` or `shop/redact` webhooks if a customer requests data deletion or if the store uninstalls your app – since you’re storing customer data in Fibermade, you need to honor those for compliance). This is a bit tangential, but since you’ll have copies of customer personal data, be aware of privacy requirements.

## Store Settings and Configuration via API

You inquired about _“global settings… list of settings I can change via the API.”_ Generally speaking, **Shopify does not allow changing core store settings through the API** – most store configuration is only editable by the merchant in the Shopify admin interface (or via Partner API in some cases). The Shopify Admin API focuses on store data (products, orders, etc.), not the store’s own configuration. For example, you cannot programmatically change the store name, address, currency, checkout settings, tax settings, etc., via the Admin API. Those are merchant-controlled settings.

 

The Admin API’s **Shop** resource gives you read-only access to basic store information: shop name, email, currency, domain, etc., but explicitly cannot be written to. As the docs state: _“The Shop resource is a collection of general business and store management settings and information about the store. The resource lets you retrieve information about the store, but it **doesn't let you update** any information. Only the merchant can update this information from inside the Shopify admin.”_. In other words, you can GET `/admin/api/latest/shop.json` to read things like the store’s contact email, myshopify domain, etc., but you cannot PUT to that endpoint.

 

**So what _can_ be configured via API?** A few things, though they may not be “settings” in the conventional sense:

- **Store Policies:** The refund policy, privacy policy, terms of service – these are editable via Shopify admin, and there is a way to update them via the GraphQL API (not REST). Shopify’s GraphQL Admin has a mutation `shopPolicyUpdate` that can update those policy texts. This requires certain scopes (like `write_legal_policies`). It’s a somewhat niche case – mostly apps that help merchants manage policies would use it. If for some reason Fibermade wanted to set a policy (perhaps not likely), it’s possible there.
    
- **Payment/Shipping settings:** These are **not** exposed for modification via Admin API for security and complexity reasons. For instance, you can’t change the payment gateways or shipping zones through the API. You _can_ read some info – e.g., via the Shop resource you can see what countries the store ships to, or whether taxes are included, etc., but not change them.
    
- **Locations:** You can create additional locations via API (there’s a Location API to add locations, primarily used by inventory apps or fulfillment services). But global settings like location address changes might still need admin UI.
    
- **Checkout settings:** Not directly accessible via API (except through some advanced GraphQL on Checkout if you’re a Plus merchant).
    
- **Themes/Online Store settings:** While not exactly a “setting”, you might consider theme customization as a global configuration. You _can_ upload theme assets or even change the live theme via API (more on this in the next section). For example, you could programmatically create a theme or update theme files, which in effect changes the store’s appearance/behavior – but Shopify requires an app to have specific access and it’s a sensitive action (starting 2023-04, editing a live theme via API for public apps requires a special protected scope due to the potential for abuse).
    

Given that Fibermade’s goal is more about data syncing, you probably won’t need to change store settings via API. Instead, focus on storing any Fibermade-specific settings on your side. If you need to store some configuration related to the Shopify store (for example, a flag in Fibermade that decides something about sync behavior), you might use Shopify’s Metafields (see below) or just keep it in your database.

 

One useful thing to know: **Shopify Metafields on the Shop resource** can act as a kind of custom setting storage. Shopify allows adding metafields to the Shop itself (owner_resource "shop"). So Fibermade’s app could, for instance, save a metafield on the store like `fibermade.syncMode = "overwrite"` or something, which you can read via API. Only your app would know the meaning. This isn’t a built-in concept, but metafields are a flexible way to store custom data on Shopify objects (products, orders, shop, etc.) that the merchant can also edit if you expose it. It’s an option if you needed to store some toggles or config that both Shopify and Fibermade should be aware of.

 

**Conclusion on settings:** Most shop settings can’t be overridden remotely for security reasons. The merchant will configure their Shopify store (tax rules, email templates, etc.) in Shopify’s admin. Your integration points will mainly be around data entities rather than global preferences. If the question was about overriding some sync behavior (like choosing Shopify vs Fibermade as master), that logic would live in Fibermade’s code and database, not as a Shopify setting.

## Storefront & Website – What Can the API Change?

Even though you plan to eventually replace Shopify’s storefront (stage 2), it’s good to know what you _could_ do with the API in the meantime to affect the website. Shopify offers a few mechanisms to alter or extend the storefront via API:

- **Theme Assets API:** Shopify lets apps read and modify the theme files (HTML/Liquid, CSS, JS, images) through the Asset resource. In essence, you can upload, update, or delete files in a theme. For example, you could have Fibermade’s app automatically add a snippet to the theme or create a new template file. The Asset API documentation says: _“You can use the Asset resource to add, change, or remove asset files from a shop's theme.”_. This includes templates, sections, snippets, images, etc. So, technically, Fibermade could alter the look or content of the Shopify storefront by pushing theme file changes (for instance, adding a Fibermade banner, or adjusting some Liquid code to insert something). **Important:** If this is a public app, Shopify now requires a special permission (`write_themes` with an additional approval) to do this, as it’s powerful. If it’s a private app for the merchant’s own store, you have more freedom. Additionally, many stores might prefer apps not to heavily modify their theme unless necessary. But it’s definitely possible. For example, if you wanted to add a little widget to the storefront that interacts with Fibermade, you might either use Asset API to include the widget’s code or the ScriptTag API (described next).
    
- **ScriptTag API:** This API allows you to inject external JavaScript into the storefront pages without editing the theme files directly. A script tag is essentially a remote JS file that Shopify will include on the storefront (or order status page) as specified. The docs describe it: _“The ScriptTag resource represents remote JavaScript code that is loaded into the pages of a shop's storefront or the order status page of checkout. This lets you add functionality to those pages without using theme templates.”_. You can create script tags via `POST /admin/api/latest/script_tags.json` by providing a script URL and where to display it (all pages or just checkout thank-you page). For instance, many apps use ScriptTag to add their custom analytics or UI widget to every page. In Fibermade’s case, if you wanted to, say, overlay some info from Fibermade onto the Shopify storefront (like a custom badge on products), you could host a JS file that does that and then use the ScriptTag API to insert it. One caveat: if this is a public app intended for multiple merchants, note that Shopify is moving away from ScriptTag for Online Store 2.0 themes in favor of App Extensions. But ScriptTag still works for “vintage” themes and is useful in private contexts. Since you might replace the store soon, you may not invest much in this, but it’s good to know it exists. Removing the script tag (via API or on app uninstall) will remove the injected code from pages.
    
- **Pages, Blogs, Redirects, etc.:** Shopify’s API can also manage content like store pages, blog posts, and navigational redirects. If needed, you could create informational pages via API or handle URL redirects. These aren’t typically necessary for an integration like Fibermade, but worth noting. For example, `POST /pages.json` can create a new webpage in the store (like a hidden landing page).
    
- **Storefront API (GraphQL):** This is actually a different API that’s used for headless commerce – it lets you fetch products, collections, create checkout, etc., from a storefront perspective. You likely _won’t_ need this now because you’re not building a custom storefront yet. But if stage 2 involves a custom front-end (not using Shopify’s online store at all), you might use the Storefront API or switch completely to Fibermade serving the website. At present, your question is more about affecting the existing Shopify storefront. Through the Admin API, your means to do that are basically theme edits or script injection as described above.
    
- **Limitations:** Some parts of the storefront cannot be fully changed via API due to Shopify’s platform guardrails. For example, you cannot remove the “Powered by Shopify” via an API call (except by editing theme code), and you cannot alter checkout behavior except through official channels (if you were Shopify Plus, you could upload checkout scripts or use Functions, but that’s beyond normal API use). So major changes like customizing checkout, adding fields to checkout, etc., are not possible via the Admin API for a regular store.
    

In summary, you **can** influence the Shopify website with the API: by programmatically editing theme files or by injecting scripts. These allow you to add new features or content to the storefront. Since you plan to replace the Shopify storefront eventually, you might not need to do much of this now. But as an example, if you wanted to show real-time inventory data from Fibermade on the Shopify product page, you could use ScriptTag to inject a script that fetches data from Fibermade’s API and displays it. Or you could add a section to the theme that the merchant can enable. It’s all doable with some development effort. Just weigh it against the timeline of moving off Shopify – if that’s soon, it might not be worth heavy customization now.

## Future Considerations and API Features to Explore

Aside from the immediate integration points, there are several **Shopify API features and best practices** that could help your Fibermade integration in the long run. Here’s a list of things you should look into or keep in mind for the future:

- **Webhooks for Real-Time Sync:** Set up Shopify webhooks to avoid constant polling and to keep data in sync efficiently. Webhooks will push notifications to Fibermade whenever certain events occur – e.g. product updated, order created, inventory level changed, customer created. As Shopify’s docs note, _“After you’ve subscribed to a webhook topic, your app can execute code immediately after specific events occur… instead of having to make API calls periodically to check their status. For example, you can rely on webhooks to trigger an action in your app when a merchant creates a new product in their Shopify admin.”_. Using webhooks means Fibermade will get a POST payload as soon as something changes, allowing you to update your database instantly. This is crucial for things like new orders (so you don’t miss any) and also useful for inventory (if someone edits a product or a sale happens).
    
- **Metafields (Custom Data):** Shopify Metafields allow you to attach custom fields to almost any object (products, variants, customers, orders, collections, and even the shop itself). _“Metafields are a flexible way to attach additional information to a Shopify resource (e.g. Product, Collection, etc.).”_. This is useful if Fibermade has some data that Shopify doesn’t natively support. For example, maybe Fibermade tracks a “cost price” for products for internal use – you could store that in a product metafield on Shopify so that data is accessible in Shopify as well. Or if you want to mark that an order has been synced to an external system, you could put a metafield on the order. Metafields can be managed via the API (both REST and GraphQL) – you can create, update, delete metafields on any resource given you have the permission. In the Shopify admin, merchants can now even define and edit metafields (especially for products and variants), so using metafields can integrate well with merchant workflows. Consider leveraging metafields to enrich data exchange between Fibermade and Shopify where appropriate.
    
- **GraphQL Admin API (Modern Approach):** Shopify is moving more toward GraphQL for the Admin API. In fact, since April 2025 all new public apps must use GraphQL instead of REST. GraphQL can be more efficient (you can fetch exactly the fields you need and combine queries). It also has access to newer features (like the newer discounts APIs, bulk operations, etc.). While the REST API will continue to work for private apps or older integrations for some time, it’s a good idea to familiarize yourself with the GraphQL Admin API. It might help you simplify certain calls – for example, you could query a product and its inventory in one request, whereas with REST you’d do multiple requests. GraphQL also tends to have newer capabilities (for instance, the discounts and some customer features are GraphQL-only now). Over time, migrating parts of your integration to GraphQL could yield performance benefits.
    
- **Bulk Operations API:** If you need to sync or migrate large amounts of data (say a one-time full import of all products or orders), look into Shopify’s GraphQL bulk operations. This feature allows asynchronous retrieval of massive data sets without hitting rate limits. _“With the GraphQL Admin API, you can use bulk operations to asynchronously fetch data in bulk. The API is designed to reduce complexity when dealing with pagination of large volumes of data… Shopify’s infrastructure does the hard work of executing your query, and then provides you with a URL where you can download all of the data.”_. For example, you could run a bulk query to get all products and all their variants in one go (the result is provided as a JSONL file you download). Bulk operations are great for initial data seeding of Fibermade or periodic full syncs. They can also do bulk mutations (like updating many products at once) by uploading a payload. Keep this in your toolbox, especially if some of your users have large catalogs or order histories.
    
- **Discounts and Promotions:** You mentioned that in the future you want to manage discounts from Fibermade. Shopify’s discounts system has an API as well, but it has evolved. The older method was to create a Price Rule and then a Discount Code under it via REST. The newer (and recommended) way is via GraphQL using Discount integrations (which tie into Shopify Functions if you create custom discount types). For basic needs, you can still create discount codes via API. The docs note: _“We recommend using the GraphQL Admin API to manage discounts. The discount types available in the GraphQL Admin API are intended to replace the REST Admin PriceRule and DiscountCode resources.”_. So, if Fibermade wants to create discount codes (say a merchant sets up a 10% off code in Fibermade), you would likely use the GraphQL `discountCodeBasicCreate` mutation or similar. This will let you define the details (percentage or fixed amount off, usage limits, etc.) and produce a code that’s active in the Shopify store. There are also APIs for automatic discounts (which don’t require a code). It’s a complex area, so when you get to it, be prepared to dive into Shopify’s discount API documentation and possibly the newer Function-based discounts. But yes, managing discounts is feasible via the API – it’s just one of the more advanced things.
    
- **App Extensions (Post-Purchase, etc.):** If at some point you remain on Shopify and want to extend functionality (like a custom post-purchase upsell, or an admin link), Shopify has an app extension framework. This might be beyond the scope of integration, but worth knowing that certain things (like embedding an iframe UI in the Shopify Admin, or adding a link in the order detail page) can be done if Fibermade is a public app. This might not be relevant if the goal is to fully move off, but I include it as a future consideration in case plans change and you maintain some presence in Shopify’s UI.
    
- **API Rate Limiting and Performance:** As you integrate, remember that Shopify’s Admin API (REST or GraphQL) has rate limits. REST allows about 2 calls per second on average (with a bucket of 40 in quick burst); GraphQL has a cost-based throttle. If you foresee heavy data transfer, design your integration to be mindful of these limits. Use bulk operations for big jobs, use webhooks to avoid excessive polling, and implement retry logic for rate limit errors. Also, try to do updates in batches when possible (Shopify REST has some batch endpoints like Product/Variant bulk, and GraphQL lets you send multiple mutations in one call or use bulk mutation jobs). This will all become important as your user base grows or if some stores have thousands of products.
    

By exploring and utilizing the above, you’ll make Fibermade’s integration more robust and scalable. For now, focus on the core – products, inventory, orders, customers – but keep these additional tools in mind as you expand functionality. They will help ensure that Fibermade can smoothly integrate with Shopify and even gradually reduce dependency on it when the time comes to transition off.

Citations

[

![](https://www.google.com/s2/favicons?domain=https://shopify.dev&sz=32)

Product

https://shopify.dev/docs/api/admin-rest/latest/resources/product

](https://shopify.dev/docs/api/admin-rest/latest/resources/product#:~:text=Endpoints)[

![](https://www.google.com/s2/favicons?domain=https://shopify.dev&sz=32)

Product

https://shopify.dev/docs/api/admin-rest/latest/resources/product

](https://shopify.dev/docs/api/admin-rest/latest/resources/product#:~:text=productsCount)[

![](https://www.google.com/s2/favicons?domain=https://shopify.dev&sz=32)

Product

https://shopify.dev/docs/api/admin-rest/latest/resources/product

](https://shopify.dev/docs/api/admin-rest/latest/resources/product#:~:text=The%20Product%20resource%20lets%20you,add%20or%20update%20%2015)[

![](https://www.google.com/s2/favicons?domain=https://shopify.dev&sz=32)

Product Variant

https://shopify.dev/docs/api/admin-rest/latest/resources/product-variant

](https://shopify.dev/docs/api/admin-rest/latest/resources/product-variant#:~:text=)[

![](https://www.google.com/s2/favicons?domain=https://shopify.dev&sz=32)

Product Variant

https://shopify.dev/docs/api/admin-rest/latest/resources/product-variant

](https://shopify.dev/docs/api/admin-rest/latest/resources/product-variant#:~:text=)[

![](https://www.google.com/s2/favicons?domain=https://shopify.dev&sz=32)

tagsAdd - GraphQL Admin

https://shopify.dev/docs/api/admin-graphql/latest/mutations/tagsAdd

](https://shopify.dev/docs/api/admin-graphql/latest/mutations/tagsAdd#:~:text=Adds%20tags%20to%20a%20resource,16%2C%20and%20Article)[

![](https://www.google.com/s2/favicons?domain=https://shopify.dev&sz=32)

Collection

https://shopify.dev/docs/api/admin-rest/latest/resources/collection

](https://shopify.dev/docs/api/admin-rest/latest/resources/collection#:~:text=,see%20the%20%2014%20resource)[

![](https://www.google.com/s2/favicons?domain=https://shopify.dev&sz=32)

Collection

https://shopify.dev/docs/api/admin-rest/latest/resources/collection

](https://shopify.dev/docs/api/admin-rest/latest/resources/collection#:~:text=SmartCollection%20resource)[

![](https://www.google.com/s2/favicons?domain=https://shopify.dev&sz=32)

Product

https://shopify.dev/docs/api/admin-rest/latest/resources/product

](https://shopify.dev/docs/api/admin-rest/latest/resources/product#:~:text=options)[

![](https://www.google.com/s2/favicons?domain=https://shopify.dev&sz=32)

Product

https://shopify.dev/docs/api/admin-rest/latest/resources/product

](https://shopify.dev/docs/api/admin-rest/latest/resources/product#:~:text=)[

![](https://www.google.com/s2/favicons?domain=https://shopify.dev&sz=32)

InventoryLevel

https://shopify.dev/docs/api/admin-rest/latest/resources/inventorylevel

](https://shopify.dev/docs/api/admin-rest/latest/resources/inventorylevel#:~:text=All%20product%20variants%20have%20a,only%20to%20that%20product%20variant)[

![](https://www.google.com/s2/favicons?domain=https://shopify.dev&sz=32)

InventoryLevel

https://shopify.dev/docs/api/admin-rest/latest/resources/inventorylevel

](https://shopify.dev/docs/api/admin-rest/latest/resources/inventorylevel#:~:text=,managing%20inventory%2C%20shipping%2C%20and%20fulfillments)[

![](https://www.google.com/s2/favicons?domain=https://shopify.dev&sz=32)

InventoryLevel

https://shopify.dev/docs/api/admin-rest/latest/resources/inventorylevel

](https://shopify.dev/docs/api/admin-rest/latest/resources/inventorylevel#:~:text=Inventory%20items%20are%20associated%20with,where%20the%20item%20is%20stocked)[

![](https://www.google.com/s2/favicons?domain=https://shopify.dev&sz=32)

Product Variant

https://shopify.dev/docs/api/admin-rest/latest/resources/product-variant

](https://shopify.dev/docs/api/admin-rest/latest/resources/product-variant#:~:text=Important)[

![](https://www.google.com/s2/favicons?domain=https://shopify.dev&sz=32)

InventoryLevel

https://shopify.dev/docs/api/admin-rest/latest/resources/inventorylevel

](https://shopify.dev/docs/api/admin-rest/latest/resources/inventorylevel#:~:text=)[

![](https://www.google.com/s2/favicons?domain=https://shopify.dev&sz=32)

InventoryLevel

https://shopify.dev/docs/api/admin-rest/latest/resources/inventorylevel

](https://shopify.dev/docs/api/admin-rest/latest/resources/inventorylevel#:~:text=When%20a%20product%20variant%20that,that%20has%20the%20lowest%20ID)[

![](https://www.google.com/s2/favicons?domain=https://shopify.dev&sz=32)

InventoryLevel

https://shopify.dev/docs/api/admin-rest/latest/resources/inventorylevel

](https://shopify.dev/docs/api/admin-rest/latest/resources/inventorylevel#:~:text=When%20an%20order%20is%20fulfilled%2C,adjusts%20the%20inventory%20if%20necessary)[

![](https://www.google.com/s2/favicons?domain=https://community.shopify.com&sz=32)

Shopify API - Retrieve full customer order history

https://community.shopify.com/t/shopify-api-retrieve-full-customer-order-history/315302

](https://community.shopify.com/t/shopify-api-retrieve-full-customer-order-history/315302#:~:text=Shopify%20API%20,unless%20the%20app%20has)[

![](https://www.google.com/s2/favicons?domain=https://shopify.dev&sz=32)

Fulfillment

https://shopify.dev/docs/api/admin-rest/latest/resources/fulfillment

](https://shopify.dev/docs/api/admin-rest/latest/resources/fulfillment#:~:text=Endpoints)[

![](https://www.google.com/s2/favicons?domain=https://shopify.dev&sz=32)

Fulfillment

https://shopify.dev/docs/api/admin-rest/latest/resources/fulfillment

](https://shopify.dev/docs/api/admin-rest/latest/resources/fulfillment#:~:text=You%20can%20use%20the%20,manage%20fulfillments%20for%20fulfillment%20orders)[

![](https://www.google.com/s2/favicons?domain=https://shopify.dev&sz=32)

Fulfillment

https://shopify.dev/docs/api/admin-rest/latest/resources/fulfillment

](https://shopify.dev/docs/api/admin-rest/latest/resources/fulfillment#:~:text=This%20resource%20is%20typically%20used,an%20order%20or%20fulfillment%20order)[

![](https://www.google.com/s2/favicons?domain=https://shopify.dev&sz=32)

Fulfillment

https://shopify.dev/docs/api/admin-rest/latest/resources/fulfillment

](https://shopify.dev/docs/api/admin-rest/latest/resources/fulfillment#:~:text=)[

![](https://www.google.com/s2/favicons?domain=https://community.shopify.dev&sz=32)

Generating Shipping Labels via API - Fulfillment & Inventory - Shopify Developer Community Forums

https://community.shopify.dev/t/generating-shipping-labels-via-api/5179

](https://community.shopify.dev/t/generating-shipping-labels-via-api/5179#:~:text=,this%20functionality%20is%20not%20available)[

![](https://www.google.com/s2/favicons?domain=https://shopify.dev&sz=32)

Customer

https://shopify.dev/docs/api/admin-rest/latest/resources/customer

](https://shopify.dev/docs/api/admin-rest/latest/resources/customer#:~:text=)[

![](https://www.google.com/s2/favicons?domain=https://shopify.dev&sz=32)

Customer

https://shopify.dev/docs/api/admin-rest/latest/resources/customer

](https://shopify.dev/docs/api/admin-rest/latest/resources/customer#:~:text=)[

![](https://www.google.com/s2/favicons?domain=https://shopify.dev&sz=32)

Customer

https://shopify.dev/docs/api/admin-rest/latest/resources/customer

](https://shopify.dev/docs/api/admin-rest/latest/resources/customer#:~:text=)[

![](https://www.google.com/s2/favicons?domain=https://shopify.dev&sz=32)

Customer

https://shopify.dev/docs/api/admin-rest/latest/resources/customer

](https://shopify.dev/docs/api/admin-rest/latest/resources/customer#:~:text=Requires%20access%20to%20protected%20customer,data)[

![](https://www.google.com/s2/favicons?domain=https://shopify.dev&sz=32)

Customer

https://shopify.dev/docs/api/admin-rest/latest/resources/customer

](https://shopify.dev/docs/api/admin-rest/latest/resources/customer#:~:text=,json)[

![](https://www.google.com/s2/favicons?domain=https://shopify.dev&sz=32)

Shop

https://shopify.dev/docs/api/admin-rest/latest/resources/shop

](https://shopify.dev/docs/api/admin-rest/latest/resources/shop#:~:text=The%20Shop%20resource%20is%20a,from%20inside%20the%20Shopify%20admin)[

![](https://www.google.com/s2/favicons?domain=https://shopify.dev&sz=32)

ShopPolicy - GraphQL Admin

https://shopify.dev/docs/api/admin-graphql/latest/objects/ShopPolicy

](https://shopify.dev/docs/api/admin-graphql/latest/objects/ShopPolicy#:~:text=Anchor%20to%20shopPolicyUpdate%20%2035)[

![](https://www.google.com/s2/favicons?domain=https://shopify.dev&sz=32)

Asset

https://shopify.dev/docs/api/admin-rest/latest/resources/asset

](https://shopify.dev/docs/api/admin-rest/latest/resources/asset#:~:text=A%20theme%27s%20assets%20include%20its,directories%2C%20refer%20to%20Theme%20architecture)[

![](https://www.google.com/s2/favicons?domain=https://shopify.dev&sz=32)

ScriptTag

https://shopify.dev/docs/api/admin-rest/latest/resources/scripttag

](https://shopify.dev/docs/api/admin-rest/latest/resources/scripttag#:~:text=The%20ScriptTag%20resource%20represents%20remote,pages%20without%20using%20theme%20templates)[

![](https://www.google.com/s2/favicons?domain=https://shopify.dev&sz=32)

ScriptTag

https://shopify.dev/docs/api/admin-rest/latest/resources/scripttag

](https://shopify.dev/docs/api/admin-rest/latest/resources/scripttag#:~:text=)[

![](https://www.google.com/s2/favicons?domain=https://shopify.dev&sz=32)

ScriptTag

https://shopify.dev/docs/api/admin-rest/latest/resources/scripttag

](https://shopify.dev/docs/api/admin-rest/latest/resources/scripttag#:~:text=display_scope)[

![](https://www.google.com/s2/favicons?domain=https://shopify.dev&sz=32)

Webhook

https://shopify.dev/docs/api/admin-rest/latest/resources/webhook

](https://shopify.dev/docs/api/admin-rest/latest/resources/webhook#:~:text=You%20can%20use%20webhook%20subscriptions,periodically%20to%20check%20their%20status)[

![](https://www.google.com/s2/favicons?domain=https://shopify.dev&sz=32)

Metafield

https://shopify.dev/docs/api/admin-rest/latest/resources/metafield

](https://shopify.dev/docs/api/admin-rest/latest/resources/metafield#:~:text=Metafields%20are%20a%20flexible%20way,type%20information%20for%20that%20context)[

![](https://www.google.com/s2/favicons?domain=https://shopify.dev&sz=32)

Product

https://shopify.dev/docs/api/admin-rest/latest/resources/product

](https://shopify.dev/docs/api/admin-rest/latest/resources/product#:~:text=The%20REST%20Admin%20API%20is,steps%2C%20visit%20our%20%2012)[

![](https://www.google.com/s2/favicons?domain=https://shopify.dev&sz=32)

Perform bulk operations with the GraphQL Admin API

https://shopify.dev/docs/api/usage/bulk-operations/queries

](https://shopify.dev/docs/api/usage/bulk-operations/queries#:~:text=With%20the%20GraphQL%20Admin%20API%2C,the%20GraphQL%20Admin%20API%20schema)[

![](https://www.google.com/s2/favicons?domain=https://shopify.dev&sz=32)

DiscountCode

https://shopify.dev/docs/api/admin-rest/latest/resources/discountcode

](https://shopify.dev/docs/api/admin-rest/latest/resources/discountcode#:~:text=Note)