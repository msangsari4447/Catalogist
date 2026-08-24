# AGENTS.md

This file provides guidance to Codex (Codex.ai/code) when working with code in this repository.

## Project Overview

Catalogist is a planned professional WordPress plugin for building printable WooCommerce product catalogs.

Canonical identifiers:

- Project/plugin name: `Catalogist`
- Text domain / slug: `catalogist`
- PHP namespace: `Catalogist\`
- Hook/function prefix: `catalogist_`
- Catalog custom post type: `ctlg_catalog`
- Template custom post type: `ctlg_template`

The master project specification is in `prompt.txt`. Treat it as the authoritative product and architecture brief.

## Current Repository State

Milestone 1 (Core Architecture) and Milestone 2 (Product Query Engine) have been implemented. The plugin scaffold is complete with:
- PSR-4 autoloading via Composer
- Service container and provider pattern
- Custom post type for catalogs (`ctlg_catalog`)
- Admin menu and settings page
- Security framework (capabilities, nonces, sanitization)
- Product Query Engine with `ProductRepositoryInterface`, `WooCommerceProductRepository`, `ProductQueryArgs`, `ProductQueryResult`
- Variation Engine with `VariationRepositoryInterface`, `WooCommerceVariationRepository`, `VariationService`, `VariationMode`
- Basic test foundation

## Development Commands

```bash
# Install dependencies
composer install

# Regenerate autoloader after adding new classes
composer dump-autoload

# Run tests
composer test
vendor/bin/phpunit

# Run specific test
vendor/bin/phpunit --filter PluginTest

# Run code sniffer
composer lint
vendor/bin/phpcs

# Fix code style issues
composer format
vendor/bin/phpcbf
```

## Required Architecture Direction

The core data flow must remain:

```text
WooCommerce
     ↓
Product Query Engine
     ↓
Filter Engine
     ↓
Catalog Item Processor
     ↓
Template Engine
     ↓
Layout / Print Engine
     ↓
Output Engine
```

Elementor is an integration layer, not the core architecture. The plugin core must load without Elementor active.

Recommended top-level structure from the master prompt:

```text
catalogist.php
composer.json
uninstall.php
src/
  Core/
  Admin/
  Catalog/
  Product/
  Template/
  Layout/
  Output/
  Print/
  REST/
  Elementor/
  Security/
  Support/
assets/
  css/
  js/
templates/
languages/
tests/
```

Review and refine this structure before implementation. Do not collapse the plugin into one large file or one large class.

## Architectural Boundaries

- Core plugin code must not depend on Elementor classes at load time.
- Elementor-specific code belongs under an Elementor integration namespace/module and loads conditionally only when Elementor is active.
- WooCommerce-dependent features must be disabled gracefully when WooCommerce is inactive, with an admin notice and no fatal errors.
- Rendering must be separated from data retrieval:

```text
Data Layer → Context Builder → Template Renderer → HTML → Print/PDF Output
```

- Template/view files must not contain direct database queries or business logic.
- Product and variation data must be normalized into Catalog Items before templates render them.
- Print layout must be controllable independently of web layout.
- PDF export is future architecture only for MVP; do not add a hard PDF library dependency in the first version unless explicitly approved.

## Storage Strategy

Prefer WordPress-native storage unless a clear need for custom tables is proven:

- Catalogs: `ctlg_catalog` CPT plus structured post meta
- Templates: evaluate `ctlg_template` CPT, Elementor templates, or a hybrid before implementation
- Settings: WordPress Settings API / options
- Caching: transients or object cache where appropriate

Catalog data should include product query, filters, selected products, selected variations, ordering, template, layout settings, print settings, output settings, status, and timestamps.

Selections must be stored as structured data, not raw HTML or plain text.

## Product and Variation Model

Treat both products and variations as manageable Catalog Items. Variable products must support these modes:

1. Parent product only
2. All variations as independent items
3. Manually selected variations
4. Multiple selected variations
5. Parent product with a variation table

Each variation should expose its own normalized template data: name, parent product, SKU, price, image, stock, attributes, dimensions, weight, and custom meta.

The Template Engine should consume a standard context similar to:

```php
$context = [
    'catalog'   => $catalog,
    'item'      => $catalogItem,
    'product'   => $product,
    'variation' => $variation,
];
```

Elementor integration must use the same context.

## Elementor Integration

Use official Elementor APIs only. Do not modify Elementor core.

Planned Elementor pieces:

- Dynamic tags for product and variation data
- Product Card widget that receives data from the Catalog Item context
- WooCommerce Catalog widget for selecting and displaying a saved catalog
- Controls for catalog selection, preview, and display mode

Dynamic data should include product name, images/gallery, SKU, price/sale price, descriptions, categories/tags, attributes, stock status, product URL, QR code, product ID, variation name/SKU/price/attributes.

## Print and Output Requirements

The first version prioritizes HTML + Print. Output architecture should allow future PDF modules such as Dompdf, mPDF, Chromium, or Browsershot without rewriting the core.

Print architecture must support:

- A4 page size
- Portrait/landscape orientation
- Configurable margins
- 1–4 columns
- Preview close to real print output
- Standards-based print CSS using `@media print`
- `break-inside: avoid` and `page-break-inside: avoid` for product cards and other sensitive blocks

Header, footer, cover, section, table, and product card page-break behavior must be explicit.

## Security and Compatibility Requirements

Build security into each milestone:

- Validate and sanitize all input
- Escape all output
- Use nonces for admin actions
- Use capability checks for privileged operations
- Never trust admin-provided data blindly
- Avoid unnecessary inline SQL; use WordPress/WooCommerce APIs where possible

Compatibility expectations:

- WordPress standards and APIs
- WooCommerce official APIs
- Elementor official APIs when Elementor is active
- Translatable strings using text domain `catalogist`
- RTL-compatible admin UI and print templates, while preserving LTR support
- Avoid deprecated APIs

## Performance Requirements

Design for large catalogs of roughly 500–1000 products from the beginning.

Avoid repeated per-product queries, heavy unnecessary meta queries, repeated API calls, and re-rendering identical data. Add caching where appropriate, but keep invalidation clear and maintainable.

## Milestone Workflow

Do not build the whole project at once. Work in the milestones from `prompt.txt`:

1. Core Architecture
2. Product Query Engine
3. Variation Engine
4. Catalog Processor
5. Template Engine
6. Elementor Integration
7. Print Engine
8. Preview
9. Output
10. Security / Performance / Testing

Before each milestone, provide:

```text
1. Goal of this milestone
2. Files affected
3. Architecture decision
4. Implementation plan
5. Risks
```

After each milestone, report:

```text
1. What changed
2. Files changed
3. Tests performed
4. Known issues
5. Next milestone
```

Only implement files related to the current milestone. Avoid unrelated changes.

## Definition of Done

A milestone is not complete until the feature is implemented, structure is clean, security and error handling are considered, regression risk is checked, testing is performed, and usage is documented where relevant.

## Important Project Rules

- Correct architecture first, then staged implementation, then testing and optimization.
- Keep Elementor integration separate from the core.
- Treat products and variations as catalog items.
- Templates must be data-driven.
- Do not put business logic in template/view files.
- Do not add dependencies without confirming they are needed.
- If requirements are incomplete, make the best engineering decision from the approved architecture and continue unless the choice would seriously affect the architecture.
- If choosing between major approaches, state pros, cons, and the recommended approach before implementing.
