# Catalogist

A professional WordPress plugin for building printable WooCommerce product catalogs.

## Features

- **Product Query Engine** – Flexible querying of WooCommerce products with filters, sorting, and pagination
- **Variation Engine** – Support for 5 variation modes: parent only, all variations, selected variations, multiple variations, and variation tables
- **Catalog Processor** – Normalize products and variations into unified catalog items with structured data
- **Template Engine** – Pluggable template system with theme override support, shortcodes, and helper functions
- **Elementor Integration** – Dynamic tags, widgets, and global helpers for visual catalog building
- **Print Engine** – Print-optimized CSS with @media print, page break controls, and configurable layouts (1-4 columns)
- **Preview Engine** – Live preview with print-accurate rendering
- **Output Engine** – HTML export, print dialog integration, and extensible architecture for future PDF modules

## Requirements

- WordPress 6.0+
- WooCommerce 7.0+
- PHP 8.1+
- Composer (for development)

## Installation

### Via Composer (Development)

```bash
composer install
```

### As WordPress Plugin

1. Download the latest release
2. Upload to `/wp-content/plugins/catalogist`
3. Activate through the WordPress Plugins menu
4. Ensure WooCommerce is installed and activated

## Quick Start

1. Go to **Catalogist → Add New Catalog**
2. Configure your product query (categories, tags, attributes, custom filters)
3. Select variation mode (parent, all, selected, multiple, table)
4. Choose a template or create a custom one
5. Configure print settings (page size, orientation, margins, columns)
6. Save and preview
7. Use the shortcode `[catalogist id="123"]` or the Elementor Catalog widget to display

## Shortcodes

```php
// Basic usage
[catalogist id="123"]

// With template override
[catalogist id="123" template="grid"]

// With column override
[catalogist id="123" columns="3"]
```

```php
// Helper function in templates
echo render_catalog(123, ['template' => 'grid', 'columns' => 3]);
```

## Elementor Integration

When Elementor is active, Catalogist provides:

- **16 Dynamic Tags**: Product name, image, SKU, price, description, categories, attributes, stock, URL, QR code, ID + variation name, SKU, price, attributes
- **2 Widgets**: Product Card, WooCommerce Catalog
- **Global Helper**: `catalogist_get_catalog_item($product_id, $parent_product_id)`

## Template System

Templates follow a fallback chain:

1. Theme override: `your-theme/catalogist/catalog.php`
2. Plugin default: `templates/catalog.php`
3. Built-in fallback

Default templates included:
- `catalog.php` – Main wrapper
- `header.php` / `footer.php` – Page header/footer
- `product-loop.php` – Product iteration
- `product-card.php` – Individual product card
- `variation-table.php` – Variation table for table mode

## Architecture

```
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

Elementor is an integration layer, not the core architecture. The plugin core loads without Elementor active.

## Development

```bash
# Install dependencies
composer install

# Regenerate autoloader
composer dump-autoload

# Run tests
composer test
vendor/bin/phpunit

# Code style check
composer lint
vendor/bin/phpcs

# Fix code style
composer format
vendor/bin/phpcbf
```

## Project Structure

```
catalogist.php              # Main plugin file
composer.json               # Composer configuration
uninstall.php               # Uninstall handler
src/
  Core/                     # Core bootstrap, container, providers
  Admin/                    # Admin menus, settings, pages
  Catalog/                  # Catalog CPT, factory, processor
  CatalogItem/              # CatalogItem value object, factory
  Elementor/                # Elementor integration (conditional)
  Output/                   # Output engines (HTML, Print, PDF future)
  Preview/                  # Preview engine
  Print/                    # Print layout engine
  Product/                  # Product query engine, repository
  Template/                 # Template engine, loaders, renderers
  Variation/                # Variation engine, repository, service
assets/
  css/                      # Admin and frontend styles
  js/                       # Admin and frontend scripts
templates/                  # Default template files
languages/                  # Translation files
tests/                      # Unit and integration tests
```

## Security

- All input validated and sanitized
- All output escaped
- Nonces for admin actions
- Capability checks for privileged operations
- No direct SQL queries (uses WP/WC APIs)

## Compatibility

- WordPress 6.0+
- WooCommerce 7.0+
- Elementor 3.10+ (optional)
- PHP 8.1+
- RTL compatible
- Translation ready (text domain: `catalogist`)

## License

GPL v2 or later

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines.

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for release history.