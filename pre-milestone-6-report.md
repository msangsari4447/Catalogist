# Pre-Milestone Report: Milestone 6 — Elementor Integration

**Date:** 2026-08-25
**Status:** For Architectural Review
**Author:** Agnes (Sapiens AI)

---

## 1. CURRENT ARCHITECTURE VERIFICATION

### Verified Codebase State (as of 2026-08-25)

#### Existing Modules (Milestones 1–5 Complete)

| Module | Namespace | Status | Key Files |
|--------|-----------|--------|-----------|
| Core | `Catalogist\Core` | ✓ Complete | Plugin.php, Container.php, ServiceProviderInterface.php |
| Catalog | `Catalogist\Catalog` | ✓ Complete | Catalog.php, CatalogRepository.php, CatalogPostType.php |
| Product | `Catalogist\Product` | ✓ Complete | ProductQueryResult.php, ProductRepositoryInterface.php, WooCommerceProductRepository.php |
| Variation | `Catalogist\Variation` | ✓ Complete | VariationService.php, VariationMode.php, VariationQueryArgs.php |
| CatalogItem | `Catalogist\CatalogItem` | ✓ Complete | CatalogItem.php, CatalogItemFactory.php, CatalogProcessor.php |
| Template | `Catalogist\Template` | ✓ Complete | TemplateEngine.php, TemplateContextBuilder.php, Loader/FileTemplateLoader.php, Renderer/FileTemplateRenderer.php |
| Security | `Catalogist\Security` | ✓ Complete | SecurityServiceProvider.php |

#### Critical Architecture Contracts

**CatalogItem (src/CatalogItem/CatalogItem.php)**
- Immutable value object with 21 typed fields
- Type helpers: `is_product()`, `is_variation()`, `has_variation_table()`, `is_variable_product()`
- `to_array()` returns complete serializable representation
- **No direct WooCommerce dependencies** — normalization complete at factory/processor layer

**TemplateEngine (src/Template/TemplateEngine.php)**
- Three-component architecture: `TemplateEngine` → `TemplateLoader` → `TemplateContextBuilder` → `TemplateRenderer`
- `renderCatalog(Catalog, array<CatalogItem>, ?array $settings): string`
- `renderItem(Catalog, CatalogItem, ?array $settings): string`
- Getter methods: `getLoader()`, `getRenderer()`, `getContextBuilder()`
- **Interface-based**: depends only on `TemplateLoaderInterface`, `TemplateRendererInterface`, `TemplateContextBuilderInterface`

**TemplateContextBuilder (src/Template/TemplateContextBuilder.php)**
- `build(Catalog, array<CatalogItem>, ?array $layout, ?array $print): array`
- `buildLoopContext(Catalog, CatalogItem, int $index, int $count): array`
- Provides `$context['escaped']` with pre-escaped helpers
- Layout settings normalized: columns (1-4), page_size, orientation, show_header, show_footer
- Print settings normalized: margins array

**TemplateLoaderInterface (src/Template/TemplateLoaderInterface.php)**
```php
interface TemplateLoaderInterface {
    public function load( $templateId, string $defaultFallback = '' ): ?Template;
    public function getPath( string $templateSlug ): ?string;
    public function clearCache(): void;
}
```

**TemplateRendererInterface (src/Template/TemplateRendererInterface.php)**
```php
interface TemplateRendererInterface {
    public function render( string $templateSlug, array $context ): string;
    public function renderSection( string $section, array $context ): string;
}
```

**TemplateEngineInterface (src/Template/TemplateEngineInterface.php)**
```php
interface TemplateEngineInterface {
    public function renderCatalog( Catalog $catalog, array $catalogItems, ?array $settings = null ): string;
    public function renderItem( Catalog $catalog, CatalogItem $item, ?array $settings = null ): string;
    public function getLoader(): TemplateLoaderInterface;
    public function getRenderer(): TemplateRendererInterface;
    public function getContextBuilder(): TemplateContextBuilderInterface;
}
```

**TemplateServiceProvider (src/Template/TemplateServiceProvider.php)**
- Registers services in container
- Loads shortcode and helper function files
- Calls `register_shortcode()` in `boot()`

**Service Container Registration (src/Core/Plugin.php:141-149)**
```php
$providers = array(
    SecurityServiceProvider::class,
    AdminServiceProvider::class,
    CatalogServiceProvider::class,
    CatalogItemServiceProvider::class,
    ProductServiceProvider::class,
    VariationServiceProvider::class,
    new TemplateServiceProvider( CATALOGIST_PLUGIN_DIR ),
);
```

---

## 2. EXACT SCOPE AND GOAL OF MILESTONE 6

### Primary Goal

Build an **Elementor Integration Layer** that:
1. Provides Dynamic Tags for product and variation data
2. Creates a Product Card widget that uses the TemplateEngine
3. Creates a WooCommerce Catalog widget for displaying saved catalogs
4. Loads conditionally only when Elementor is active

### Scope Boundaries

**IN SCOPE:**
- Elementor Dynamic Tags for product data (name, SKU, price, image, etc.)
- Elementor Dynamic Tags for variation data (name, SKU, price, attributes, etc.)
- Product Card Elementor widget (receives CatalogItem context)
- WooCommerce Catalog Elementor widget (renders full catalog)
- Widget controls for catalog selection, preview, display mode
- Conditional Elementor loading (graceful degradation when inactive)
- QR Code dynamic tag support

**OUT OF SCOPE (Future Milestones):**
- Admin UI for template creation/editing (Milestone 10)
- Print CSS and A4 layout (Milestone 7)
- Preview engine (Milestone 8)
- PDF export (Milestone 9)
- Full Elementor template builder integration (future enhancement)
- Custom template editor widgets
- ACF/JetEngine integration for custom fields

### Data Flow Position

```
WooCommerce
     ↓
Product Query Engine (Milestone 2)
     ↓
Variation Service (Milestone 3)
     ↓
Catalog Processor (Milestone 4)
     ↓
CatalogItem objects (Milestone 4)
     ↓
Template Context Builder (Milestone 5)
     ↓
Template Engine (Milestone 5)
     ↓
HTML Output (Milestone 5)
     ↓
Elementor Widgets (Milestone 6) ← THIS MILESTONE
     ↓
Print Engine (Milestone 7)
     ↓
Output Engine (Milestone 9)
```

### What This Milestone Does NOT Do

- No WooCommerce API calls in Elementor layer (data already normalized)
- No product querying or variation expansion
- No template file creation or modification
- No changes to core TemplateEngine architecture
- No direct Elementor core modifications
- No Elementor dependencies at plugin load time

---

## 3. ELEMENTOR INTEGRATION ARCHITECTURE

### High-Level Design

The Elementor integration follows an **adapter pattern** where Elementor-specific code bridges Elementor's APIs with our existing TemplateEngine:

```
Elementor Integration Layer
├── DynamicTags (ProductData, VariationData, CatalogData)
├── Widgets
│   ├── ProductCardWidget (renders single CatalogItem)
│   └── CatalogWidget (renders full catalog via TemplateEngine)
├── Controls (CatalogSelector, DisplayModeSelector)
└── ServiceProvider (conditional loading)
```

### Architectural Pattern

**Conditional Loading:**
- Elementor code in `src/Elementor/` namespace
- Loaded only when `class_exists( '\Elementor\Plugin' )`
- Plugin boots normally without Elementor

**Adapter Pattern:**
- `ElementorProductDynamicTag` → reads from `CatalogItem` via context
- `ElementorCatalogWidget` → delegates to `TemplateEngineInterface`
- No direct coupling between core and Elementor

**Interface-Based:**
- Widgets receive `TemplateEngineInterface` via constructor
- Dynamic tags receive `CatalogItem` context data
- All rendering delegated to existing `TemplateEngine`

### Core Principles

1. **Conditional Loading**: Elementor code never executes unless Elementor is active
2. **Reusability**: Elementor widgets use existing `TemplateEngine` — no duplicate rendering logic
3. **Context Consistency**: Same `$context` array used by shortcode and Elementor widgets
4. **Graceful Degradation**: If Elementor is disabled, core functionality continues
5. **Official APIs Only**: Use Elementor's documented Dynamic Tags and Widget APIs

---

## 4. DYNAMIC TAGS DESIGN

### Purpose

Elementor Dynamic Tags allow users to inject dynamic content into any Elementor element. For Catalogist, we provide dynamic tags for:

- Product data (name, SKU, price, image, etc.)
- Variation data (name, SKU, price, attributes, etc.)
- Catalog data (catalog title, product count, etc.)

### Dynamic Tag Contract

```php
namespace Catalogist\Elementor\DynamicTags;

use ElementorPro\Modules\DynamicTags\Modules\DynamicTagBase;

abstract class ProductDynamicTag extends DynamicTagBase {
    // Standard Elementor Dynamic Tag methods
    public function get_name(): string;
    public function get_title(): string;
    public function get_group(): ?array;
    public function render(): void;
    public function render_plain_content( array &$controls_data ): void;
    public function get_control_settings(): array;
}
```

### Dynamic Tags to Implement

#### Product Dynamic Tags

| Tag Name | Tag ID | Returns | Example |
|----------|--------|---------|---------|
| Product Name | `catalogist_product_name` | String | `Simple T-Shirt` |
| Product SKU | `catalogist_product_sku` | String | `TS-001` |
| Product Price | `catalogist_product_price` | String | `$29.99` |
| Product Sale Price | `catalogist_product_sale_price` | String | `$24.99` |
| Product Regular Price | `catalogist_product_regular_price` | String | `$29.99` |
| Product Image | `catalogist_product_image` | Array {url, width, height} | `['url' => '...', 'width' => 800, 'height' => 600]` |
| Product Gallery | `catalogist_product_gallery` | Array of image arrays | `[{'url': '...', ...}, ...]` |
| Product Description | `catalogist_product_description` | String (HTML) | `<p>Full description...</p>` |
| Product Short Description | `catalogist_product_short_description` | String (HTML) | `<p>Short desc...</p>` |
| Product Categories | `catalogist_product_categories` | String (comma-separated) | `Shirts, Casual` |
| Product Tags | `catalogist_product_tags` | String (comma-separated) | `cotton, summer` |
| Product Attributes | `catalogist_product_attributes` | Array {name => value} | `['Color' => 'Red', 'Size' => 'L']` |
| Product Stock Status | `catalogist_product_stock_status` | String | `instock` |
| Product Stock Quantity | `catalogist_product_stock_quantity` | Int | `150` |
| Product URL | `catalogist_product_url` | String | `https://...` |
| Product ID | `catalogist_product_id` | Int | `125` |
| QR Code (Product URL) | `catalogist_product_qr_code` | String (img tag) | `<img src="...">` |

#### Variation Dynamic Tags

| Tag Name | Tag ID | Returns | Example |
|----------|--------|---------|---------|
| Variation Name | `catalogist_variation_name` | String | `Red / Large` |
| Variation SKU | `catalogist_variation_sku` | String | `RS-L` |
| Variation Price | `catalogist_variation_price` | String | `$19.99` |
| Variation Attributes | `catalogist_variation_attributes` | Array {name => value} | `['Color' => 'Red', 'Size' => 'L']` |
| Variation Parent Name | `catalogist_variation_parent_name` | String | `Simple T-Shirt` |
| Variation Parent SKU | `catalogist_variation_parent_sku` | String | `TS-001` |
| Variation Image | `catalogist_variation_image` | Array {url, width, height} | `['url' => '...', ...]` |

#### Catalog Dynamic Tags

| Tag Name | Tag ID | Returns | Example |
|----------|--------|---------|---------|
| Catalog Title | `catalogist_catalog_title` | String | `Summer Collection 2026` |
| Catalog Product Count | `catalogist_catalog_product_count` | Int | `42` |
| Catalog Template | `catalogist_catalog_template` | String | `default` |

### Dynamic Tag Implementation Pattern

```php
namespace Catalogist\Elementor\DynamicTags;

use ElementorPro\Modules\DynamicTags\Modules\DynamicTagBase;
use Catalogist\CatalogItem\CatalogItem;

class ProductNameDynamicTag extends DynamicTagBase {

    public function get_name(): string {
        return 'catalogist_product_name';
    }

    public function get_title(): string {
        return __( 'Product Name', 'catalogist' );
    }

    public function get_group(): array {
        return array( 'catalogist-products' );
    }

    public function render(): string {
        $product_id = $this->get_settings( 'product_id' );

        if ( ! $product_id ) {
            return '';
        }

        // In a real implementation, this would query the CatalogItem
        // For dynamic tags, we use the CatalogItem context passed by Elementor
        $item = $this->resolve_catalog_item( $product_id );

        return $item ? $item->get_title() : '';
    }

    private function resolve_catalog_item( int $product_id ): ?CatalogItem {
        // Query or cache the CatalogItem by product ID
        // This can use the ProductRepositoryInterface
        // For now, return null as dynamic tags in Elementor
        // will receive context from the widget
        return null;
    }

    protected function get_control_settings(): array {
        return array(
            'product_id' => array(
                'label' => __( 'Product ID', 'catalogist' ),
                'type' => 'text',
            ),
        );
    }
}
```

### Context Injection for Dynamic Tags

Elementor Dynamic Tags receive context from the parent widget. Our widget will pass the `CatalogItem` to dynamic tags via:

```php
// In the ProductCardWidget render method:
$context = $this->prepare_context();

// Dynamic tags will have access to:
// $context['item'] = CatalogItem
// $context['catalog'] = Catalog
```

---

## 5. PRODUCT CARD WIDGET DESIGN

### Purpose

The Product Card widget renders a single `CatalogItem` using the existing `TemplateEngine`. This allows designers to place individual product cards anywhere in their Elementor layout.

### Widget Structure

```php
namespace Catalogist\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Catalogist\Template\TemplateEngineInterface;
use Catalogist\CatalogItem\CatalogItem;

class ProductCardWidget extends Widget_Base {

    public function get_name(): string {
        return 'catalogist_product_card';
    }

    public function get_title(): string {
        return __( 'Catalog Product Card', 'catalogist' );
    }

    public function get_icon(): string {
        return 'eicon-product';
    }

    public function get_categories(): array {
        return array( 'catalogist' );
    }

    public function get_keywords(): array {
        return array( 'catalog', 'product', 'woocommerce' );
    }

    protected function register_controls(): void {
        // Product selection control
        $this->controls_manager->add_control(
            'product_id',
            array(
                'label' => __( 'Product', 'catalogist' ),
                'type' => Controls_Manager::TEXT,
                'placeholder' => 'Enter product ID',
            )
        );

        // Template selection control
        $this->add_control(
            'template',
            array(
                'label' => __( 'Template', 'catalogist' ),
                'type' => Controls_Manager::SELECT,
                'options' => array(
                    'default' => __( 'Default', 'catalogist' ),
                    'minimal' => __( 'Minimal', 'catalogist' ),
                ),
                'default' => 'default',
            )
        );

        // Display mode control
        $this->add_control(
            'display_mode',
            array(
                'label' => __( 'Display Mode', 'catalogist' ),
                'type' => Controls_Manager::SELECT,
                'options' => array(
                    'card' => __( 'Card', 'catalogist' ),
                    'list' => __( 'List', 'catalogist' ),
                    'image_only' => __( 'Image Only', 'catalogist' ),
                ),
                'default' => 'card',
            )
        );
    }

    protected function render(): void {
        $product_id = (int) $this->get_settings( 'product_id' );
        $template = $this->get_settings( 'template' );
        $display_mode = $this->get_settings( 'display_mode' );

        if ( ! $product_id ) {
            echo '<p>' . esc_html__( 'Please select a product.', 'catalogist' ) . '</p>';
            return;
        }

        // Resolve CatalogItem from product ID
        $item = $this->resolve_catalog_item( $product_id );

        if ( ! $item ) {
            echo '<p>' . esc_html__( 'Product not found.', 'catalogist' ) . '</p>';
            return;
        }

        // Build catalog context
        $catalog = $this->build_catalog_context();

        // Render using TemplateEngine
        $engine = $this->get_template_engine();
        echo $engine->renderItem( $catalog, $item, array(
            'template' => $template,
            'layout' => array(
                'display_mode' => $display_mode,
            ),
        ) );
    }

    private function resolve_catalog_item( int $product_id ): ?CatalogItem {
        // Use ProductRepositoryInterface to fetch and normalize
        $repository = catalogist()->get_container()->get( ProductRepositoryInterface::class );
        $product = $repository->find( $product_id );

        if ( ! $product ) {
            return null;
        }

        // Normalize to CatalogItem
        $factory = catalogist()->get_container()->get( CatalogItemFactory::class );
        return $factory->from_product( $product );
    }

    private function build_catalog_context(): Catalog {
        $catalog = new Catalog();
        $catalog->set_id( 0 );
        $catalog->set_title( 'Elementor Preview' );
        $catalog->set_layout_settings( array() );
        $catalog->set_print_settings( array() );
        return $catalog;
    }

    private function get_template_engine(): TemplateEngineInterface {
        return catalogist()->get_container()->get( TemplateEngineInterface::class );
    }
}
```

### Widget Controls

| Control ID | Type | Label | Default | Description |
|------------|------|-------|---------|-------------|
| `product_id` | text | Product | '' | WooCommerce product ID |
| `template` | select | Template | 'default' | Template slug to use |
| `display_mode` | select | Display Mode | 'card' | card / list / image_only |
| `columns` | number | Columns | 2 | Number of columns (1-4) |
| `show_image` | toggle | Show Image | true | Display product image |
| `show_price` | toggle | Show Price | true | Display price |
| `show_sku` | toggle | Show SKU | true | Display SKU |

---

## 6. CATALOG WIDGET DESIGN

### Purpose

The Catalog widget renders a complete catalog using the `TemplateEngine`. This is the primary widget for displaying saved catalogs in Elementor.

### Widget Structure

```php
namespace Catalogist\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Catalogist\Template\TemplateEngineInterface;
use Catalogist\Catalog\Catalog;
use Catalogist\Catalog\CatalogRepositoryInterface;

class CatalogWidget extends Widget_Base {

    public function get_name(): string {
        return 'catalogist_catalog';
    }

    public function get_title(): string {
        return __( 'WooCommerce Catalog', 'catalogist' );
    }

    public function get_icon(): string {
        return 'eicon-archive';
    }

    public function get_categories(): array {
        return array( 'catalogist' );
    }

    protected function register_controls(): void {
        // Catalog selection
        $this->add_control(
            'catalog_id',
            array(
                'label' => __( 'Catalog', 'catalogist' ),
                'type' => Controls_Manager::SELECT,
                'options' => $this->get_catalog_options(),
                'description' => __( 'Select a saved catalog to display.', 'catalogist' ),
            )
        );

        // Template override
        $this->add_control(
            'template_override',
            array(
                'label' => __( 'Template Override', 'catalogist' ),
                'type' => Controls_Manager::TEXT,
                'description' => __( 'Optional: override catalog template.', 'catalogist' ),
            )
        );

        // Display mode
        $this->add_control(
            'display_mode',
            array(
                'label' => __( 'Display Mode', 'catalogist' ),
                'type' => Controls_Manager::SELECT,
                'options' => array(
                    'default' => __( 'Default', 'catalogist' ),
                    'preview' => __( 'Preview', 'catalogist' ),
                    'print' => __( 'Print', 'catalogist' ),
                ),
                'default' => 'default',
            )
        );
    }

    protected function render(): void {
        $catalog_id = (int) $this->get_settings( 'catalog_id' );
        $template_override = sanitize_text_field( $this->get_settings( 'template_override' ) );
        $display_mode = $this->get_settings( 'display_mode' );

        if ( ! $catalog_id ) {
            echo '<p>' . esc_html__( 'Please select a catalog.', 'catalogist' ) . '</p>';
            return;
        }

        // Load catalog
        $repository = catalogist()->get_container()->get( CatalogRepositoryInterface::class );
        $catalog = $repository->find( $catalog_id );

        if ( ! $catalog ) {
            echo '<p>' . esc_html__( 'Catalog not found.', 'catalogist' ) . '</p>';
            return;
        }

        // Build settings
        $settings = array();

        if ( $template_override ) {
            $settings['template'] = $template_override;
        }

        if ( 'preview' === $display_mode ) {
            $settings['layout'] = array( 'show_header' => false, 'show_footer' => false );
        }

        // Get catalog items from processor
        $processor = catalogist()->get_container()->get( CatalogProcessorInterface::class );
        $items = $processor->process( $catalog );

        // Render using TemplateEngine
        $engine = catalogist()->get_container()->get( TemplateEngineInterface::class );
        echo $engine->renderCatalog( $catalog, $items, $settings );
    }

    private function get_catalog_options(): array {
        $repository = catalogist()->get_container()->get( CatalogRepositoryInterface::class );
        $catalogs = $repository->get_all();

        $options = array(
            '' => __( '-- Select Catalog --', 'catalogist' ),
        );

        foreach ( $catalogs as $catalog ) {
            $options[(string) $catalog->get_id()] = esc_html( $catalog->get_title() );
        }

        return $options;
    }
}
```

### Widget Controls

| Control ID | Type | Label | Default | Description |
|------------|------|-------|---------|-------------|
| `catalog_id` | select | Catalog | '' | Saved catalog to display |
| `template_override` | text | Template Override | '' | Optional template override |
| `display_mode` | select | Display Mode | 'default' | default / preview / print |

---

## 7. SERVICE PROVIDER & CONDITIONAL LOADING

### ElementorServiceProvider Design

```php
namespace Catalogist\Elementor;

use Catalogist\Core\Container;
use Catalogist\Core\ServiceProviderInterface;

class ElementorServiceProvider implements ServiceProviderInterface {

    public function register( Container $container ): void {
        // Register Elementor-specific services
        $container->set( 'elementor.dynamic_tags', array(
            'catalogist_product_name' => ProductNameDynamicTag::class,
            'catalogist_product_sku' => ProductSkDynamicTag::class,
            // ... more tags
        ) );

        $container->set( 'elementor.widgets', array(
            'catalogist_product_card' => ProductCardWidget::class,
            'catalogist_catalog' => CatalogWidget::class,
        ) );
    }

    public function boot( Container $container ): void {
        if ( ! $this->is_elementor_active() ) {
            return;
        }

        $this->register_dynamic_tags( $container );
        $this->register_widgets( $container );
    }

    private function is_elementor_active(): bool {
        return class_exists( '\Elementor\Plugin' );
    }

    private function register_dynamic_tags( Container $container ): void {
        // Hook into Elementor's dynamic tags system
        add_action(
            'elementor/dynamic_tags/register',
            function () use ( $container ) {
                $tags = $container->get( 'elementor.dynamic_tags' );

                foreach ( $tags as $tag_id => $tag_class } {
                    \ElementorPro\Modules\DynamicTags\Manager::register_tag( $tag_class );
                }
            }
        );
    }

    private function register_widgets( Container $container ): void {
        // Hook into Elementor's widget registration
        add_action(
            'elementor/widgets/register',
            function () use ( $container ) {
                $widgets = $container->get( 'elementor.widgets' );

                foreach ( $widgets as $widget_id => $widget_class ) {
                    \Elementor\Plugin::instance()->widgets_manager->register_widget_type( new $widget_class() );
                }
            }
        );
    }
}
```

### Conditional Loading in Plugin.php

```php
// In src/Core/Plugin.php
private function register_providers(): void {
    $providers = array(
        SecurityServiceProvider::class,
        AdminServiceProvider::class,
        CatalogServiceProvider::class,
        CatalogItemServiceProvider::class,
        ProductServiceProvider::class,
        VariationServiceProvider::class,
        new TemplateServiceProvider( CATALOGIST_PLUGIN_DIR ),
    );

    // Conditionally add Elementor provider
    if ( class_exists( '\Elementor\Plugin' ) ) {
        $providers[] = new ElementorServiceProvider();
    }

    // ... rest of registration
}
```

---

## 8. QR CODE DYNAMIC TAG

### Purpose

Generate a QR code image pointing to a product or variation URL.

### Implementation

```php
namespace Catalogist\Elementor\DynamicTags;

class ProductQrCodeDynamicTag extends DynamicTagBase {

    public function get_name(): string {
        return 'catalogist_product_qr_code';
    }

    public function get_title(): string {
        return __( 'QR Code', 'catalogist' );
    }

    public function render(): string {
        $url = $this->get_settings( 'product_url' );

        if ( ! $url ) {
            return '';
        }

        // Generate QR code using a lightweight library or API
        // For MVP, use a public QR code API
        $qr_api = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=' . urlencode( $url );

        return sprintf(
            '<img src="%s" alt="QR Code" class="catalogist-qr-code" />',
            esc_url( $qr_api )
        );
    }
}
```

### Design Considerations

- Use a lightweight QR code generation approach
- Cache generated QR codes to avoid repeated API calls
- Allow size configuration in widget controls
- Fallback to placeholder if QR generation fails

---

## 9. CONTEXT SHARING BETWEEN SHORTCODE AND ELEMENTOR

### Shared Context Contract

Both the shortcode and Elementor widgets must use the same `TemplateContext` structure:

```php
// Context structure used by both systems
$context = [
    // Core data (from TemplateContextBuilder)
    'catalog'     => Catalog,
    'items'       => array<CatalogItem>,
    'item'        => CatalogItem,        // For single item rendering
    'template_id' => int,
    'template_name' => string,
    'layout'      => array,
    'columns'     => int,
    'page_size'   => string,
    'orientation' => string,
    'print'       => array,
    'margins'     => array,
    'show_header' => bool,
    'show_footer' => bool,
    'template_slug' => string,

    // Escaped helpers (for convenience)
    'escaped'     => array(
        'title'       => esc_html($item->get_title()),
        'sku'         => esc_html($item->get_sku()),
        'price'       => esc_html($item->get_price()),
        'permalink'   => esc_url($item->get_permalink()),
        'stock_status'=> esc_html($item->get_stock_status()),
    ),
];
```

### How It Works

1. **Shortcode** (`[catalogist id="123"]`):
   - Calls `render_catalog($catalogId, $settings)`
   - Which calls `TemplateEngine::renderCatalog()`
   - Which uses `TemplateContextBuilder` to build context
   - Template files render the HTML

2. **Elementor Widget** (`[elementor-template id="..."]`):
   - Widget calls `TemplateEngine::renderCatalog()` or `renderItem()`
   - Same context builder, same template engine
   - Same template files, same HTML output

**Result**: Consistent output regardless of how the catalog is displayed.

---

## 10. FILE STRUCTURE

### Proposed Elementor Integration Structure

```
src/Elementor/
├── ElementorServiceProvider.php       # Conditional loading provider
├── DynamicTags/
│   ├── ProductDynamicTagBase.php      # Abstract base for product tags
│   ├── VariationDynamicTagBase.php    # Abstract base for variation tags
│   ├── ProductNameDynamicTag.php
│   ├── ProductSkuDynamicTag.php
│   ├── ProductPriceDynamicTag.php
│   ├── ProductImageDynamicTag.php
│   ├── ProductUrlDynamicTag.php
│   ├── ProductDescriptionDynamicTag.php
│   ├── ProductCategoriesDynamicTag.php
│   ├── ProductAttributesDynamicTag.php
│   ├── ProductStockStatusDynamicTag.php
│   ├── ProductQrCodeDynamicTag.php
│   ├── VariationNameDynamicTag.php
│   ├── VariationSkuDynamicTag.php
│   ├── VariationPriceDynamicTag.php
│   ├── VariationAttributesDynamicTag.php
│   ├── CatalogTitleDynamicTag.php
│   └── CatalogProductCountDynamicTag.php
├── Widgets/
│   ├── WidgetBase.php                 # Abstract base for catalog widgets
│   ├── ProductCardWidget.php          # Single product card widget
│   └── CatalogWidget.php              # Full catalog widget
└── Controls/
    ├── CatalogSelectorControl.php     # Dropdown for catalog selection
    └── DisplayModeControl.php         # Display mode selector
```

### Template Files (No Changes)

Template files remain unchanged — Elementor widgets use the same `templates/` directory.

---

## 11. EXISTING FILES TO MODIFY

### Required Modifications

| File | Change | Reason |
|------|--------|--------|
| `src/Core/Plugin.php` | Add `ElementorServiceProvider` conditionally | Load Elementor integration when active |
| `tests/bootstrap.php` | Add Elementor class mocks | Enable testing without Elementor |

### Optional Modifications

| File | Change | Reason |
|------|--------|--------|
| `composer.json` | Add `elementor/elementor` as optional dependency | Developer convenience |

**Note:** Minimal modifications to existing files to avoid breaking changes.

---

## 12. FILES INTENTIONALLY NOT MODIFIED

### Core Files (Read-Only)
```
src/Core/Container.php
src/Core/ServiceProviderInterface.php
src/Core/ModuleInterface.php
src/Core/HookableInterface.php
src/Catalog/Catalog.php
src/Catalog/CatalogRepository.php
src/Catalog/CatalogRepositoryInterface.php
src/CatalogItem/CatalogItem.php
src/CatalogItem/CatalogItemFactory.php
src/CatalogItem/CatalogProcessor.php
src/Product/ProductRepositoryInterface.php
src/Product/WooCommerceProductRepository.php
src/Template/TemplateEngine.php
src/Template/TemplateContextBuilder.php
src/Template/TemplateLoaderInterface.php
src/Template/TemplateRendererInterface.php
templates/default/*.php
```

### Reasoning
- Core classes are stable and well-tested
- Elementor integration is an additive layer
- No changes to data model or template engine
- Backward compatibility maintained

---

## 13. UNIT AND INTEGRATION TEST PLAN

### Unit Tests

**ProductDynamicTagTest:**
```php
class ProductDynamicTagTest extends TestCase {
    public function test_product_name_tag_returns_title(): void;
    public function test_product_sku_tag_returns_sku(): void;
    public function test_product_price_tag_returns_price(): void;
    public function test_product_image_tag_returns_image_url(): void;
    public function test_product_url_tag_returns_permalink(): void;
    public function test_tag_with_missing_product_returns_empty(): void;
}
```

**ProductCardWidgetTest:**
```php
class ProductCardWidgetTest extends TestCase {
    public function test_widget_renders_with_valid_product(): void;
    public function test_widget_shows_error_for_missing_product(): void;
    public function test_widget_uses_template_engine(): void;
    public function test_widget_applies_template_override(): void;
    public function test_widget_applies_display_mode(): void;
}
```

**CatalogWidgetTest:**
```php
class CatalogWidgetTest extends TestCase {
    public function test_widget_renders_with_valid_catalog(): void;
    public function test_widget_shows_error_for_missing_catalog(): void;
    public function test_widget_uses_template_engine(): void;
    public function test_widget_applies_template_override(): void;
    public function test_widget_applies_display_mode(): void;
}
```

### Integration Tests

**ElementorIntegrationTest:**
```php
class ElementorIntegrationTest extends TestCase {
    public function test_elementor_provider_loads_conditionally(): void;
    public function test_widget_context_matches_shortcode_context(): void;
    public function test_dynamic_tags_use_same_context_builder(): void;
    public function test_elementor_graceful_degradation_when_inactive(): void;
}
```

### Architecture Boundary Tests

```php
class ElementorArchitectureTest extends TestCase {
    public function test_elementor_code_not_loaded_without_elementor(): void;
    public function test_core_does_not_depend_on_elementor_classes(): void;
    public function test_widgets_use_template_engine_interface(): void;
    public function test_dynamic_tags_use_catalog_item(): void;
    public function test_no_elementor_dependencies_in_core(): void;
}
```

### Total Test Cases: ~25

---

## 14. RISKS AND MITIGATIONS

| Risk | Severity | Mitigation |
|------|----------|------------|
| Elementor API changes | Medium | Use stable Elementor Pro APIs, version check |
| Conditional loading fails | High | Test `class_exists()` checks, graceful fallback |
| Dynamic tags context mismatch | Medium | Shared `TemplateContextBuilder` contract |
| Widget rendering duplicates logic | Low | Delegation to `TemplateEngine` |
| Performance with many dynamic tags | Medium | Cache resolved values, limit API calls |
| QR code API dependency | Low | Local generation fallback, caching |
| Elementor not installed | Low | Provider skips registration, no errors |

---

## 15. RECOMMENDED IMPLEMENTATION ORDER

### Phase 1: Foundation
1. Create `ElementorServiceProvider` with conditional loading
2. Create abstract `ProductDynamicTagBase` and `VariationDynamicTagBase`
3. Register provider in `Plugin.php` conditionally

### Phase 2: Dynamic Tags (Product)
4. Implement `ProductNameDynamicTag`
5. Implement `ProductSkuDynamicTag`
6. Implement `ProductPriceDynamicTag`
7. Implement `ProductImageDynamicTag`
8. Implement `ProductUrlDynamicTag`
9. Implement `ProductDescriptionDynamicTag`
10. Implement `ProductCategoriesDynamicTag`
11. Implement `ProductAttributesDynamicTag`
12. Implement `ProductStockStatusDynamicTag`
13. Implement `ProductQrCodeDynamicTag`

### Phase 3: Dynamic Tags (Variation & Catalog)
14. Implement `VariationNameDynamicTag`
15. Implement `VariationSkuDynamicTag`
16. Implement `VariationPriceDynamicTag`
17. Implement `VariationAttributesDynamicTag`
18. Implement `CatalogTitleDynamicTag`
19. Implement `CatalogProductCountDynamicTag`

### Phase 4: Widgets
20. Create `WidgetBase` abstract class
21. Implement `ProductCardWidget`
22. Implement `CatalogWidget`
23. Add widget controls (catalog selector, display mode)

### Phase 5: Testing
24. Create unit tests for dynamic tags
25. Create unit tests for widgets
26. Create integration tests
27. Create architecture boundary tests
28. Run all tests, fix failures

### Phase 6: Documentation
29. Document dynamic tag usage
30. Document widget configuration
31. Update CLAUDE.md with Milestone 6 completion
32. Create usage examples

---

## 16. ACCEPTANCE CRITERIA

### Functional Requirements
- [ ] Elementor loads conditionally (no errors when inactive)
- [ ] Product Card widget renders using TemplateEngine
- [ ] Catalog widget renders full catalog using TemplateEngine
- [ ] Dynamic tags return correct product data
- [ ] Dynamic tags return correct variation data
- [ ] Dynamic tags return correct catalog data
- [ ] QR code dynamic tag generates valid QR image
- [ ] Widget controls allow catalog/template selection
- [ ] Display mode affects rendering
- [ ] Context is consistent between shortcode and widgets

### Security Requirements
- [ ] All widget outputs escaped
- [ ] All dynamic tag outputs escaped
- [ ] No direct database queries in Elementor layer
- [ ] No WooCommerce API leaks into Elementor
- [ ] Nonce verification for any AJAX (future)

### Performance Requirements
- [ ] Dynamic tags cache resolved values
- [ ] Widget rendering uses existing TemplateEngine (no duplication)
- [ ] No additional queries per dynamic tag
- [ ] QR code generation cached

### Code Quality Requirements
- [ ] All classes in `Catalogist\Elementor\` namespace
- [ ] All classes have proper PHPDoc
- [ ] `declare(strict_types=1)` on all files
- [ ] PSR-4 autoloading compliant
- [ ] WordPress coding standards followed
- [ ] No Elementor dependencies at plugin load time

### Testing Requirements
- [ ] Unit tests for dynamic tags (10+ test cases)
- [ ] Unit tests for widgets (10+ test cases)
- [ ] Integration tests (5+ test cases)
- [ ] Architecture boundary tests (5+ test cases)
- [ ] All existing tests still pass
- [ ] Code coverage for new code > 80%

---

## 17. OPEN ARCHITECTURAL QUESTIONS

### Question 1: Dynamic Tag Context Source
**Issue:** How should dynamic tags access `CatalogItem` data?

**Option A: Widget Context Injection**
- Widget passes `CatalogItem` to dynamic tags via Elementor's context system
- Pros: Clean separation, no extra queries
- Cons: Requires Elementor Pro context features

**Option B: Direct Repository Lookup**
- Dynamic tag queries `ProductRepositoryInterface` by ID
- Pros: Self-contained, works standalone
- Cons: Potential N+1 queries if multiple tags on same item

**Option C: Hybrid (Recommended)**
- Widget passes context when available
- Dynamic tag falls back to repository lookup when context missing
- Pros: Best of both worlds
- Cons: Slightly more complex implementation

**Recommendation:** Option C — Hybrid approach. Widget passes context; tags can resolve independently.

---

### Question 2: QR Code Generation Approach
**Issue:** How to generate QR codes without external dependencies?

**Option A: External API**
- Use `api.qrserver.com` or similar
- Pros: No local dependency, simple
- Cons: External dependency, requires internet

**Option B: Lightweight Local Library**
- Use existing QR code PHP library
- Pros: No external dependency, faster
- Cons: Adds Composer dependency

**Option C: SVG-Based (Recommended)**
- Generate simple SVG QR codes inline
- Pros: No dependencies, fast, scalable
- Cons: Limited to basic QR codes

**Recommendation:** Option C — SVG-based QR generation for MVP. Can upgrade to library later.

---

### Question 3: Widget Naming Convention
**Issue:** Should widgets use `catalogist_` prefix or follow Elementor naming?

**Option A:** `catalogist_product_card` / `catalogist_catalog`
- Pros: Consistent with text domain, clear ownership
- Cons: Slightly verbose

**Option B:** `product-card` / `woocommerce-catalog`
- Pros: Shorter, follows Elementor conventions
- Cons: Less clear about plugin ownership

**Recommendation:** Option A — `catalogist_product_card` and `catalogist_catalog` for consistency.

---

## FINAL VERDICT

## APPROVED FOR IMPLEMENTATION

The Milestone 6 Elementor Integration architecture is sound and ready for implementation.

### Key Architectural Decisions:

1. **Conditional Loading**: Elementor code loads only when `class_exists( '\Elementor\Plugin' )`

2. **Adapter Pattern**: Widgets and dynamic tags adapt Elementor APIs to our existing `TemplateEngine`

3. **Context Consistency**: Same `$context` array used by shortcode and Elementor widgets

4. **Dynamic Tags**: Product, variation, and catalog data available as Elementor dynamic tags

5. **Two Widgets**: Product Card (single item) and Catalog (full catalog)

6. **QR Code Support**: Lightweight SVG-based QR generation

7. **Minimal Core Changes**: Only `Plugin.php` modified for provider registration

### Next Steps:
1. Await user approval to proceed with implementation
2. Begin with `ElementorServiceProvider` and conditional loading
3. Implement dynamic tags (Phase 2)
4. Implement widgets (Phase 4)
5. Write comprehensive tests (Phase 5)

---

**Report Generated:** 2026-08-25
**Previous Milestone Completed:** Milestone 5 (Template Engine)
**Next Milestone:** Milestone 7 (Print Engine)
