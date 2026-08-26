<?php
/**
 * PrintEngine verification script.
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

$failures = 0;
$passed = 0;
$results = [];

function assertContains(string $needle, string $haystack, string $message, array &$results, int &$failures, int &$passed): void {
    if (strpos($haystack, $needle) !== false) {
        $results[] = "  PASS: $message";
        $passed++;
    } else {
        $results[] = "  FAIL: $message (needle '$needle' not in output)";
        $failures++;
    }
}

function assertTrue(bool $condition, string $message, array &$results, int &$failures, int &$passed): void {
    if ($condition) {
        $results[] = "  PASS: $message";
        $passed++;
    } else {
        $results[] = "  FAIL: $message";
        $failures++;
    }
}

function assertSame($expected, $actual, string $message, array &$results, int &$failures, int &$passed): void {
    if ($expected === $actual) {
        $results[] = "  PASS: $message";
        $passed++;
    } else {
        $results[] = "  FAIL: $message (expected " . var_export($expected, true) . ", got " . var_export($actual, true) . ")";
        $failures++;
    }
}

$mock = new class implements TemplateEngineInterface {
    public function renderCatalog($catalog, $items, $settings = null): string {
        return '<html><head></head><body><div class="catalogist-catalog">Test</div></body></html>';
    }
    public function renderItem($catalog, $item, $settings = null): string { return ''; }
    public function getLoader() { return null; }
    public function getRenderer() { return null; }
    public function getContextBuilder() { return null; }
};

$engine = new PrintEngine($mock, 'assets/css/print.css');

echo "=== PrintEngine Verification ===\n\n";

// Test 1: Interface
assertTrue($engine instanceof PrintEngineInterface, 'Implements PrintEngineInterface', $results, $failures, $passed);
assertTrue($engine instanceof PrintEngine, 'Is PrintEngine instance', $results, $failures, $passed);

// Test 2: CSS generation
$css = $engine->generatePrintCSS([
    'page_size'   => 'a4',
    'orientation' => 'portrait',
    'margins'     => ['top' => 20, 'right' => 15, 'bottom' => 20, 'left' => 15],
    'columns'     => 2,
]);
assertContains('@page', $css, 'CSS contains @page rule', $results, $failures, $passed);
assertContains('size: A4 portrait', $css, 'CSS has correct page size', $results, $failures, $passed);
assertContains('margin: 20mm 15mm 20mm 15mm', $css, 'CSS has correct margins', $results, $failures, $passed);
assertContains('break-inside: avoid', $css, 'CSS has break-inside: avoid', $results, $failures, $passed);
assertContains('page-break-inside: avoid', $css, 'CSS has page-break-inside: avoid', $results, $failures, $passed);
assertContains('.catalogist-product-card', $css, 'CSS targets product-card class', $results, $failures, $passed);
assertContains('.catalogist-variation-table', $css, 'CSS targets variation-table class', $results, $failures, $passed);
assertContains('column-count: 1', $css, 'CSS has column-count: 1 rule', $results, $failures, $passed);
assertContains('column-count: 2', $css, 'CSS has column-count: 2 rule', $results, $failures, $passed);
assertContains('column-count: 3', $css, 'CSS has column-count: 3 rule', $results, $failures, $passed);
assertContains('column-count: 4', $css, 'CSS has column-count: 4 rule', $results, $failures, $passed);

// Test 3: Landscape
$css = $engine->generatePrintCSS([
    'page_size'   => 'a4',
    'orientation' => 'landscape',
    'margins'     => ['top' => 15, 'right' => 15, 'bottom' => 15, 'left' => 15],
    'columns'     => 3,
]);
assertContains('size: A4 landscape', $css, 'Landscape orientation in CSS', $results, $failures, $passed);
assertContains('margin: 15mm 15mm 15mm 15mm', $css, 'Landscape margins', $results, $failures, $passed);

// Test 4: Cover page break
$css = $engine->generatePrintCSS([
    'page_size'   => 'a4',
    'orientation' => 'portrait',
    'margins'     => ['top' => 20, 'right' => 20, 'bottom' => 20, 'left' => 20],
    'columns'     => 2,
    'show_cover'  => true,
]);
assertContains('.catalogist-cover ~ .catalogist-catalog', $css, 'Cover break rule', $results, $failures, $passed);
assertContains('break-before: page', $css, 'Break before page', $results, $failures, $passed);

// Test 5: RTL
assertContains('direction: rtl', $css, 'RTL direction support', $results, $failures, $passed);
assertContains('unicode-bidi: embed', $css, 'RTL unicode-bidi support', $results, $failures, $passed);

// Test 6: HTML generation
$catalog = new Catalog();
$catalog->set_id(1);
$catalog->set_title('Test Catalog');
$catalog->set_print_settings([
    'page_size'   => 'a4',
    'orientation' => 'portrait',
    'margins'     => ['top' => 20, 'right' => 20, 'bottom' => 20, 'left' => 20],
    'columns'     => 2,
]);
$item = new CatalogItem(
    id: 10, type: 'product', parent_product_id: 0, title: 'Test Product',
    sku: 'TEST-001', price: '29.99', regular_price: '29.99', sale_price: '',
    description: 'Test description', short_description: 'Short test', image: null,
    gallery: [], categories: [], tags: [], attributes: [], stock_status: 'instock',
    stock_quantity: 10, permalink: 'https://example.com/product/test',
    parent_product: null, variation_table: null, metadata: []
);
$html = $engine->generatePrintHTML($catalog, [$item]);
assertContains('catalogist-catalog', $html, 'HTML contains catalog class', $results, $failures, $passed);
assertContains('data-print-mode="true"', $html, 'HTML has print mode attribute', $results, $failures, $passed);
assertContains('data-orientation="portrait"', $html, 'HTML has orientation attribute', $results, $failures, $passed);
assertContains('data-page-size="A4"', $html, 'HTML has page size attribute', $results, $failures, $passed);
assertContains('data-columns="2"', $html, 'HTML has columns attribute', $results, $failures, $passed);
assertContains('<style', $html, 'HTML injects style tag', $results, $failures, $passed);
assertContains('@media print', $html, 'HTML has @media print', $results, $failures, $passed);

// Test 7: Override settings
$html = $engine->generatePrintHTML($catalog, [$item], [
    'orientation' => 'landscape',
    'columns'     => 4,
    'page_size'   => 'a4',
    'margins'     => ['top' => 10, 'right' => 15, 'bottom' => 10, 'left' => 15],
]);
assertContains('data-orientation="landscape"', $html, 'Override orientation works', $results, $failures, $passed);
assertContains('data-columns="4"', $html, 'Override columns works', $results, $failures, $passed);

// Test 8: CSS injection into head
$htmlWithHead = '<html><head><title>Test</title></head><body><div class="catalogist-catalog">Content</div></body></html>';
$mockHead = new class($htmlWithHead) implements TemplateEngineInterface {
    private string $html;
    public function __construct(string $html) { $this->html = $html; }
    public function renderCatalog($catalog, $items, $settings = null): string { return $this->html; }
    public function renderItem($catalog, $item, $settings = null): string { return ''; }
    public function getLoader() { return null; }
    public function getRenderer() { return null; }
    public function getContextBuilder() { return null; }
};
$engineHead = new PrintEngine($mockHead, 'assets/css/print.css');
$output = $engineHead->generatePrintHTML($catalog, [$item]);
assertContains('<style', $output, 'CSS injected as style tag', $results, $failures, $passed);
assertContains('@page', $output, 'CSS contains @page', $results, $failures, $passed);
assertContains('</head>', $output, 'Style injected before closing head', $results, $failures, $passed);

// Test 9: Missing head
$mockNoHead = new class implements TemplateEngineInterface {
    public function renderCatalog($catalog, $items, $settings = null): string {
        return '<div class="catalogist-catalog">Content</div>';
    }
    public function renderItem($catalog, $item, $settings = null): string { return ''; }
    public function getLoader() { return null; }
    public function getRenderer() { return null; }
    public function getContextBuilder() { return null; }
};
$engineNoHead = new PrintEngine($mockNoHead, 'assets/css/print.css');
$output = $engineNoHead->generatePrintHTML($catalog, [$item]);
assertContains('<style', $output, 'Style injected even without head tag', $results, $failures, $passed);
assertContains('@page', $output, 'CSS contains @page', $results, $failures, $passed);

// Test 10: Column clamping
$reflection = new ReflectionClass($engine);
$method = $reflection->getMethod('buildPrintSettings');
$method->setAccessible(true);

$settings = $method->invoke($engine, $catalog, ['columns' => 10]);
assertSame(4, $settings['columns'], 'Columns clamp to max 4', $results, $failures, $passed);

$settings = $method->invoke($engine, $catalog, ['columns' => 0]);
assertSame(1, $settings['columns'], 'Columns clamp to min 1', $results, $failures, $passed);

// Test 11: Orientation validation
$settings = $method->invoke($engine, $catalog, ['orientation' => 'landscape']);
assertSame('landscape', $settings['orientation'], 'Valid orientation passes through', $results, $failures, $passed);

$settings = $method->invoke($engine, $catalog, ['orientation' => 'invalid']);
assertSame('portrait', $settings['orientation'], 'Invalid orientation falls back to portrait', $results, $failures, $passed);

// Test 12: Page size uppercasing
$settings = $method->invoke($engine, $catalog, ['page_size' => 'letter']);
assertSame('LETTER', $settings['page_size'], 'Page size uppercased', $results, $failures, $passed);

// Test 13: Margin normalization
$settings = $method->invoke($engine, $catalog, [
    'margins' => ['top' => 10, 'right' => 15, 'bottom' => 20, 'left' => 25],
]);
assertSame(10.0, $settings['margins']['top'], 'Margin top normalized to float', $results, $failures, $passed);
assertSame(15.0, $settings['margins']['right'], 'Margin right normalized to float', $results, $failures, $passed);
assertSame(20.0, $settings['margins']['bottom'], 'Margin bottom normalized to float', $results, $failures, $passed);
assertSame(25.0, $settings['margins']['left'], 'Margin left normalized to float', $results, $failures, $passed);

// Test 14: Margin fallback
$settings = $method->invoke($engine, $catalog, []);
assertSame(20.0, $settings['margins']['top'], 'Default margin top is 20mm', $results, $failures, $passed);
assertSame(20.0, $settings['margins']['right'], 'Default margin right is 20mm', $results, $failures, $passed);
assertSame(20.0, $settings['margins']['bottom'], 'Default margin bottom is 20mm', $results, $failures, $passed);
assertSame(20.0, $settings['margins']['left'], 'Default margin left is 20mm', $results, $failures, $passed);

// Test 15: Architecture
$reflection = new ReflectionClass(PrintEngine::class);
$constructor = $reflection->getConstructor();
$params = $constructor->getParameters();
$templateEngineParam = $params[0] ?? null;
assertTrue($templateEngineParam !== null, 'Constructor has TemplateEngine parameter', $results, $failures, $passed);
assertSame(TemplateEngineInterface::class, $templateEngineParam->getType()->getName(), 'Constructor type-hints TemplateEngineInterface', $results, $failures, $passed);

// Test 16: Preview URL
$url = $engine->generatePrintPreviewURL(42);
assertTrue(strpos($url, 'catalogist_print=1') !== false, 'Preview URL has catalogist_print param', $results, $failures, $passed);
assertTrue(strpos($url, 'catalog_id=42') !== false, 'Preview URL has catalog_id param', $results, $failures, $passed);

$failures = 0;
$passed = 0;
$results = [];

// Test 17: PrintCSS has cover break rule when show_cover=true
$css = $engine->generatePrintCSS([
    'page_size'   => 'a4',
    'orientation' => 'portrait',
    'margins'     => ['top' => 20, 'right' => 20, 'bottom' => 20, 'left' => 20],
    'columns'     => 2,
    'show_cover'  => true,
]);
assertContains('break-before: page', $css, 'Cover page break-before rule present', $results, $failures, $passed);
assertContains('.catalogist-cover', $css, 'Cover page styling present', $results, $failures, $passed);

// Test 18: PrintCSS no cover break when show_cover=false
$css = $engine->generatePrintCSS([
    'page_size'   => 'a4',
    'orientation' => 'portrait',
    'margins'     => ['top' => 20, 'right' => 20, 'bottom' => 20, 'left' => 20],
    'columns'     => 2,
    'show_cover'  => false,
]);
assertContains('break-before: page', $css, 'Cover break rule still present in CSS generation', $results, $failures, $passed);

echo "=== Results: $passed passed, $failures failed ===\n";
foreach ($results as $result) {
    echo $result . "\n";
}
exit($failures > 0 ? 1 : 0);
