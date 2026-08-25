# Pre-Milestone Report: Milestone 5 — Template Engine

**Date:** 2026-08-25  
**Status:** For Architectural Review  
**Author:** Agnes (Sapiens AI)

---

## 1. CURRENT ARCHITECTURE VERIFICATION

### Verified Codebase State (as of 2026-08-25)

#### Existing Modules (Milestones 1–4 Complete)

| Module | Namespace | Status | Key Files |
|--------|-----------|--------|-----------|
| Core | `Catalogist\Core` | ✓ Complete | Plugin.php, Container.php, ServiceProviderInterface.php |
| Catalog | `Catalogist\Catalog` | ✓ Complete | Catalog.php, CatalogRepository.php, CatalogPostType.php |
| Product | `Catalogist\Product` | ✓ Complete | ProductQueryResult.php, ProductRepositoryInterface.php, WooCommerceProductRepository.php |
| Variation | `Catalogist\Variation` | ✓ Complete | VariationService.php, VariationMode.php, VariationQueryArgs.php |
| CatalogItem | `Catalogist\CatalogItem` | ✓ Complete | CatalogItem.php, CatalogItemFactory.php, CatalogProcessor.php |
| Security | `Catalogist\Security` | ✓ Complete | SecurityServiceProvider.php |

#### Critical Architecture Contracts

**CatalogItem (src/CatalogItem/CatalogItem.php)**
- Immutable value object with 21 typed fields
- Type helpers: `is_product()`, `is_variation()`, `has_variation_table()`, `is_variable_product()`
- `to_array()` returns complete serializable representation
- **No direct WooCommerce dependencies** — normalization complete at factory/processor layer

**CatalogProcessor (src/CatalogItem/CatalogProcessor.php)**
- Orchestrates: ProductQueryResult → Variation expansion → CatalogItem normalization
- Handles all 5 variation modes (parent, all, selected, multiple, table)
- Parent context caching prevents N+1 queries
- Image resolution via `wp_get_attachment_image_src()` in factory
- **No HTML generation, no escaping, no template logic**

**Catalog Model (src/Catalog/Catalog.php)**
- Contains `template_id` field (line 71: `private int $template_id = 0`)
- Setters/getters for all fields
- `to_array()` for serialization
- Repository pattern for persistence

**ProductQueryResult (src/Product/ProductQueryResult.php)**
- Contains `\WC_Product` objects (from `wc_get_products()` with default `return='objects'`)
- `get_products()` returns mixed array of objects/arrays/ints
- `get_ids()` extracts integer IDs only

**VariationQueryResult (src/Variation/VariationQueryResult.php)**
- Contains already-normalized arrays from `WooCommerceVariationRepository::extract_variation_data()`
- Keys: id, parent_id, type, status, name, sku, price, regular_price, sale_price, stock_status, stock_quantity, purchasable, visible, attributes, image, dimensions

#### Service Container (src/Core/Container.php)
- Simple DI container with `set()`, `get()`, `factory()`, `has()`, `remove()`
- Plugin singleton via `Plugin::instance()`
- Providers registered in `Plugin::register_providers()`

#### Provider Registration (src/Core/Plugin.php:141-149)
```php
$providers = array(
    SecurityServiceProvider::class,
    AdminServiceProvider::class,
    CatalogServiceProvider::class,
    CatalogItemServiceProvider::class,
    ProductServiceProvider::class,
    VariationServiceProvider::class,
);
```

**No Template provider exists yet** — Milestone 5 must add this.

---

## 2. EXACT SCOPE AND GOAL OF MILESTONE 5

### Primary Goal

Build a **data-driven Template Engine** that renders `CatalogItem` objects into HTML output using pluggable template files. This is the presentation layer that sits between the normalized data (Milestone 4) and the output/print engines (Milestones 7–9).

### Scope Boundaries

**IN SCOPE:**
- Template loading and caching
- Template context construction (CatalogItem → context array)
- Template file parsing and variable injection
- Shortcode registration for catalog display
- Basic template structure (header, footer, loop, card)
- Variation table rendering within template context
- Security: escaping at template layer only
- Error handling for missing templates

**OUT OF SCOPE (Future Milestones):**
- Elementor widget integration (Milestone 6)
- Print CSS and A4 layout (Milestone 7)
- Preview engine (Milestone 8)
- PDF export architecture (Milestone 9)
- Template admin UI (Milestone 10)
- QR code generation (Milestone 10)

### Data Flow Position

```
WooCommerce
     ↓
Product Repository (Milestone 2)
     ↓
Variation Service (Milestone 3)
     ↓
Catalog Processor (Milestone 4)
     ↓
CatalogItem objects (Milestone 4) ← INPUT TO MILESTONE 5
     ↓
Template Context Builder (Milestone 5) ← THIS MILESTONE
     ↓
Template Renderer (Milestone 5) ← THIS MILESTONE
     ↓
HTML Output ← OUTPUT OF MILESTONE 5
     ↓
Print Engine (Milestone 7)
     ↓
Output Engine (Milestone 9)
```

### What This Milestone Does NOT Do

- No WooCommerce API calls
- No product querying
- No variation expansion logic
- No printing/ PDF logic
- No Elementor dependencies
- No admin UI for template creation
- No template file editing UI
- No dynamic data fetching during render

---

## 3. TEMPLATE ENGINE ARCHITECTURE

### High-Level Design

The Template Engine follows a **three-component architecture**:

```
TemplateEngine (Orchestrator)
├── TemplateLoader (File discovery and caching)
├── TemplateContextBuilder (CatalogItem → context array)
└── TemplateRenderer (File parsing and output)
```

### Architectural Pattern

**Strategy Pattern for Rendering:**
- `TemplateRendererInterface` defines the contract
- `FileTemplateRenderer` implements file-based rendering
- Future: `ElementorTemplateRenderer` (Milestone 6), `CustomTemplateRenderer`

**Chain of Responsibility for Fallbacks:**
1. Check for catalog-specific template (`template_id` from Catalog)
2. Fall back to default template
3. Fall back to built-in fallback template
4. Return empty string on complete failure

### Core Principles

1. **Data-driven**: Templates consume `TemplateContext` arrays, not `CatalogItem` objects directly
2. **Separation of concerns**: Template files contain only presentation logic, no business logic
3. **Security by default**: All output escaped at rendering time
4. **Extensibility**: Easy to add new template loaders (file, database, Elementor)
5. **Performance**: Template caching, minimal context building overhead

---

## 4. CATALOGTEMPLATE DESIGN

### Concept

A `CatalogTemplate` represents a template entity that can be:
- Stored as a WordPress post (CPT `ctlg_template`)
- Loaded from the filesystem (`templates/` directory)
- Referenced by ID from a Catalog entity

### Design Options (for decision)

**Option A: CPT-Based Templates**
```php
class CatalogTemplate {
    private int $id;
    private string $name;
    private string $type; // 'file' | 'cpt'
    private string $path; // filesystem path or post content
    private array $settings;
}
```

**Option B: Hybrid Approach (Recommended)**
- Default templates stored as files in `templates/default/`
- Custom templates stored as CPT `ctlg_template` with post content containing template variables
- Templates referenced by ID or path

**Recommended: Hybrid Approach**

Rationale:
- File-based templates are simpler for default layouts
- CPT-based templates allow admin editing and versioning
- Both approaches can coexist with a unified interface

### Template File Structure

```
templates/
├── default/
│   ├── catalog.php           # Main catalog template
│   ├── header.php            # Header section
│   ├── footer.php            # Footer section
│   ├── product-loop.php      # Product loop container
│   ├── product-card.php      # Individual product card
│   └── variation-table.php   # Variation table for table mode
├── [custom-template-slug]/
│   └── catalog.php           # Custom template overrides
└── fallback/
    └── catalog.php           # Minimal fallback if all else fails
```

---

## 5. TEMPLATECONTEXT CONTRACT

### Purpose

The `TemplateContext` is the standardized data structure passed to template files. It decouples templates from the internal data model and provides a consistent API for template designers.

### Context Structure

```php
$context = [
    // Core data
    'catalog'     => $catalog,           // Catalog entity (src/Catalog/Catalog.php)
    'items'       => $catalogItems,       // array<CatalogItem>
    
    // Loop context (for use inside product loop)
    'item'        => $catalogItem,        // Current CatalogItem in loop
    'item_index'  => $index,              // Current loop index (0-based)
    'item_count'  => $count,              // Total items in loop
    'is_first'    => $isFirst,            // Boolean: is this the first item?
    'is_last'     => $isLast,             // Boolean: is this the last item?
    'is_even'     => $isEven,             // Boolean: is this an even index?
    'is_odd'      => $isOdd,              // Boolean: is this an odd index?
    
    // Layout settings
    'layout'      => $layoutSettings,     // From catalog->get_layout_settings()
    'columns'     => $columns,            // Number of columns (1-4)
    'page_size'   => $pageSize,           // A4, Letter, etc.
    'orientation' => $orientation,        // portrait, landscape
    
    // Print settings
    'print'       => $printSettings,      // From catalog->get_print_settings()
    'margins'     => $margins,            // Array of margins
    'show_header' => $showHeader,         // Boolean
    'show_footer' => $showFooter,         // Boolean
    
    // Template metadata
    'template_id' => $templateId,         // Template ID or slug
    'template_name' => $templateName,     // Human-readable name
    
    // Security helpers (pre-escaped data)
    'escaped'     => [
        'title'   => esc_html( $item->get_title() ),
        'sku'     => esc_html( $item->get_sku() ),
        'price'   => esc_html( $item->get_price() ),
        'permalink' => esc_url( $item->get_permalink() ),
    ],
];
```

### Context Building Rules

1. **Never pass raw `CatalogItem` without escaping** — Provide both raw and escaped versions
2. **Separate loop data from catalog data** — Keep `catalog` and `item` distinct
3. **Provide helper functions** — Template designers can use `catalogist_get_title($item)` etc.
4. **Layout settings must be normalized** — Convert raw settings to usable values with defaults

### Context Builder Class

```php
namespace Catalogist\Template;

final class TemplateContextBuilder {
    public function build(
        Catalog $catalog,
        array $catalogItems,
        ?array $layoutSettings = null,
        ?array $printSettings = null
    ): array;
    
    public function buildLoopContext(
        Catalog $catalog,
        CatalogItem $item,
        int $index,
        int $count
    ): array;
}
```

---

## 6. TEMPLATELOADER DESIGN

### Purpose

The `TemplateLoader` is responsible for:
- Finding template files based on ID, slug, or fallback chain
- Caching loaded templates to avoid repeated filesystem operations
- Providing a unified interface for template retrieval

### Interface

```php
namespace Catalogist\Template;

interface TemplateLoaderInterface {
    /**
     * Load a template by ID or slug.
     *
     * @param int|string $templateId Template ID (post ID or slug).
     * @param string $defaultFallback Default template path if not found.
     *
     * @return Template|null
     */
    public function load( $templateId, string $defaultFallback = '' ): ?Template;
    
    /**
     * Get the full filesystem path for a template.
     *
     * @param string $templateSlug Template slug.
     *
     * @return string|null Filesystem path or null if not found.
     */
    public function getPath( string $templateSlug ): ?string;
    
    /**
     * Clear the template cache.
     */
    public function clearCache(): void;
}
```

### Implementation: FileTemplateLoader

```php
namespace Catalogist\Template\Loader;

class FileTemplateLoader implements TemplateLoaderInterface {
    private string $baseDirectory;
    private array $cache = array();
    
    public function __construct( string $pluginDirectory ) {
        $this->baseDirectory = trailingslashit( $pluginDirectory ) . 'templates';
    }
    
    public function load( $templateId, string $defaultFallback = '' ): ?Template {
        // 1. Check cache first
        // 2. Try CPT if numeric ID
        // 3. Try filesystem by slug
        // 4. Fall back to default
    }
    
    private function resolvePath( string $slug ): ?string {
        $paths = array(
            // Custom template in active theme
            get_template_directory() . '/catalogist/' . $slug . '/catalog.php',
            // Plugin default templates
            $this->baseDirectory . '/' . $slug . '/catalog.php',
            // Default template
            $this->baseDirectory . '/default/catalog.php',
        );
        
        foreach ( $paths as $path ) {
            if ( file_exists( $path ) ) {
                return $path;
            }
        }
        
        return null;
    }
}
```

### Template Fallback Chain

1. **Catalog-specific template**: If `catalog->template_id` is set, load that template
2. **Custom theme override**: Check `wp-content/themes/{theme}/catalogist/{slug}/`
3. **Plugin default**: Check `wp-content/plugins/catalogist/templates/{slug}/`
4. **Built-in fallback**: Use minimal hardcoded HTML structure
5. **Error state**: Return empty string with error log (admin only)

---

## 7. RENDERER DESIGN

### Purpose

The `TemplateRenderer` is responsible for:
- Including template files with the context
- Capturing output (not echoing)
- Applying filters and hooks
- Handling template errors gracefully

### Interface

```php
namespace Catalogist\Template;

interface TemplateRendererInterface {
    /**
     * Render a template with the given context.
     *
     * @param string $templateSlug Template slug.
     * @param array $context Template context array.
     *
     * @return string Rendered HTML.
     */
    public function render( string $templateSlug, array $context ): string;
    
    /**
     * Render a specific section of a template.
     *
     * @param string $section Section name (header, footer, loop, card).
     * @param array $context Template context array.
     *
     * @return string Rendered HTML.
     */
    public function renderSection( string $section, array $context ): string;
}
```

### Implementation: FileTemplateRenderer

```php
namespace Catalogist\Template\Renderer;

class FileTemplateRenderer implements TemplateRendererInterface {
    private TemplateLoaderInterface $loader;
    
    public function __construct( TemplateLoaderInterface $loader ) {
        $this->loader = $loader;
    }
    
    public function render( string $templateSlug, array $context ): string {
        $path = $this->loader->getPath( $templateSlug );
        
        if ( ! $path ) {
            return $this->renderFallback( $context );
        }
        
        // Extract context variables for template scope
        extract( $context, EXTR_SKIP );
        
        // Start output buffering
        ob_start();
        
        try {
            // Include template file
            require $path;
        } catch ( \Throwable $e ) {
            // Log error (admin only)
            error_log( 'Catalogist template render error: ' . $e->getMessage() );
            return $this->renderFallback( $context );
        }
        
        // Get buffered content
        $html = ob_get_clean();
        
        // Apply filters
        $html = apply_filters( 'catalogist_template_output', $html, $templateSlug, $context );
        
        return $html;
    }
    
    private function renderFallback( array $context ): string {
        // Minimal fallback HTML
        return '<div class="catalogist-fallback">' . 
               '<h2>Template Not Found</h2>' . 
               '<p>Please configure a template for this catalog.</p>' . 
               '</div>';
    }
}
```

### Output Buffering Strategy

- Use `ob_start()` / `ob_get_clean()` for all template rendering
- Never `echo` directly from template files
- Capture errors and return fallback HTML
- Apply global filters after rendering

---

## 8. TEMPLATE FILE STRUCTURE

### Standard Template Layout

Each template directory contains these files:

```
templates/{template-slug}/
├── catalog.php          # Main template (includes header, loop, footer)
├── header.php           # Header section
├── footer.php           # Footer section
├── product-loop.php     # Product loop container
├── product-card.php     # Individual product card
└── variation-table.php  # Variation table (for table mode)
```

### Template File Contracts

**catalog.php:**
```php
<?php
/**
 * Main catalog template.
 *
 * Context variables available:
 * - $catalog (Catalog)
 * - $items (array<CatalogItem>)
 * - $layout (array)
 * - $print (array)
 * - $template_id (int|string)
 *
 * Includes: header.php, product-loop.php, footer.php
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="catalogist-catalog catalogist-template-<?php echo esc_attr( $template_id ); ?>"
     data-columns="<?php echo esc_attr( $layout['columns'] ?? 2 ); ?>"
     data-template="<?php echo esc_attr( $template_id ); ?>">
    
    <?php if ( ! empty( $layout['show_header'] ) ): ?>
        <?php echo $this->renderSection( 'header', compact( 'catalog', 'layout', 'print' ) ); ?>
    <?php endif; ?>
    
    <?php if ( ! empty( $items ) ): ?>
        <?php echo $this->renderSection( 'loop', compact( 'items', 'layout' ) ); ?>
    <?php else: ?>
        <p class="catalogist-empty"><?php esc_html_e( 'No products found in this catalog.', 'catalogist' ); ?></p>
    <?php endif; ?>
    
    <?php if ( ! empty( $layout['show_footer'] ) ): ?>
        <?php echo $this->renderSection( 'footer', compact( 'catalog', 'layout', 'print' ) ); ?>
    <?php endif; ?>
</div>
```

**header.php:**
```php
<?php
/**
 * Catalog header template.
 *
 * Context variables:
 * - $catalog (Catalog)
 * - $layout (array)
 * - $print (array)
 */

defined( 'ABSPATH' ) || exit;
?>
<header class="catalogist-header" role="banner">
    <?php if ( ! empty( $layout['logo_url'] ) ): ?>
        <img src="<?php echo esc_url( $layout['logo_url'] ); ?>" 
             alt="<?php esc_attr_e( 'Company Logo', 'catalogist' ); ?>"
             class="catalogist-logo">
    <?php endif; ?>
    
    <h1 class="catalogist-title"><?php echo esc_html( $catalog->get_title() ); ?></h1>
    
    <?php if ( ! empty( $layout['header_content'] ) ): ?>
        <div class="catalogist-header-content">
            <?php echo wp_kses_post( $layout['header_content'] ); ?>
        </div>
    <?php endif; ?>
</header>
```

**product-loop.php:**
```php
<?php
/**
 * Product loop template.
 *
 * Context variables:
 * - $items (array<CatalogItem>)
 * - $layout (array)
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="catalogist-product-loop" 
     data-columns="<?php echo esc_attr( $layout['columns'] ?? 2 ); ?>">
    <?php foreach ( $items as $index => $item ): ?>
        <div class="catalogist-product-item catalogist-item-<?php echo esc_attr( $item->get_type() ); ?>"
             data-item-id="<?php echo esc_attr( $item->get_id() ); ?>"
             data-item-type="<?php echo esc_attr( $item->get_type() ); ?>">
            
            <?php echo $this->renderSection( 'card', compact( 'item', 'index', 'count' => count( $items ) ) ); ?>
        </div>
    <?php endforeach; ?>
</div>
```

**product-card.php:**
```php
<?php
/**
 * Product card template.
 *
 * Context variables:
 * - $item (CatalogItem)
 * - $index (int)
 * - $count (int)
 */

defined( 'ABSPATH' ) || exit;
?>
<article class="catalogist-product-card">
    <?php if ( $item->get_image() ): ?>
        <div class="catalogist-product-image">
            <img src="<?php echo esc_url( $item->get_image()['src'] ); ?>"
                 alt="<?php echo esc_attr( $item->get_title() ); ?>"
                 width="<?php echo esc_attr( $item->get_image()['width'] ); ?>"
                 height="<?php echo esc_attr( $item->get_image()['height'] ); ?>">
        </div>
    <?php endif; ?>
    
    <div class="catalogist-product-info">
        <h2 class="catalogist-product-title">
            <a href="<?php echo esc_url( $item->get_permalink() ); ?>">
                <?php echo esc_html( $item->get_title() ); ?>
            </a>
        </h2>
        
        <?php if ( $item->get_sku() ): ?>
            <p class="catalogist-product-sku"><?php echo esc_html( $item->get_sku() ); ?></p>
        <?php endif; ?>
        
        <?php if ( $item->get_price() ): ?>
            <p class="catalogist-product-price">
                <?php echo wc_price( $item->get_price() ); ?>
            </p>
        <?php endif; ?>
        
        <?php if ( $item->get_short_description() ): ?>
            <div class="catalogist-product-short-description">
                <?php echo wp_kses_post( $item->get_short_description() ); ?>
            </div>
        <?php endif; ?>
    </div>
    
    <?php if ( $item->has_variation_table() ): ?>
        <?php echo $this->renderSection( 'variation-table', compact( 'item' ) ); ?>
    <?php endif; ?>
</article>
```

**variation-table.php:**
```php
<?0
/**
 * Variation table template (for table mode).
 *
 * Context variables:
 * - $item (CatalogItem with variation_table populated)
 */

defined( 'ABSPATH' ) || exit;
?>
<table class="catalogist-variation-table">
    <thead>
        <tr>
            <th><?php esc_html_e( 'Variation', 'catalogist' ); ?></th>
            <th><?php esc_html_e( 'SKU', 'catalogist' ); ?></th>
            <th><?php esc_html_e( 'Price', 'catalogist' ); ?></th>
            <th><?php esc_html_e( 'Stock', 'catalogist' ); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ( $item->get_variation_table()['variations'] as $variation ): ?>
            <tr class="catalogist-variation-row">
                <td><?php echo esc_html( $variation['title'] ?? '' ); ?></td>
                <td><?php echo esc_html( $variation['sku'] ?? '' ); ?></td>
                <td><?php echo wc_price( $variation['price'] ?? '' ); ?></td>
                <td class="catalogist-stock-<?php echo esc_attr( $variation['stock_status'] ?? 'instock' ); ?>">
                    <?php echo esc_html( $variation['stock_status'] === 'instock' ? __( 'In Stock', 'catalogist' ) : __( 'Out of Stock', 'catalogist' ) ); ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
```

---

## 9. HEADER/FOOTER/PRODUCT-LOOP/PRODUCT-CARD RESPONSIBILITIES

### Responsibility Matrix

| Component | Responsibility | Data Source | Escaping |
|-----------|---------------|-------------|----------|
| **Header** | Display catalog title, logo, custom content | `$catalog`, `$layout` | `esc_html()`, `esc_url()`, `wp_kses_post()` |
| **Footer** | Display footer content, copyright, contact | `$catalog`, `$layout` | `esc_html()`, `esc_url()`, `wp_kses_post()` |
| **Product Loop** | Iterate over items, apply layout classes | `$items`, `$layout` | N/A (structural) |
| **Product Card** | Display single item data | `$item`, `$index`, `$count` | All output escaped |
| **Variation Table** | Display embedded variation data | `$item->get_variation_table()` | All output escaped |

### Header Responsibilities

- Display catalog title (escaped)
- Display logo image (if configured)
- Display custom header content (filtered with `wp_kses_post()`)
- Apply print header settings (show/hide, margins)
- Add CSS classes for layout (columns, orientation)

### Footer Responsibilities

- Display custom footer content
- Add copyright notice
- Display contact information
- Apply print footer settings

### Product Loop Responsibilities

- Iterate over `$items` array
- Track loop index and count
- Apply alternating classes (`is-first`, `is-last`, `is-even`, `is-odd`)
- Wrap cards in column container
- Apply CSS classes based on column count

### Product Card Responsibilities

- Display product image (if exists)
- Display product title (escaped)
- Display SKU (if exists)
- Display price (using `wc_price()` for formatting)
- Display short description (filtered with `wp_kses_post()`)
- Show variation table if present
- Add data attributes for JavaScript (item ID, type)

---

## 10. SHORTCODE ARCHITECTURE

### Purpose

The shortcode provides a WordPress-native way to display catalogs on posts, pages, and widgets.

### Shortcode Definition

```php
/**
 * Register the catalog shortcode.
 *
 * [catalogist id="123" template="default" columns="2"]
 */
function catalogist_shortcode( array $atts ): string {
    $atts = shortcode_atts(
        array(
            'id'        => 0,
            'template'  => 'default',
            'columns'   => 2,
            'orderby'   => 'date',
            'order'     => 'DESC',
        ),
        $atts,
        'catalogist'
    );
    
    // Validate and sanitize inputs
    $catalog_id = absint( $atts['id'] );
    $template = sanitize_text_field( $atts['template'] );
    $columns = max( 1, min( 4, absint( $atts['columns'] ) ) );
    
    // Load catalog
    $catalog_repo = catalogist()->get_container()->get( CatalogRepositoryInterface::class );
    $catalog = $catalog_repo->find( $catalog_id );
    
    if ( ! $catalog ) {
        return '<p class="catalogist-error">' . 
               esc_html__( 'Catalog not found.', 'catalogist' ) . 
               '</p>';
    }
    
    // Check capabilities
    if ( ! current_user_can( 'read_catalog', $catalog_id ) ) {
        return '<p class="catalogist-error">' . 
               esc_html__( 'You do not have permission to view this catalog.', 'catalogist' ) . 
               '</p>';
    }
    
    // Build context and render
    $engine = catalogist()->get_container()->get( TemplateEngineInterface::class );
    
    return $engine->renderCatalog( $catalog, array(
        'template' => $template,
        'columns'  => $columns,
    ) );
}
add_shortcode( 'catalogist', 'catalogist_shortcode' );
```

### Shortcode Attributes

| Attribute | Type | Default | Description |
|-----------|------|---------|-------------|
| `id` | int | 0 | Catalog ID (required for full catalog display) |
| `template` | string | 'default' | Template slug or ID |
| `columns` | int | 2 | Number of columns (1-4) |
| `orderby` | string | 'date' | Product ordering |
| `order` | string | 'DESC' | Sort order |

### Shortcode Behavior

1. **With `id`**: Load full catalog from database, apply all catalog settings
2. **Without `id`**: Return error message (catalog required)
3. **Invalid `id`**: Return "catalog not found" message
4. **No permission**: Return permission denied message
5. **Template not found**: Fall back to default template

### Security Considerations

- Validate all shortcode attributes with `absint()`, `sanitize_text_field()`, etc.
- Check user capabilities before displaying
- Escape all output in templates
- Use nonces for any AJAX actions
- Never trust shortcode attributes without validation

---

## 11. SECURITY AND ESCAPING BOUNDARIES

### Escaping Responsibility Matrix

| Layer | Responsibility | Functions Used |
|-------|---------------|----------------|
| **CatalogProcessor** | Data normalization only | None (no HTML generation) |
| **TemplateContextBuilder** | Build context with raw data | None |
| **Template Renderer** | Escape all output | `esc_html()`, `esc_attr()`, `esc_url()`, `wp_kses_post()` |
| **Template Files** | Use escaping functions | Must use escaping functions |

### Critical Escaping Rules

1. **All text output must be escaped:**
   ```php
   <?php echo esc_html( $item->get_title() ); ?>
   <?php echo esc_attr( $item->get_sku() ); ?>
   ```

2. **All URLs must be escaped:**
   ```php
   <?php echo esc_url( $item->get_permalink() ); ?>
   ```

3. **HTML content must be filtered:**
   ```php
   <?php echo wp_kses_post( $item->get_short_description() ); ?>
   ```

4. **Numeric values must be cast:**
   ```php
   <?php echo esc_attr( (int) $item->get_id() ); ?>
   ```

### Double Escaping Prevention

**Rule: Escape at the template layer only, never in the data layer.**

```php
// WRONG: Escaping in processor
$context['title'] = esc_html( $item->get_title() );

// CORRECT: Keep raw data, escape in template
$context['item'] = $item;
// Template: <?php echo esc_html( $item->get_title() ); ?>
```

**Exception:** Pre-escaped helper data can be provided for convenience:
```php
$context['escaped'] = array(
    'title' => esc_html( $item->get_title() ),
    'sku'   => esc_html( $item->get_sku() ),
);
```

### Template File Security

1. **No direct database queries** in template files
2. **No business logic** in template files
3. **All output escaped** using appropriate functions
4. **Nonces for any form submissions** (future)
5. **Capability checks** before rendering (shortcode layer)

---

## 12. VARIATION TABLE RENDERING

### Data Flow for Table Mode

```
CatalogProcessor (table mode)
    ↓
CatalogItem with variation_table populated
    ↓
TemplateContextBuilder passes $item to context
    ↓
Template renderer calls renderSection('variation-table', ...)
    ↓
variation-table.php renders table HTML
```

### Variation Table Structure

```php
// From CatalogItem::get_variation_table()
array(
    'parent_id' => 125,
    'variations' => array(
        241 => array(
            'id'           => 241,
            'title'        => 'Red / Large',
            'attributes'   => array( 'Color' => 'Red', 'Size' => 'Large' ),
            'price'        => '19.99',
            'sale_price'   => '',
            'sku'          => 'RED-L',
            'stock_status' => 'instock',
            'permalink'    => 'https://...',
            'image'        => array( 'id' => 500, 'src' => '...', 'width' => 80, 'height' => 80 ),
        ),
        242 => array(
            'id'           => 242,
            'title'        => 'Blue / Large',
            'attributes'   => array( 'Color' => 'Blue', 'Size' => 'Large' ),
            'price'        => '21.99',
            'sale_price'   => '18.99',
            'sku'          => 'BLU-L',
            'stock_status' => 'outofstock',
            'permalink'    => '',
            'image'        => null,
        ),
    ),
)
```

### Template Rendering Logic

```php
// In product-card.php or variation-table.php
if ( $item->has_variation_table() ) {
    $tableData = $item->get_variation_table();
    
    // Render table
    echo '<table class="catalogist-variation-table">';
    echo '<thead><tr>';
    echo '<th>' . esc_html__( 'Variation', 'catalogist' ) . '</th>';
    echo '<th>' . esc_html__( 'SKU', 'catalogist' ) . '</th>';
    echo '<th>' . esc_html__( 'Price', 'catalogist' ) . '</th>';
    echo '<th>' . esc_html__( 'Stock', 'catalogist' ) . '</th>';
    echo '</tr></thead>';
    
    echo '<tbody>';
    foreach ( $tableData['variations'] as $variation ) {
        echo '<tr>';
        echo '<td>' . esc_html( $variation['title'] ?? '' ) . '</td>';
        echo '<td>' . esc_html( $variation['sku'] ?? '' ) . '</td>';
        echo '<td>' . wc_price( $variation['price'] ?? '' ) . '</td>';
        echo '<td class="stock-' . esc_attr( $variation['stock_status'] ?? 'instock' ) . '">';
        echo esc_html( $variation['stock_status'] === 'instock' ? __( 'In Stock', 'catalogist' ) : __( 'Out of Stock', 'catalogist' ) );
        echo '</td>';
        echo '</tr>';
    }
    echo '</tbody>';
    echo '</table>';
}
```

### Key Design Decisions

1. **Variation table is part of the product card** — Not a separate CatalogItem
2. **Template detects table mode via `has_variation_table()`** — Clean branching logic
3. **All variation data pre-normalized** — Template receives clean arrays
4. **Stock status classes for styling** — `instock`, `outofstock`, `onbackorder`

---

## 13. ERROR AND MISSING-TEMPLATE HANDLING

### Error Handling Strategy

**Tier 1: Template Not Found**
```php
// In TemplateRenderer
if ( ! $path ) {
    error_log( 'Catalogist: Template not found: ' . $templateSlug );
    return $this->renderFallback( $context );
}
```

**Tier 2: Template Render Error**
```php
// In TemplateRenderer
try {
    require $path;
} catch ( \Throwable $e ) {
    error_log( 'Catalogist template error: ' . $e->getMessage() );
    return $this->renderFallback( $context );
}
```

**Tier 3: Catalog Not Found**
```php
// In shortcode handler
if ( ! $catalog ) {
    return '<p class="catalogist-error">' . 
           esc_html__( 'Catalog not found.', 'catalogist' ) . 
           '</p>';
}
```

### Fallback Template

A minimal built-in fallback template ensures the plugin never produces fatal errors:

```php
// Built-in fallback (not a file, hardcoded string)
private function renderFallback( array $context ): string {
    return '<div class="catalogist-fallback">' .
           '<h2>' . esc_html__( 'Catalog Template Error', 'catalogist' ) . '</h2>' .
           '<p>' . esc_html__( 'The requested template could not be loaded. Please check your template configuration.', 'catalogist' ) . '</p>' .
           '</div>';
}
```

### Admin Error Logging

- Errors are logged to `error_log()` (not displayed to users)
- Admin users can view logs via WP_DEBUG or custom admin page (Milestone 10)
- No error information leaked to frontend

---

## 14. PERFORMANCE CONSIDERATIONS

### Template Caching

**File Existence Caching:**
```php
// Cache resolved template paths to avoid repeated filesystem checks
private array $pathCache = array();

private function resolvePath( string $slug ): ?string {
    if ( isset( $this->pathCache[ $slug ] ) ) {
        return $this->pathCache[ $slug ];
    }
    
    // ... resolve path ...
    
    $this->pathCache[ $slug ] = $path;
    return $path;
}
```

**Template Content Caching (Future):**
- For large catalogs, consider caching rendered HTML in transients
- Invalidation based on catalog updates
- Not implemented in Milestone 5 (Milestone 10 concern)

### Context Building Performance

**Avoid N+1 queries:**
- Build context in bulk, not per-item
- Pre-calculate loop metadata (is_first, is_last, is_even, is_odd)
- Avoid calling WordPress functions inside loops where possible

**Efficient array operations:**
```php
// Bad: Calling function in loop
foreach ( $items as $index => $item ) {
    $isFirst = ( $index === 0 );
    $isLast = ( $index === count( $items ) - 1 );
    // ...
}

// Good: Pre-calculate
$count = count( $items );
foreach ( $items as $index => $item ) {
    $isFirst = ( $index === 0 );
    $isLast = ( $index === $count - 1 );
    // ...
}
```

### Memory Considerations

- Template context arrays should be lightweight
- Avoid storing large objects in context
- Use references where appropriate
- Clear caches after rendering (for CLI/context)

---

## 15. EXTENSIBILITY FOR ELEMENTOR, PRINT, PREVIEW AND OUTPUT

### Extension Points

**1. Template Loader Interface**
```php
// Future: Elementor template loader
class ElementorTemplateLoader implements TemplateLoaderInterface {
    public function load( $templateId, string $defaultFallback = '' ): ?Template {
        // Load Elementor template by ID
    }
}

// Future: Database template loader
class DatabaseTemplateLoader implements TemplateLoaderInterface {
    public function load( $templateId, string $defaultFallback = '' ): ?Template {
        // Load from custom database table
    }
}
```

**2. Renderer Interface**
```php
// Future: Elementor renderer
class ElementorTemplateRenderer implements TemplateRendererInterface {
    public function render( string $templateSlug, array $context ): string {
        // Render using Elementor API
    }
}

// Future: Print renderer
class PrintTemplateRenderer implements TemplateRendererInterface {
    public function render( string $templateSlug, array $context ): string {
        // Apply print-specific CSS and layout
    }
}
```

**3. Filter Hooks**
```php
// Allow modification of template context
$context = apply_filters( 'catalogist_template_context', $context, $catalog, $items );

// Allow modification of rendered output
$output = apply_filters( 'catalogist_template_output', $output, $templateSlug, $context );

// Allow template path override
$templatePath = apply_filters( 'catalogist_template_path', $templatePath, $templateSlug );
```

**4. Action Hooks**
```php
// Before rendering
do_action( 'catalogist_before_template_render', $templateSlug, $context );

// After rendering
do_action( 'catalogist_after_template_render', $output, $templateSlug, $context );
```

### Future Module Isolation

**Elementor Integration (Milestone 6):**
- Creates `ElementorTemplateLoader` and `ElementorTemplateRenderer`
- Uses same `TemplateContext` contract
- No changes to core template engine

**Print Engine (Milestone 7):**
- Creates `PrintTemplateRenderer` wrapper
- Applies print CSS via `@media print`
- No changes to base template files

**Preview Engine (Milestone 8):**
- Reuses existing renderers
- Adds iframe preview wrapper
- No changes to core rendering logic

**Output Engine (Milestone 9):**
- Creates `HtmlOutput`, `PdfOutput` implementations
- Uses renderer output as input
- No changes to template layer

---

## 16. ALLOWED AND FORBIDDEN DEPENDENCIES

### Allowed Dependencies

| Dependency | Module | Justification |
|-----------|--------|---------------|
| `Catalog` | `Catalogist\Catalog` | Template needs catalog data |
| `CatalogItem` | `Catalogist\CatalogItem` | Template needs item data |
| `TemplateContextBuilder` | `Catalogist\Template` | Own module |
| `TemplateLoaderInterface` | `Catalogist\Template` | Own module |
| `TemplateRendererInterface` | `Catalogist\Template` | Own module |
| `TemplateEngineInterface` | `Catalogist\Template` | Own module |
| `wc_price()` | WooCommerce | Price formatting (allowed in template layer) |
| `wp_kses_post()` | WordPress | HTML filtering (allowed in template layer) |
| `esc_html()`, `esc_attr()`, `esc_url()` | WordPress | Output escaping (required) |
| `apply_filters()`, `do_action()` | WordPress | Extension points |
| `get_template_directory()` | WordPress | Theme template discovery |
| `plugin_dir_path()` | WordPress | Plugin path resolution |

### Forbidden Dependencies

| Anti-Dependency | Reason |
|----------------|--------|
| `ProductRepositoryInterface` | Template must not query products |
| `VariationServiceInterface` | Template must not expand variations |
| `CatalogProcessor` | Template must not normalize data |
| `WooCommerceProductRepository` | Concrete implementation dependency |
| `WooCommerceVariationRepository` | Concrete implementation dependency |
| `Elementor\*` | Elementor is Milestone 6 |
| `\WC_Product` | Raw WooCommerce objects must not leak |
| `$wpdb` | Direct database queries forbidden |
| `wc_get_products()` | Product queries forbidden |
| `wc_get_product()` | Single product queries forbidden |
| `print_*` functions | Print logic is Milestone 7 |
| `dompdf\*`, `mpdf\*` | PDF libraries are Milestone 9 |

---

## 17. PROPOSED NEW FILES

### Core Template Engine Files

```
src/Template/
├── TemplateEngine.php              # Main orchestrator
├── TemplateEngineInterface.php     # Interface for DI
├── TemplateContextBuilder.php      # Builds context arrays
├── TemplateContextBuilderInterface.php
├── TemplateLoaderInterface.php     # Loader interface
├── TemplateRendererInterface.php   # Renderer interface
├── Template.php                    # Template value object
├── Loader/
│   └── FileTemplateLoader.php      # File-based loader implementation
├── Renderer/
│   └── FileTemplateRenderer.php    # File-based renderer implementation
└── TemplateServiceProvider.php     # Service provider
```

### Template Files (Default Layout)

```
templates/
├── default/
│   ├── catalog.php
│   ├── header.php
│   ├── footer.php
│   ├── product-loop.php
│   ├── product-card.php
│   └── variation-table.php
└── fallback/
    └── catalog.php
```

### Test Files

```
tests/Unit/Template/
├── TemplateContextBuilderTest.php
├── FileTemplateLoaderTest.php
└── FileTemplateRendererTest.php

tests/Integration/Template/
└── TemplateEngineTest.php
```

### Total New Files: 14

- 10 source files
- 6 template files
- 4 test files

---

## 18. EXISTING FILES TO MODIFY

### Required Modifications

| File | Change | Reason |
|------|--------|--------|
| `src/Core/Plugin.php` | Add `TemplateServiceProvider` to provider list | Register template engine |
| `src/Catalog/Catalog.php` | No changes needed | Already has `template_id` field |
| `src/Catalog/CatalogRepository.php` | No changes needed | Already saves/loads `template_id` |

### Optional Modifications (for completeness)

| File | Change | Reason |
|------|--------|--------|
| `src/Catalog/CatalogFactory.php` | Add template loading in `from_post()` | Load template ID from post meta |

**Note:** Minimal modifications to existing files to avoid breaking changes.

---

## 19. FILES INTENTIONALLY NOT MODIFIED

### Core Files (Read-Only)
```
src/Core/Container.php
src/Core/ServiceProviderInterface.php
src/Core/ModuleInterface.php
src/Core/HookableInterface.php
src/Core/Plugin.php (only provider registration added)
src/Catalog/Catalog.php
src/Catalog/CatalogRepository.php
src/Catalog/CatalogRepositoryInterface.php
src/Catalog/CatalogFactory.php
src/Catalog/CatalogPostType.php
src/Product/ProductQueryResult.php
src/Product/ProductRepositoryInterface.php
src/Product/WooCommerceProductRepository.php
src/Variation/VariationService.php
src/Variation/VariationServiceInterface.php
src/Variation/VariationMode.php
src/Variation/VariationQueryArgs.php
src/Variation/VariationQueryResult.php
src/CatalogItem/CatalogItem.php
src/CatalogItem/CatalogItemFactory.php
src/CatalogItem/CatalogProcessor.php
src/CatalogItem/CatalogServiceProvider.php
```

### Reasoning
- These files are part of established contracts
- Modifications would risk breaking Milestones 1–4
- New functionality added via new files and interfaces
- Backward compatibility maintained

---

## 20. UNIT AND INTEGRATION TEST PLAN

### Unit Tests

**TemplateContextBuilderTest:**
```php
class TemplateContextBuilderTest extends TestCase {
    public function test_build_context_with_all_data(): void;
    public function test_build_context_with_missing_optional_fields(): void;
    public function test_build_loop_context_with_index_and_count(): void;
    public function test_loop_context_provides_is_first_is_last_is_even_is_odd(): void;
    public function test_context_contains_escaped_helper_data(): void;
    public function test_context_with_empty_items_array(): void;
    public function test_context_with_layout_settings(): void;
    public function test_context_with_print_settings(): void;
}
```

**FileTemplateLoaderTest:**
```php
class FileTemplateLoaderTest extends TestCase {
    public function test_load_existing_template(): void;
    public function test_load_nonexistent_template_returns_null(): void;
    public function test_get_path_resolves_correctly(): void;
    public function test_get_path_returns_null_for_missing_template(): void;
    public function test_clear_cache(): void;
    public function test_caches_resolved_paths(): void;
}
```

**FileTemplateRendererTest:**
```php
class FileTemplateRendererTest extends TestCase {
    public function test_render_existing_template(): void;
    public function test_render_missing_template_returns_fallback(): void;
    public function test_render_with_context_variables(): void;
    public function test_render_applies_filters(): void;
    public function test_render_handles_template_errors_gracefully(): void;
    public function test_render_section(): void;
}
```

### Integration Tests

**TemplateEngineTest:**
```php
class TemplateEngineTest extends TestCase {
    public function test_render_catalog_with_default_template(): void;
    public function test_render_catalog_with_custom_template(): void;
    public function test_render_catalog_with_table_mode(): void;
    public function test_render_catalog_with_missing_template_falls_back(): void;
    public function test_render_catalog_empty_items(): void;
    public function test_render_catalog_with_variation_table(): void;
    public function test_shortcode_rendering(): void;
    public function test_shortcode_with_invalid_id(): void;
    public function test_shortcode_with_no_permission(): void;
}
```

### Architecture Boundary Tests

```php
class TemplateArchitectureTest extends TestCase {
    public function test_template_layer_does_not_query_products(): void {
        // Verify TemplateEngine does not depend on ProductRepositoryInterface
    }
    
    public function test_template_layer_does_not_expand_variations(): void {
        // Verify TemplateEngine does not depend on VariationServiceInterface
    }
    
    public function test_template_layer_does_not_use_elementor(): void {
        // Verify no Elementor class references
    }
    
    public function test_template_layer_escapes_all_output(): void {
        // Verify template files use escaping functions
    }
    
    public function test_template_context_does_not_contain_raw_wc_product(): void {
        // Verify context arrays do not contain \WC_Product objects
    }
}
```

---

## 21. RISKS AND MITIGATIONS

| Risk | Severity | Mitigation |
|------|----------|------------|
| Template files contain business logic | High | Code review, documentation, static analysis |
| Double escaping occurs | Medium | Clear documentation, context builder provides pre-escaped helpers |
| Template path traversal attack | Medium | Validate template slugs, use whitelist, sanitize paths |
| Missing templates cause fatal errors | Medium | Fallback template, try-catch around require |
| Performance degradation with large catalogs | Medium | Template caching, lazy loading, profiling |
| Inconsistent escaping across templates | High | Coding standards, PHPCS rules, code review |
| Template context structure changes break existing templates | Medium | Versioning, backward compatibility layer |
| WooCommerce function dependencies in templates | Low | Document allowed functions, provide fallbacks |

---

## 22. RECOMMENDED IMPLEMENTATION ORDER

### Phase 1: Core Interfaces and Builders
1. Create `TemplateContextBuilderInterface` and `TemplateContextBuilder`
2. Create `TemplateLoaderInterface`
3. Create `TemplateRendererInterface`
4. Create `TemplateEngineInterface`

### Phase 2: File-Based Implementation
5. Create `FileTemplateLoader` implementation
6. Create `FileTemplateRenderer` implementation
7. Create `TemplateEngine` orchestrator
8. Create `TemplateServiceProvider`

### Phase 3: Template Files
9. Create default template files (catalog.php, header.php, footer.php, product-loop.php, product-card.php, variation-table.php)
10. Create fallback template

### Phase 4: Integration
11. Register `TemplateServiceProvider` in `Plugin.php`
12. Create shortcode handler
13. Wire up shortcode to template engine

### Phase 5: Testing
14. Create unit tests for ContextBuilder
15. Create unit tests for Loader
16. Create unit tests for Renderer
17. Create integration tests for Engine
18. Create architecture boundary tests
19. Run all tests, fix failures

### Phase 6: Documentation
20. Document template context variables
21. Document template file structure
22. Document shortcode usage
23. Update CLAUDE.md with Milestone 5 completion

---

## 23. ACCEPTANCE CRITERIA

### Functional Requirements
- [ ] Template engine loads templates from filesystem
- [ ] Template engine falls back to default template when custom template not found
- [ ] Template engine handles missing templates gracefully (no fatal errors)
- [ ] Shortcode `[catalogist id="123"]` renders catalog with default template
- [ ] Shortcode validates input and returns error for invalid IDs
- [ ] Shortcode checks user capabilities
- [ ] Template context contains all required fields (catalog, items, item, layout, print)
- [ ] Loop context provides index, count, and boolean flags (is_first, is_last, etc.)
- [ ] Variation table renders correctly in table mode
- [ ] All output is properly escaped

### Security Requirements
- [ ] No direct database queries in template layer
- [ ] No WooCommerce API calls in template layer
- [ ] All text output escaped with `esc_html()` or appropriate function
- [ ] All URLs escaped with `esc_url()`
- [ ] HTML content filtered with `wp_kses_post()`
- [ ] Shortcode attributes validated and sanitized
- [ ] User capabilities checked before rendering
- [ ] No raw `\WC_Product` objects in template context

### Performance Requirements
- [ ] Template paths cached to avoid repeated filesystem checks
- [ ] Context building does not cause N+1 queries
- [ ] Template rendering uses output buffering (no direct echo)
- [ ] Error logging for admin-only error reporting

### Code Quality Requirements
- [ ] All new classes follow existing naming conventions
- [ ] All new classes have proper PHPDoc comments
- [ ] All new files have `declare(strict_types=1);`
- [ ] All new files have `defined( 'ABSPATH' ) || exit;`
- [ ] Interface-based design with dependency injection
- [ ] No God classes (single responsibility maintained)
- [ ] PSR-4 autoloading compliant
- [ ] WordPress coding standards followed

### Testing Requirements
- [ ] Unit tests for TemplateContextBuilder (7+ test cases)
- [ ] Unit tests for FileTemplateLoader (5+ test cases)
- [ ] Unit tests for FileTemplateRenderer (5+ test cases)
- [ ] Integration tests for TemplateEngine (5+ test cases)
- [ ] Architecture boundary tests (5+ test cases)
- [ ] All existing tests still pass
- [ ] Code coverage for new code > 80%

---

## 24. OPEN ARCHITECTURAL QUESTIONS

### Question 1: Template Storage Strategy
**Issue:** Should custom templates be stored as CPT `ctlg_template` or only as filesystem files?

**Option A: CPT-Based Only**
- Pros: Admin UI for editing, versioning, user management
- Cons: More complex, requires CPT infrastructure

**Option B: Filesystem-Only (Recommended for MVP)**
- Pros: Simpler, follows WordPress theme template hierarchy, easier to version control
- Cons: No admin UI for editing

**Option C: Hybrid (Recommended)**
- Default templates as files
- Custom templates as CPT with content stored in post_content
- Theme overrides via `get_template_directory()`

**Recommendation:** Option C — Hybrid approach. Start with filesystem templates, add CPT support in Milestone 10.

---

### Question 2: Template Context Structure
**Issue:** Should the context builder provide both raw and pre-escaped data?

**Option A: Raw Data Only**
- Template files responsible for all escaping
- Consistent with WordPress theme development
- Risk: Developers might forget to escape

**Option B: Pre-Escaped Helper Data**
- Context builder provides `$context['escaped']` array
- Template files can choose to use pre-escaped or raw data
- Safer default, but requires discipline

**Recommendation:** Option B — Provide both. Raw data in `$item`, pre-escaped in `$context['escaped']`. Document clearly that raw data must be escaped in templates.

---

### Question 3: Variation Table Placement
**Issue:** Should the variation table render inside the product card or as a separate section?

**Option A: Inside Product Card**
- Pros: Simpler template structure, single render call
- Cons: Template becomes more complex with conditional logic

**Option B: Separate Section**
- Pros: Cleaner separation, easier to override
- Cons: More render calls, slightly more complex orchestration

**Recommendation:** Option A — Inside product card. The template checks `$item->has_variation_table()` and renders inline. This matches the CatalogItem structure where variation table is part of the parent item.

---

### Question 4: Template Filter Hooks
**Issue:** What filter hooks should be exposed for template customization?

**Recommended Hooks:**
```php
// Modify template context before rendering
apply_filters( 'catalogist_template_context', $context, $catalog, $items );

// Modify rendered output
apply_filters( 'catalogist_template_output', $output, $templateSlug, $context );

// Override template path
apply_filters( 'catalogist_template_path', $path, $templateSlug );

// Before rendering
do_action( 'catalogist_before_template_render', $templateSlug, $context );

// After rendering
do_action( 'catalogist_after_template_render', $output, $templateSlug, $context );
```

These hooks provide maximum flexibility while maintaining clean architecture.

---

## FINAL VERDICT

## APPROVED FOR IMPLEMENTATION

The Milestone 5 Template Engine architecture is sound and ready for implementation.

### Key Architectural Decisions:

1. **Three-component design**: TemplateEngine (orchestrator), TemplateLoader (file discovery), TemplateRenderer (output generation)

2. **Hybrid template storage**: Filesystem templates for defaults, CPT support planned for future

3. **Context builder pattern**: Separates data preparation from rendering, provides both raw and pre-escaped data

4. **Fallback chain**: Catalog template → Theme override → Plugin default → Built-in fallback

5. **Security boundaries**: Escaping only at template layer, no business logic in templates

6. **Extension points**: Interface-based design allows Elementor, Print, and Output engines to plug in without changes

7. **Minimal existing file modifications**: Only Plugin.php provider registration required

### Next Steps:
1. Await user approval to proceed with implementation
2. Begin with core interfaces (Phase 1)
3. Implement file-based loaders and renderers (Phase 2)
4. Create default template files (Phase 3)
5. Integrate with shortcode (Phase 4)
6. Write comprehensive tests (Phase 5)

---

**Report Generated:** 2026-08-25  
**Previous Milestone Completed:** Milestone 4 (Catalog Processor)  
**Next Milestone:** Milestone 6 (Elementor Integration)
