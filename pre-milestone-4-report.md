# Pre-Milestone Report: Milestone 4 — Catalog Processor
## Revised Architecture

---

### 1. GOAL

Create the **normalization layer** that converts data from the Product Query Engine and Variation Engine into a unified, structured `CatalogItem` value object.

This milestone establishes the data contract that all future presentation layers (Template Engine, Elementor, Print Engine, Output) will consume. It is purely about **data transformation**, not rendering.

**Input:** Already-normalized data arrays from `ProductRepositoryInterface::find()` and `VariationRepositoryInterface::get_variations()`.

**Output:** A flat list of `CatalogItem` value objects, each representing one sellable entry in the catalog.

**What this milestone does NOT do:**
- No HTML generation
- No template rendering
- No Elementor integration
- No print/layout logic
- No PDF generation
- No WordPress presentation hooks
- No direct database queries

---

### 2. CURRENT ARCHITECTURE — ACTUAL CONTRACTS

After re-inspecting the codebase, here are the **exact** contracts as they exist today:

#### ProductQueryResult
```php
// src/Product/ProductQueryResult.php
private array $products; // @var array<\WC_Product|array<string, mixed>|int>
public function get_products(): array;   // Returns raw types (mixed)
public function get_ids(): array;        // Extracts integer IDs only
```

The `query()` method in `WooCommerceProductRepository` calls `wc_get_products()` with `return => 'objects'` (the WooCommerce default), so **the products array contains `\WC_Product` objects**, not normalized arrays.

#### ProductRepositoryInterface
```php
public function query( ProductQueryArgs $args ): ProductQueryResult;
public function find( int $product_id ): ?array;  // Returns normalized array
public function exists( int $product_id ): bool;
```

The `find()` method already returns a **normalized array** with keys:
`id`, `type`, `status`, `name`, `slug`, `sku`, `price`, `stock`, `categories`, `tags`.

#### VariationQueryResult
```php
// src/Variation/VariationQueryResult.php
private int $parent_product_id;
private array $variations; // @var array<int, array<string, mixed>>
public function get_variations(): array;  // Returns normalized arrays only
public function get_variation_ids(): array;
```

The `get_variations()` method returns **already-normalized arrays** from `extract_variation_data()`:
`id`, `parent_id`, `type`, `status`, `name`, `sku`, `price`, `regular_price`, `sale_price`, `stock_status`, `stock_quantity`, `purchasable`, `visible`, `attributes`, `image`, `dimensions`.

#### VariationService
```php
public function expand( ProductQueryResult $product_result, VariationQueryArgs $variation_args ): VariationQueryResult;
public function get_product_variations( int $product_id, VariationQueryArgs $variation_args ): VariationQueryResult;
public function has_variations( int $product_id ): bool;
```

#### Key Discovery
- **Products** from `ProductQueryResult` are **NOT normalized** (raw `\WC_Product` objects)
- **Variations** from `VariationQueryResult` **ARE already normalized** (plain arrays)
- `ProductRepositoryInterface::find()` returns normalized arrays but requires a separate API call per product

This asymmetry is the core architectural problem Milestone 4 must solve.

---

### 3. CATALOG ITEM DESIGN

#### Conceptual model

A `CatalogItem` represents a single sellable entry in a catalog — either a **standalone product** or a **variation of a variable product**. It is a value object: immutable after construction, serializable, and type-safe.

Both simple products and variations share the same `CatalogItem` type. The `type` field distinguishes them. This is consistent with CLAUDE.md Rule 11: *"Treat Products and Variations as manageable Catalog Items."*

#### Fields

| Field | Type | Required | Source | Description |
|-------|------|----------|--------|-------------|
| `id` | `int` | Yes | Product/Variation ID | The WooCommerce product ID. For variations, this is the variation ID. |
| `type` | `string` | Yes | `product` / `variation` | Identifies the item kind. Drives template branching. |
| `parent_product_id` | `int` | Yes | 0 for products | For variations: the parent product ID. Always 0 for simple products. |
| `title` | `string` | Yes | `get_name()` | Product or variation display name. |
| `sku` | `string` | Yes | `get_sku()` | Stock Keeping Unit. Empty string if not set. |
| `price` | `string` | Yes | Active price | Sale price if on sale, otherwise regular price. Stored as string. |
| `regular_price` | `string` | Yes | `get_regular_price()` | Base price. Empty string if not set. |
| `sale_price` | `string` | Yes | `get_sale_price()` | Sale price. Empty string if no sale. |
| `description` | `string` | Yes | `get_description()` | Full product description. May contain HTML. Raw data, not escaped. |
| `short_description` | `string` | Yes | `get_short_description()` | Excerpt. May contain HTML. Raw data, not escaped. |
| `image` | `array\|null` | No | `get_image_id()` + `wp_get_attachment_image_src()` | `{ id, src, width, height }`. Null if no image. |
| `gallery` | `array<int>` | No | `get_gallery_image_ids()` | Attachment IDs. Empty array if none. |
| `categories` | `array<int>` | No | `get_category_ids()` | Category IDs. Empty array if none. |
| `tags` | `array<int>` | No | `get_tag_ids()` | Tag IDs. Empty array if none. |
| `attributes` | `array<string, string>` | No | Variation attributes only | Key-value pairs (e.g., `['Color' => 'Red', 'Size' => 'L']`). Empty array for simple products. |
| `stock_status` | `string` | Yes | `get_stock_status()` | `'instock'`, `'outofstock'`, or `'onbackorder'`. |
| `stock_quantity` | `int\|null` | No | `get_stock_quantity()` | Number of items in stock. Null if not tracked. |
| `permalink` | `string` | Yes | `get_permalink()` | WordPress URL for this item. |
| `parent_product` | `array\|null` | No | Parent product data | For variations: `{ id, name, sku, permalink }`. Null for products. |
| `variation_table` | `array\|null` | No | Table mode data | For table mode parents: structured variation data. Null for all other modes. |
| `metadata` | `array<string, mixed>` | No | Extra product data | Dimensions, weight, shipping class, custom meta. Kept minimal. |

#### Design decisions

**Why these fields and not more:**
- **No `_links` or REST API metadata** — This is a domain object, not an API response.
- **No price objects** — Prices are strings. The template layer handles formatting (`wc_price()`). Storing formatted prices would require the processor to call WordPress functions.
- **`description` and `short_description` stored raw** — These may contain HTML from WooCommerce. Sanitization/escaping is a presentation concern (Milestone 5), not a data normalization concern.
- **`image` stores resolved URLs** — Image resolution requires `wp_get_attachment_image_src()`, which is a data retrieval concern appropriate for the processor. The template layer should not make WordPress attachment calls.
- **`parent_product` as a nested object** — Provides the template layer with parent context for variations without requiring a separate lookup. Contains only display-relevant fields.
- **`variation_table` as a dedicated field** — Not buried in generic `metadata`. See Section 6 for the full design.
- **No `status` field** — The Product Query Engine already filters by status. The CatalogItem represents visible catalog entries only.

**Immutability:**
- `final class` with typed properties set in the constructor.
- No setters. Consistent with `ProductQueryArgs`, `VariationQueryArgs`, `Catalog`.
- `to_array()` returns a complete serializable representation.

**Factory-based construction:**
- `CatalogItem::from_product(array $data)` — for simple/variable products
- `CatalogItem::from_variation(array $data, ?array $parent_context = null)` — for variations
- Both handle null/missing data gracefully with sensible defaults.

**Type-checking helpers:**
- `is_product(): bool`
- `is_variation(): bool`
- `has_variations(): bool` — for table mode detection
- `is_variable_product(): bool` — for variation mode detection

---

### 4. PRODUCT NORMALIZATION — THE WC_Product BOUNDARY PROBLEM

#### The core tension

`ProductQueryResult` currently contains `\WC_Product` objects (from `wc_get_products()` with default `return='objects'`). The security review flagged this as a violation: *"Core should not depend on integrations."*

However, the `ProductRepositoryInterface` and `ProductQueryResult` contracts are **already established** in Milestone 2. Modifying them to remove `\WC_Product` would be a breaking change to the public API.

#### The solution: Normalize inside the processor, not the repository

The `CatalogProcessor` will implement its own internal normalization step for `\WC_Product` objects. This is the correct boundary because:

1. **The processor IS the consumer that needs normalized data.** It is the layer responsible for converting raw WooCommerce data into the `CatalogItem` contract.
2. **The repository already normalizes variations** via `extract_variation_data()`. It is consistent for the processor to normalize products using the same pattern.
3. **The `\WC_Product` dependency is contained.** The processor imports `\WC_Product` only in its normalization helper. The `CatalogItem` itself never references `\WC_Product`.
4. **No interface changes required.** The existing `ProductRepositoryInterface` and `ProductQueryResult` contracts remain untouched.

#### Normalization flow for products:

```
For each item in ProductQueryResult::get_products():
  If item is \WC_Product:
    1. Extract data via getter methods (get_id, get_name, get_sku, etc.)
    2. Resolve image via wp_get_attachment_image_src()
    3. Build plain array
    4. Pass to CatalogItem::from_product()
  If item is int:
    1. Call ProductRepositoryInterface::find($id) for full data
    2. Pass array to CatalogItem::from_product()
  If item is array:
    1. Pass directly to CatalogItem::from_product()
```

#### Parent context for variations:

When normalizing a variation, the processor needs parent product data (for `parent_product` field). It obtains this by calling `ProductRepositoryInterface::find($parent_id)` — a single additional call per unique parent product, cached in-memory during the normalization pass.

---

### 5. VARIATION NORMALIZATION

#### Input: VariationQueryResult

`VariationQueryResult::get_variations()` already returns normalized arrays from `WooCommerceVariationRepository::extract_variation_data()`. These arrays contain:
`id`, `parent_id`, `type`, `status`, `name`, `sku`, `price`, `regular_price`, `sale_price`, `stock_status`, `stock_quantity`, `purchasable`, `visible`, `attributes`, `image`, `dimensions`.

#### Normalization flow for variations:

```
For each variation in VariationQueryResult::get_variations():
  1. Get parent product context via ProductRepositoryInterface::find($variation['parent_id'])
  2. Pass variation data + parent context to CatalogItem::from_variation()
  3. CatalogItem::from_variation() builds the CatalogItem with:
     - type = 'variation'
     - parent_product_id = $variation['parent_id']
     - parent_product = { id, name, sku, permalink } from parent context
     - All variation fields populated
```

#### Why the processor handles parent lookups:

The processor already has access to `ProductRepositoryInterface` (injected). It batches the parent lookups — one per unique parent product ID — rather than making redundant calls for each variation.

---

### 6. VARIATION MODES AND CATALOGITEM STRUCTURE

#### Mode: `parent`
- Products remain as `CatalogItem` with `type = 'product'`
- `parent_product_id = 0`, `attributes = []`, `variation_table = null`
- No variation expansion occurs

#### Mode: `all`
- Every variation becomes its own `CatalogItem`
- `type = 'variation'`, `parent_product_id = <parent ID>`
- `parent_product` populated with parent context
- `attributes` populated from variation attributes
- The parent product itself is NOT included as a separate CatalogItem

#### Mode: `selected`
- Only the user-selected single variation per parent
- Same structure as `all` mode, filtered to selected IDs

#### Mode: `multiple`
- Multiple selected variations per parent
- Same structure as `all` mode, filtered to selected IDs

#### Mode: `table` — Detailed Design

In table mode, the parent product must appear alongside its variation data. The representation must be:
- **Self-contained**: The parent `CatalogItem` carries all variation data it needs
- **Presentation-agnostic**: No HTML, no CSS, no rendering logic
- **Type-safe**: The variation data has a defined structure, not buried in generic arrays

**Design: Dedicated `variation_table` field on `CatalogItem`**

```php
// On the parent CatalogItem in table mode:
$catalogItem = CatalogItem::from_product($product_data);
$catalogItem = $catalogItem->with_variation_table([
    'variations' => [
        [
            'id'           => 241,
            'title'        => 'Red / Large',
            'attributes'   => ['Color' => 'Red', 'Size' => 'Large'],
            'price'        => '19.99',
            'sale_price'   => '',
            'sku'          => 'RED-L',
            'stock_status' => 'instock',
            'permalink'    => 'https://...',
            'image'        => ['id' => 500, 'src' => '...', 'width' => 80, 'height' => 80],
        ],
        [
            'id'           => 242,
            'title'        => 'Blue / Large',
            'attributes'   => ['Color' => 'Blue', 'Size' => 'Large'],
            'price'        => '21.99',
            'sale_price'   => '18.99',
            'sku'          => 'BLU-L',
            'stock_status' => 'outofstock',
            'permalink'    => 'https://...',
            'image'        => null,
        ],
    ],
    'parent_id' => 125,
]);
```

**Why not `metadata['variation_table']`:**
- `metadata` is intended for arbitrary extra data (dimensions, weight, custom fields)
- A `variation_table` is a **first-class domain concept** — it should have explicit structure
- The template layer (Milestone 5) needs to detect table mode reliably: `$item->has_variation_table()` is clearer than `$item->get_metadata()['variation_table'] ?? null`
- Type safety: `variation_table` has a defined shape; `metadata` does not

**Why not create separate variation CatalogItems:**
- The parent must remain visible as a catalog entry
- Creating both parent + variation items would require the template to distinguish between "standalone variation items" and "variation rows in a table"
- The template would need complex logic: "if this item has variation_table, render as table; otherwise render as card"
- A single self-contained `CatalogItem` with embedded variation data is simpler for the template

**Template consumption in table mode:**
```php
// The template receives the parent CatalogItem
if ($item->has_variation_table()) {
    // Render as product with embedded variation table
    $tableData = $item->get_variation_table();
    foreach ($tableData['variations'] as $variation) {
        // Render each variation row
    }
} else {
    // Render as standard product card
}
```

This is clean, explicit, and fully presentation-agnostic.

---

### 7. CATALOG PROCESSOR API

#### Recommended design:

```php
namespace Catalogist\CatalogItem;

final class CatalogProcessor {

    public function __construct(
        private CatalogItemFactory $factory,
        private VariationService $variationService,
        private ProductRepositoryInterface $productRepository
    ) {}

    /**
     * Process product query results into normalized CatalogItems.
     *
     * @param ProductQueryResult   $product_result   Product query result.
     * @param VariationQueryArgs|null $variation_args Variation expansion args.
     *                                                    null = no expansion (parent mode).
     *
     * @return array<CatalogItem> Flat list of normalized catalog items.
     */
    public function process(
        ProductQueryResult $product_result,
        ?VariationQueryArgs $variation_args = null
    ): array;

    /**
     * Get a single CatalogItem by ID.
     *
     * @param int $id Product or variation ID.
     *
     * @return CatalogItem|null
     */
    public function find(int $id): ?CatalogItem;
}
```

#### Why this design:

1. **Single entry point** — `process()` is the main method. It handles the full pipeline: query results → normalization → flat CatalogItem list.
2. **Optional variation expansion** — Passing `null` for `$variation_args` means no expansion. The processor handles this internally by checking if expansion args were provided.
3. **Depends on interfaces only** — `ProductRepositoryInterface` and `VariationService` are injected, not directly instantiated.
4. **`find()` for convenience** — Provides a way to get a single catalog item, used when a single product ID is known.

#### Why NOT `process(ProductQueryResult, VariationQueryResult)`:

- The processor should orchestrate the variation expansion itself, not require the caller to pre-build a `VariationQueryResult`.
- This keeps the API simple: the caller passes query args, the processor handles expansion.
- The `VariationService` abstraction is preserved — the processor uses it internally.

#### Internal orchestration flow:

```
process(ProductQueryResult, ?VariationQueryArgs):
  1. Normalize all products → array<CatalogItem>
  2. If $variation_args is provided and not parent mode:
     a. Call $this->variationService->expand(product_result, variation_args)
     b. For each variation in result:
        - Look up parent product context via $this->productRepository->find()
        - Normalize variation → CatalogItem
     c. If table mode: attach variation data to parent CatalogItem
     d. If expansion modes: add variation CatalogItems to result list
  3. Return combined array<CatalogItem>
```

---

### 8. DATA CONTEXT BOUNDARY

Three distinct layers (from CLAUDE.md and prompt.txt section 45):

```
Data Layer          → Query results (ProductQueryResult, VariationQueryResult)
Context Builder     → Normalized domain data (CatalogItem)          ← MILESTONE 4
Template Renderer   → Template context array ($context)            ← MILESTONE 5
```

**Milestone 4 output:** `CatalogItem` objects. These are domain value objects containing normalized data. They are NOT template context arrays.

**Milestone 5 responsibility:** Build the template context array:
```php
$context = [
    'catalog'   => $catalog,
    'items'     => $catalogItems,      // array<CatalogItem>
    'item'      => $catalogItem,       // single item (in loop)
    'product'   => $catalogItem->to_array(),
    'variation' => $catalogItem->is_variation() ? $catalogItem->to_array() : null,
];
```

**Milestone 4 must NOT:**
- Build `$context` arrays
- Call `esc_html()`, `esc_attr()`, or any escaping function
- Generate HTML, JSON, or any serialized output format
- Know about template file paths or rendering engines

**Milestone 4 MUST:**
- Ensure data integrity (correct types, no null pointer issues)
- Provide all data a template might need
- Keep data in a format that is easy to serialize to any output format

---

### 9. SERVICE ARCHITECTURE

#### Services to create:

| Service | Responsibility | Pattern |
|---------|---------------|---------|
| `CatalogItem` | Value object: single catalog entry (product or variation) | Same as `Catalog` (Milestone 1) — `final class`, typed props, constructor, getters, `to_array()` |
| `CatalogItemFactory` | Creates `CatalogItem` from product/variation arrays | Same as `CatalogFactory` (Milestone 1) — static `from_product()`, `from_variation()` |
| `CatalogProcessor` | Orchestrates: product query → variation expansion → normalization | Same as `VariationService` (Milestone 3) — injected deps, public API |
| `CatalogServiceProvider` | Registers services in the container | Same as `ProductServiceProvider` (Milestone 3) |

#### Container registration:

```php
// In CatalogServiceProvider::register():
$container->set( CatalogItemFactory::class, new CatalogItemFactory() );
$container->factory(
    CatalogProcessor::class,
    static function( Container $c ): CatalogProcessor {
        return new CatalogProcessor(
            $c->get( CatalogItemFactory::class ),
            $c->get( VariationService::class ),
            $c->get( ProductRepositoryInterface::class )
        );
    }
);
```

#### Pattern consistency with existing code:
- `CatalogItem` follows `Catalog` (Milestone 1): typed properties, no setters, `to_array()`
- `CatalogItemFactory` follows `CatalogFactory` (Milestone 1): static factory methods
- `CatalogProcessor` follows `VariationService` (Milestone 3): dependency injection, orchestration
- `CatalogServiceProvider` follows `ProductServiceProvider` (Milestone 3): interface-to-implementation binding

---

### 10. DEPENDENCY RULES

#### What CatalogProcessor MAY depend on:

| Dependency | Module | Reason |
|-----------|--------|--------|
| `ProductRepositoryInterface` | Product | Fetch parent product context for variations |
| `VariationService` | Variation | Expand variations based on mode |
| `ProductQueryResult` | Product | Input: product query results |
| `VariationQueryArgs` | Variation | Input: variation expansion parameters |
| `CatalogItem` | CatalogItem (self) | Output: normalized domain object |
| `CatalogItemFactory` | CatalogItem (self) | Dependency: creates CatalogItem instances |

#### What CatalogProcessor MUST NOT depend on:

| Anti-dependency | Reason |
|----------------|--------|
| `\WC_Product` directly in `CatalogItem` | Must not leak into the value object. Normalization happens in the factory/processor, not in the domain object. |
| `WooCommerceProductRepository` | Must depend on interface, not concrete class |
| `WooCommerceVariationRepository` | Must depend on interface, not concrete class |
| `ProductQueryArgs` | Product query parameters are not the processor's concern |
| `VariationQueryResult` | Processor orchestrates via `VariationService`, does not consume pre-built results directly |
| `Elementor\*` | Elementor is Milestone 6 |
| Template/rendering classes | Milestone 5 |
| Print/Output classes | Milestones 7–9 |
| WordPress output functions (`esc_html`, `esc_attr`, `echo`) | Escaping is a presentation concern |
| `wp_get_attachment_image_src` directly | Image resolution happens in `CatalogItemFactory`, not the processor |

#### Cross-module dependency graph:
```
CatalogProcessor
├── ProductRepositoryInterface   (interface, from Product module)
├── VariationService             (service, from Variation module)
├── CatalogItemFactory           (own module)
└── CatalogItem                  (own module)

CatalogItemFactory
├── ProductRepositoryInterface   (for parent product lookup in variations)
└── WordPress functions          (wp_get_attachment_image_src for image resolution)
```

**Note:** `CatalogItemFactory` will call `wp_get_attachment_image_src()` for image resolution. This is a data retrieval concern, not a presentation concern. The factory is the appropriate place for this.

---

### 11. PROPOSED FILE STRUCTURE

#### New files:
```
src/CatalogItem/CatalogItem.php              — Value object
src/CatalogItem/CatalogItemFactory.php       — Factory
src/CatalogItem/CatalogProcessor.php         — Processor
src/CatalogItem/CatalogServiceProvider.php   — Service provider

tests/Unit/CatalogItem/CatalogItemTest.php          — Value object tests
tests/Unit/CatalogItem/CatalogItemFactoryTest.php   — Factory tests
tests/Integration/CatalogItem/CatalogProcessorTest.php — End-to-end tests
```

#### Modified files:
```
src/Core/Plugin.php — Register CatalogServiceProvider
```

#### Files intentionally NOT modified:
```
src/Product/ProductQueryResult.php    — Read-only contract
src/Product/ProductRepositoryInterface.php — Read-only contract
src/Product/WooCommerceProductRepository.php — Read-only (normalization happens in processor)
src/Variation/VariationQueryResult.php — Read-only contract
src/Variation/VariationRepositoryInterface.php — Read-only contract
src/Variation/WooCommerceVariationRepository.php — Read-only (already returns normalized arrays)
src/Variation/VariationService.php — Read-only; processor calls it, not the other way
```

---

### 12. TEST STRATEGY

#### Unit tests (no WordPress/WooCommerce required):

**CatalogItemTest:**
- Construction from product data (all required fields present)
- Construction from variation data (variation-specific fields)
- `is_product()` / `is_variation()` type checks
- `to_array()` serialization round-trip
- Optional fields default correctly (null image, empty gallery, empty attributes)
- Parent product ID handling
- Variation table data structure (table mode)
- `has_variation_table()` detection

**CatalogItemFactoryTest:**
- `from_product()` with complete data
- `from_product()` with missing optional fields (no image, no gallery, no attributes)
- `from_variation()` with complete data including parent context
- `from_variation()` with missing optional fields
- Null/empty data handling (graceful defaults)
- Type safety (rejects non-array input)

#### Integration tests (require WooCommerce mock):

**CatalogProcessorTest:**
- Simple products only (no variations) — mode=parent
- Variable products in `parent` mode — parent only, no variations
- Variable products in `all` mode — all variations as separate CatalogItems
- Variable products in `selected` mode — filtered to selected variation
- Variable products in `multiple` mode — filtered to multiple selections
- Variable products in `table` mode — parent with variation_table structure
- Mixed catalog (simple + variable products)
- Empty product list — returns empty array
- Non-variable product in expansion mode — returns product only
- Graceful degradation when WooCommerce is inactive
- Parent context caching (no duplicate `find()` calls for same parent)

#### Architecture boundary tests:
- CatalogProcessor does not reference `\WC_Product` in its public API (only in normalization helper)
- CatalogProcessor does not call WordPress output functions
- CatalogProcessor does not depend on Elementor
- CatalogProcessor does not depend on Template, Layout, Output, or Print modules
- CatalogItem does not reference `\WC_Product` at all
- `variation_table` field is a dedicated property, not buried in `metadata`

---

### 13. SECURITY

#### Input trust boundaries:
- All input comes from `ProductQueryResult` and `VariationQueryResult`, which are already produced by the repository layer
- Product IDs are validated as integers (type-hinted in interface)
- No user-supplied data enters the processor directly

#### Escaping responsibilities (clearly separated):
- **Processor responsibility:** Data integrity — correct types, no null pointer issues, valid structure
- **Template layer responsibility (Milestone 5):** HTML escaping (`esc_html()`, `esc_attr()`, `esc_url()`, `wp_kses_post()`)
- **NO presentation escaping in the processor** — This would couple the domain layer to output format and break the clean separation.

#### Data validation (defense-in-depth):
- `CatalogItemFactory` validates types with `is_array()`, `is_int()`, `is_string()` checks
- Integer fields use `absint()` to ensure type safety
- String fields use `(string)` cast — `sanitize_text_field()` is NOT used (see Section 14)
- Array fields are validated with `is_array()` and filtered to expected types

#### XSS risks:
- Since the processor does not generate HTML, there is no XSS risk at this layer
- The template layer (Milestone 5) is responsible for escaping all output
- Storing raw HTML in `description` and `short_description` is intentional — the template will escape as needed

#### Unsafe data concerns:
- **No raw `\WC_Product` objects in CatalogItem** — The factory converts everything to plain data types
- **No WordPress post objects** — Only scalar data and arrays
- **No serialized WooCommerce objects** — All data is plain PHP types
- **`variation_table` is a structured array** — Not arbitrary metadata; it has a defined shape

---

### 14. SANITIZATION VS NORMALIZATION

#### Four distinct responsibilities:

| Responsibility | Definition | Owned by | Example |
|---------------|-----------|----------|---------|
| **Validation** | Ensuring data conforms to expected types and ranges | `CatalogItemFactory` | `is_int($id)`, `is_array($attributes)` |
| **Normalization** | Converting raw data into the canonical domain format | `CatalogItemFactory` | Converting `\WC_Product` → array, resolving image URLs |
| **Sanitization** | Removing dangerous or unwanted content | N/A (not needed here) | N/A — data comes from WooCommerce APIs, not user input |
| **Escaping** | Formatting data for a specific output context | Template layer (Milestone 5) | `esc_html()`, `esc_attr()`, `wp_kses_post()` |

#### Why NO `sanitize_text_field()` in the factory:

1. **`sanitize_text_field()` strips HTML** — Product descriptions and short descriptions may contain legitimate HTML (`<strong>`, `<em>`, `<a>`, etc.). Stripping this would destroy data the template needs to render.
2. **The data source is trusted** — Data comes from `wc_get_products()` and `wc_get_product()`, which are WordPress/WooCommerce core APIs. It is not user-submitted form input.
3. **Escaping is a presentation concern** — The template layer (Milestone 5) should decide how to escape output based on context (`esc_html()` for text, `wp_kses_post()` for HTML content).
4. **Defense-in-depth is already handled** — The repository layer (`WooCommerceProductRepository`, `WooCommerceVariationRepository`) already sanitizes IDs with `absint()` and slugs with `sanitize_title()`.

#### What the factory DOES validate:
- Type checks: `is_int()`, `is_string()`, `is_array()`, `is_null()`
- Range checks: non-negative IDs, non-negative stock quantities
- Structure checks: arrays have expected keys, strings are not excessively long

#### Summary:
- **Validation** → Factory (type/structure checks)
- **Normalization** → Factory (data transformation)
- **Sanitization** → Not needed (trusted data source)
- **Escaping** → Template layer (output-specific)

---

### 15. PERFORMANCE

#### Considerations:
- **Large catalogs (500–1000 products):** The processor iterates once over products. For variable products, it calls `VariationService::expand()` once per batch, then normalizes each variation.
- **Parent context lookups:** When normalizing variations, the processor calls `ProductRepositoryInterface::find($parent_id)` for each unique parent. With caching of lookups (in-memory array), this is at most one call per unique variable product in the result set.
- **Image resolution:** `wp_get_attachment_image_src()` is called once per product/variation that has an image. Results are cached in-memory during the normalization pass to avoid duplicate calls for the same attachment ID.
- **Object creation:** Each product/variation becomes one `CatalogItem`. This is necessary — there is no way to avoid it while maintaining the value object pattern.

#### No premature caching:
- Caching of normalized `CatalogItem` lists would require cache keys based on query parameters and product IDs
- Invalidation would need to track which catalog items depend on which products
- This is a Milestone 10 concern. The processor should be pure and stateless.

---

### 16. BACKWARD COMPATIBILITY

#### What must NOT break:
- `ProductQueryResult` — unchanged
- `ProductRepositoryInterface` — unchanged
- `VariationQueryResult` — unchanged
- `VariationRepositoryInterface` — unchanged
- `VariationService` — unchanged
- `VariationMode` — unchanged
- `ProductQueryArgs` — unchanged
- `VariationQueryArgs` — unchanged
- Any existing tests — no modifications required

#### Safe integration:
- The `CatalogServiceProvider` registers new services only
- `Plugin.php` adds one more provider to the list
- No existing interfaces gain new methods
- No existing classes change their behavior
- The processor depends on interfaces, not concrete implementations

---

### 17. RISKS

| Risk | Severity | Mitigation |
|------|----------|------------|
| `wc_get_products()` returns `\WC_Product` objects — processor must handle them | Medium | Factory normalizes via getter methods. This is the correct layer for this conversion. |
| `wp_get_attachment_image_src()` returns false in test environment | Low | Mock in tests. Return `null` image when resolution fails. |
| Variation attributes format varies across WooCommerce versions | Medium | Use `get_variation_attributes()` which is the stable API. Handle both array and string return types. |
| `CatalogProcessor` may become a "god class" if scope creeps | Medium | Keep it focused: only normalization. No rendering, no template logic, no business rules. |
| Parent context lookup adds N+1 queries for variable products | Medium | Cache lookups in-memory during a single `process()` call. One `find()` per unique parent ID. |
| `variation_table` field adds complexity to `CatalogItem` | Low | It is only populated in table mode. Other modes have `null`. Clean type: `array|null`. |

---

### 18. RECOMMENDED IMPLEMENTATION ORDER

1. **Create `CatalogItem` value object** — fields, constructor, getters, `to_array()`, type helpers (`is_product()`, `is_variation()`, `has_variation_table()`)
2. **Create `CatalogItemFactory`** — `from_product()` and `from_variation()` static factories with full normalization logic
3. **Create `CatalogProcessor`** — `process()` and `find()` methods with variation expansion orchestration
4. **Create `CatalogServiceProvider`** — register services in container
5. **Register `CatalogServiceProvider` in `Plugin.php`**
6. **Create unit tests** — `CatalogItemTest`, `CatalogItemFactoryTest`
7. **Create integration tests** — `CatalogProcessorTest` covering all 5 modes
8. **Run validation** — PHP syntax, manual test runner, architecture boundary checks

---

### 19. ACCEPTANCE CRITERIA

Before Milestone 4 is considered complete:

- [ ] `CatalogItem` value object exists with all required fields and type safety
- [ ] `CatalogItemFactory::from_product()` correctly normalizes product data (including `\WC_Product` objects)
- [ ] `CatalogItemFactory::from_variation()` correctly normalizes variation data with parent context
- [ ] `CatalogProcessor::process()` handles all five variation modes correctly
- [ ] Parent mode: only products, no variations expanded
- [ ] All mode: every variation becomes a separate `CatalogItem` (`type='variation'`)
- [ ] Selected mode: only selected variation per parent
- [ ] Multiple mode: all selected variations per parent
- [ ] Table mode: parent `CatalogItem` with `variation_table` field populated, no separate variation items
- [ ] Mixed catalogs (simple + variable products) handled correctly
- [ ] Empty results handled gracefully (no errors)
- [ ] Graceful degradation when WooCommerce is inactive
- [ ] `CatalogItem` does NOT reference `\WC_Product` (normalization is in factory/processor only)
- [ ] No rendering/HTML/escaping in the processor or factory
- [ ] No Elementor dependencies
- [ ] `variation_table` is a dedicated field, not buried in generic `metadata`
- [ ] Unit tests cover all five variation modes
- [ ] Integration tests verify architecture boundaries
- [ ] PHP syntax validation passes on all new files
- [ ] Manual test suite passes
- [ ] Existing Milestone 1–3 tests are not broken

---

### 20. FILE CHANGE SUMMARY

**New files (7):**
```
src/CatalogItem/CatalogItem.php              — Value object (product or variation)
src/CatalogItem/CatalogItemFactory.php       — Factory: normalizes product/variation data
src/CatalogItem/CatalogProcessor.php         — Orchestrator: processes query results
src/CatalogItem/CatalogServiceProvider.php   — Registers services in container
tests/Unit/CatalogItem/CatalogItemTest.php   — Value object tests
tests/Unit/CatalogItem/CatalogItemFactoryTest.php — Factory tests
tests/Integration/CatalogItem/CatalogProcessorTest.php — End-to-end tests
```

**Modified files (1):**
```
src/Core/Plugin.php — Register CatalogServiceProvider
```

**Files intentionally NOT modified:**
```
src/Product/ProductQueryResult.php
src/Product/ProductRepositoryInterface.php
src/Product/WooCommerceProductRepository.php
src/Variation/VariationQueryResult.php
src/Variation/VariationRepositoryInterface.php
src/Variation/WooCommerceVariationRepository.php
src/Variation/VariationService.php
src/Core/Container.php
src/Core/ServiceProviderInterface.php
Any other existing files
```

---

### 21. FINAL VERDICT

## APPROVED FOR IMPLEMENTATION

The revised architecture resolves all concerns from the initial report:

1. **WC_Product boundary:** The processor handles normalization internally. `CatalogItem` never references `\WC_Product`. No interface changes required.
2. **Variation orchestration:** The processor internally uses `VariationService` for expansion, keeping the separation between querying, expansion, and normalization.
3. **variation_table representation:** Dedicated `variation_table` field on `CatalogItem` — structured, typed, presentation-agnostic.
4. **Sanitization vs normalization:** Clear boundary defined. No `sanitize_text_field()` in the factory. Escaping is the template layer's responsibility.
5. **Codebase inspection:** All contracts verified against actual code. The report reflects the real state of `ProductQueryResult`, `VariationQueryResult`, and their interfaces.
