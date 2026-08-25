# Template Engine Guide

## Overview

The Template Engine renders `CatalogItem` objects into HTML output using pluggable template files. It separates data retrieval from rendering and provides a clean architecture for template customization.

## Architecture

```
TemplateEngine (orchestrator)
    ↓
TemplateLoader (file discovery/caching)
    ↓
TemplateContextBuilder (data preparation)
    ↓
TemplateRenderer (output generation)
```

### Component Responsibilities

- **TemplateEngine**: Coordinates template loading, context building, and rendering
- **TemplateLoader**: Discovers template files with fallback chain and caching
- **TemplateContextBuilder**: Builds standardized context arrays from catalog data
- **TemplateRenderer**: Renders templates using output buffering and WordPress escaping

## Fallback Chain

Templates are resolved in this order:

1. **Theme override**: `{theme}/catalogist/{slug}/`
2. **Plugin default**: `templates/{slug}/`
3. **Built-in fallback**: `templates/fallback/catalog.php`

## Shortcode Usage

```
[catalogist id="123" template="default" columns="2"]
```

### Attributes

| Attribute | Type | Description | Default |
|-----------|------|-------------|---------|
| `id` | int | Catalog post ID | Required |
| `template` | string | Template slug | `default` |
| `columns` | int | Column count (1-4) | `2` |
| `order` | string | Sort order (ASC/DESC) | `ASC` |
| `orderby` | string | Sort field | `menu_order title` |

### Example

```
[catalogist id="456" template="luxury" columns="3"]
[catalogist id="789" template="compact" columns="1" order="DESC" orderby="price"]
```

## Programmatic API

```php
/**
 * Render a catalog using the template engine.
 *
 * @param int                          $catalogId      Catalog post ID.
 * @param array<string, mixed>|null     $settings       Override settings.
 *
 * @return string Rendered HTML output.
 */
function render_catalog( int $catalogId, ?array $settings = null ): string;
```

### Settings Override

```php
$html = render_catalog(123, [
    'template' => 'default',
    'layout' => [
        'columns' => 3,
        'show_header' => true,
        'logo_url' => 'https://example.com/logo.png',
    ],
    'print' => [
        'margins' => [
            'top' => 20,
            'right' => 20,
            'bottom' => 20,
            'left' => 20,
        ],
    ],
]);
```

## Template Context

### Main Context (`renderCatalog`)

```php
$context = [
    'catalog'         => Catalog,           // Catalog entity
    'items'           => array<CatalogItem>, // Normalized catalog items
    'template_id'     => int,               // Template post ID
    'template_name'   => string,            // Template name
    'layout'          => array,             // Normalized layout settings
    'columns'         => int,               // Column count (1-4)
    'page_size'       => string,            // Page size (A4, etc.)
    'orientation'     => string,            // portrait/landscape
    'print'           => array,             // Normalized print settings
    'margins'         => array,             // Margin settings
    'show_header'     => bool,              // Show header flag
    'show_footer'     => bool,              // Show footer flag
    'template_slug'   => string,            // Resolved template slug
];
```

### Loop Context (`buildLoopContext`)

```php
$context = [
    'item'        => CatalogItem,    // Current catalog item
    'item_index'  => int,            // Zero-based loop index
    'item_count'  => int,            // Total item count
    'is_first'    => bool,           // First item flag
    'is_last'     => bool,           // Last item flag
    'is_even'     => bool,           // Even index flag
    'is_odd'      => bool,           // Odd index flag
    'escaped'     => array,          // Pre-escaped helpers
];
```

### Escaped Helpers

```php
$context['escaped'] = [
    'title'       => esc_html($item->get_title()),
    'sku'         => esc_html($item->get_sku()),
    'price'       => esc_html($item->get_price()),
    'permalink'   => esc_url($item->get_permalink()),
    'stock_status' => esc_html($item->get_stock_status()),
];
```

## Template Structure

### Main Template (`catalog.php`)

```php
<?php
/**
 * Main catalog template.
 *
 * Context: $catalog, $items, $layout, $print, $columns, etc.
 */
?>
<div class="catalogist-catalog" data-columns="<?php echo esc_attr($columns); ?>">
    <?php if ($show_header) : ?>
        <?php echo $this->renderSection('header', $context); ?>
    <?php endif; ?>
    
    <?php if (!empty($items)) : ?>
        <?php echo $this->renderSection('product-loop', $context); ?>
    <?php else : ?>
        <p class="catalogist-empty"><?php esc_html_e('No products found.', 'catalogist'); ?></p>
    <?php endif; ?>
    
    <?php if ($show_footer) : ?>
        <?php echo $this->renderSection('footer', $context); ?>
    <?php endif; ?>
</div>
```

### Section Template (`product-card.php`)

```php
<?php
/**
 * Product card section.
 *
 * Context: $item, $index, $count, $escaped, $catalog
 */
?>
<article class="catalogist-product-card" data-item-id="<?php echo esc_attr($item->get_id()); ?>">
    <?php if ($item->get_image()) : ?>
        <a href="<?php echo esc_url($item->get_permalink()); ?>" class="catalogist-product-image">
            <img src="<?php echo esc_url($item->get_image()['src']); ?>" 
                 alt="<?php echo esc_attr($item->get_title()); ?>" />
        </a>
    <?php endif; ?>
    
    <h2 class="catalogist-product-title">
        <a href="<?php echo esc_url($item->get_permalink()); ?>">
            <?php echo esc_html($item->get_title()); ?>
        </a>
    </h2>
    
    <?php if ($item->get_sku()) : ?>
        <p class="catalogist-product-sku">SKU: <?php echo esc_html($item->get_sku()); ?></p>
    <?php endif; ?>
    
    <?php if ($item->get_price()) : ?>
        <p class="catalogist-product-price"><?php echo wc_price($item->get_price()); ?></p>
    <?php endif; ?>
    
    <?php if ($item->has_variation_table()) : ?>
        <?php echo $this->renderSection('variation-table', $context); ?>
    <?php endif; ?>
</article>
```

## Template Hooks

### Filter Hooks

- `catalogist_template_path` - Modify template path before rendering
- `catalogist_template_output` - Modify rendered HTML output

### Action Hooks

- `catalogist_before_template_render` - Called before template render
- `catalogist_after_template_render` - Called after template render

## Escaping Guidelines

### In Template Files

All output must be escaped using WordPress functions:

```php
// Use esc_html for text content
<?php echo esc_html($item->get_title()); ?>

// Use esc_attr for HTML attributes
<div class="<?php echo esc_attr($class); ?>">

// Use esc_url for URLs
<a href="<?php echo esc_url($item->get_permalink()); ?>">

// Use wp_kses_post for HTML content
<div class="description">
    <?php echo wp_kses_post($item->get_short_description()); ?>
</div>
```

### In Context

Raw data is stored in context. Escaping is applied in template files only.

## Testing

### Unit Tests

```bash
# Run all template tests
vendor/bin/phpunit --filter Template

# Run specific test class
vendor/bin/phpunit tests/Unit/Template/TemplateContextBuilderTest.php
```

### Integration Tests

```bash
# Run integration tests
vendor/bin/phpunit tests/Integration/Template/TemplateEngineTest.php
```

### Architecture Tests

```bash
# Run architecture boundary tests
vendor/bin/phpunit tests/Unit/Template/TemplateArchitectureTest.php
```

## Example: Custom Template

Create a custom template at `templates/my-template/catalog.php`:

```php
<?php
/**
 * Custom catalog template.
 */
?>
<div class="custom-catalog" data-template="my-template">
    <h1><?php echo esc_html($catalog->get_title()); ?></h1>
    
    <div class="custom-product-loop">
        <?php foreach ($items as $index => $item) : ?>
            <div class="custom-item" data-index="<?php echo esc_attr($index); ?>">
                <h2><?php echo esc_html($item->get_title()); ?></h2>
                <p><?php echo wc_price($item->get_price()); ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</div>
```

Use it with shortcode:

```
[catalogist id="123" template="my-template" columns="2"]
```

## Dependencies

The Template Engine depends on:

- `Catalog` entity (Milestone 1)
- `CatalogItem` value object (Milestone 2)
- `CatalogProcessor` (Milestone 4)
- `ProductRepositoryInterface` (Milestone 2)
- `VariationServiceInterface` (Milestone 3)

## Future Extensions

The Template Engine is designed for future extensions:

- PDF export modules (Dompdf, mPDF, Chromium)
- Print engine integration
- Elementor widget integration
- Custom template registration via CPT
