<?php
/**
 * PrintEngine verification script.
 * Writes results to a file for reading.
 */

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/mocks/WordPressMocks.php';
require __DIR__ . '/mocks/ElementorMocks.php';
require __DIR__ . '/mocks/CatalogistMocks.php';

use Catalogist\Print\PrintEngine;
use Catalogist\Print\PrintEngineInterface;
use Catalogist\Template\TemplateEngineInterface;
use Catalogist\Catalog\Catalog;
use Catalogist\CatalogItem\CatalogItem;

$results = [];

// Mock template engine
$mock = new class implements TemplateEngineInterface {
    public function renderCatalog($c, $i, $s = null): string {
        return '<html><head></head><body><div class="catalogist-catalog">Test</div></body></html>';
    }
    public function renderSection($s, $sec, $ctx): string { return ''; }
    public function getTemplatePath($s): string { return ''; }
    public function loadTemplate($s): string { return ''; }
    public function render($s, $ctx): string { return ''; }
};

$engine = new PrintEngine($mock, 'assets/css/print.css');

// Test 1
$results[] = "1. Interface: " . ($engine instanceof PrintEngineInterface ? 'PASS' : 'FAIL');

// Test CSS
$css = $engine->generatePrintCSS([
    'page_size' => 'a4',
    'orientation' => 'portrait',
    'margins' => ['top' => 20, 'right' => 20, 'bottom' => 20, 'left' => 20],
    'columns' => 2,
]);
$results[] = "2. @page: " . (strpos($css, '@page') !== false ? 'PASS' : 'FAIL');
$results[] = "3. break-inside: " . (strpos($css, 'break-inside: avoid') !== false ? 'PASS' : 'FAIL');
$results[] = "4. margins: " . (strpos($css, 'margin: 20mm 20mm 20mm 20mm') !== false ? 'PASS' : 'FAIL');
$results[] = "5. column-count: " . (strpos($css, 'column-count:') !== false ? 'PASS' : 'FAIL');
$results[] = "6. RTL: " . (strpos($css, 'direction: rtl') !== false ? 'PASS' : 'FAIL');

// Test HTML
$catalog = new Catalog();
$catalog->set_id(1);
$catalog->set_title('Test Catalog');
$catalog->set_print_settings([
    'page_size' => 'a4',
    'orientation' => 'portrait',
    'margins' => ['top' => 20, 'right' => 20, 'bottom' => 20, 'left' => 20],
    'columns' => 2,
]);
$item = new CatalogItem(
    id: 1, type: 'product', parent_product_id: 0, title: 'Product',
    sku: 'SKU', price: '10.00', regular_price: '10.00', sale_price: '',
    description: 'Desc', short_description: 'Short', image: null,
    gallery: [], categories: [], tags: [], attributes: [], stock_status: 'instock',
    stock_quantity: 10, permalink: 'http://example.com', parent_product: null,
    variation_table: null, metadata: []
);

$html = $engine->generatePrintHTML($catalog, [$item]);
$results[] = "7. print-mode: " . (strpos($html, 'data-print-mode="true"') !== false ? 'PASS' : 'FAIL');
$results[] = "8. orientation: " . (strpos($html, 'data-orientation="portrait"') !== false ? 'PASS' : 'FAIL');
$results[] = "9. page-size: " . (strpos($html, 'data-page-size="A4"') !== false ? 'PASS' : 'FAIL');
$results[] = "10. columns: " . (strpos($html, 'data-columns="2"') !== false ? 'PASS' : 'FAIL');
$results[] = "11. style tag: " . (strpos($html, '<style') !== false ? 'PASS' : 'FAIL');
$results[] = "12. @media print: " . (strpos($html, '@media print') !== false ? 'PASS' : 'FAIL');

// Override test
$html = $engine->generatePrintHTML($catalog, [$item], ['orientation' => 'landscape', 'columns' => 4]);
$results[] = "13. override orientation: " . (strpos($html, 'data-orientation="landscape"') !== false ? 'PASS' : 'FAIL');
$results[] = "14. override columns: " . (strpos($html, 'data-columns="4"') !== false ? 'PASS' : 'FAIL');

// Architecture test
$reflection = new ReflectionClass(PrintEngine::class);
$constructor = $reflection->getConstructor();
$params = $constructor->getParameters();
$tep = $params[0] ?? null;
$results[] = "15. interface dep: " . ($tep && $tep->getType()->getName() === TemplateEngineInterface::class ? 'PASS' : 'FAIL');

// Settings normalization
$method = $reflection->getMethod('buildPrintSettings');
$method->setAccessible(true);

$settings = $method->invoke($engine, $catalog, ['columns' => 10]);
$results[] = "16. column clamp max: " . ($settings['columns'] === 4 ? 'PASS' : 'FAIL');

$settings = $method->invoke($engine, $catalog, ['columns' => 0]);
$results[] = "17. column clamp min: " . ($settings['columns'] === 1 ? 'PASS' : 'FAIL');

$settings = $method->invoke($engine, $catalog, ['orientation' => 'invalid']);
$results[] = "18. orientation fallback: " . ($settings['orientation'] === 'portrait' ? 'PASS' : 'FAIL');

$settings = $method->invoke($engine, $catalog, ['page_size' => 'letter']);
$results[] = "19. page_size uppercase: " . ($settings['page_size'] === 'LETTER' ? 'PASS' : 'FAIL');

$settings = $method->invoke($engine, $catalog, []);
$results[] = "20. margin defaults: " . ($settings['margins']['top'] === 20.0 && $settings['margins']['right'] === 20.0 ? 'PASS' : 'FAIL');

// Write results
file_put_contents(__DIR__ . '/../tests/verify_print_result.txt', implode("\n", $results) . "\n");
exit(0);
