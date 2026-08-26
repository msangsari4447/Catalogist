<?php
require 'D:/wordpress/Catalogist/vendor/autoload.php';
require 'D:/wordpress/Catalogist/tests/mocks/WordPressMocks.php';
require 'D:/wordpress/Catalogist/tests/mocks/ElementorMocks.php';
require 'D:/wordpress/Catalogist/tests/mocks/CatalogistMocks.php';

use Catalogist\Print\PrintEngine;
use Catalogist\Print\PrintEngineInterface;
use Catalogist\Template\TemplateEngineInterface;
use Catalogist\Catalog\Catalog;
use Catalogist\CatalogItem\CatalogItem;

$results = [];

$mock = new class implements TemplateEngineInterface {
    public function renderCatalog($c, $i, $s = null): string {
        return '<html><head></head><body><div class="catalogist-catalog">Test</div></body></html>';
    }
    public function renderItem($c, $item, $s = null): string { return ''; }
    public function getLoader() { return null; }
    public function getRenderer() { return null; }
    public function getContextBuilder() { return null; }
};

$engine = new PrintEngine($mock, 'assets/css/print.css');
$results[] = '1. Interface: ' . ($engine instanceof PrintEngineInterface ? 'PASS' : 'FAIL');

$css = $engine->generatePrintCSS([
    'page_size'   => 'a4',
    'orientation' => 'portrait',
    'margins'     => ['top' => 20, 'right' => 20, 'bottom' => 20, 'left' => 20],
    'columns'     => 2,
]);
$results[] = '2. @page: ' . (strpos($css, '@page') !== false ? 'PASS' : 'FAIL');
$results[] = '3. break: ' . (strpos($css, 'break-inside: avoid') !== false ? 'PASS' : 'FAIL');
$results[] = '4. margin: ' . (strpos($css, 'margin: 20mm 20mm 20mm 20mm') !== false ? 'PASS' : 'FAIL');
$results[] = '5. cols: ' . (strpos($css, 'column-count:') !== false ? 'PASS' : 'FAIL');
$results[] = '6. RTL: ' . (strpos($css, 'direction: rtl') !== false ? 'PASS' : 'FAIL');

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
    description: 'Test', short_description: 'Short', image: null,
    gallery: [], categories: [], tags: [], attributes: [], stock_status: 'instock',
    stock_quantity: 10, permalink: 'https://example.com/product/test',
    parent_product: null, variation_table: null, metadata: []
);

$html = $engine->generatePrintHTML($catalog, [$item]);
$results[] = '7. print-mode: ' . (strpos($html, 'data-print-mode="true"') !== false ? 'PASS' : 'FAIL');
$results[] = '8. orient: ' . (strpos($html, 'data-orientation="portrait"') !== false ? 'PASS' : 'FAIL');
$results[] = '9. page-size: ' . (strpos($html, 'data-page-size="A4"') !== false ? 'PASS' : 'FAIL');
$results[] = '10. cols: ' . (strpos($html, 'data-columns="2"') !== false ? 'PASS' : 'FAIL');
$results[] = '11. style: ' . (strpos($html, '<style') !== false ? 'PASS' : 'FAIL');
$results[] = '12. media: ' . (strpos($html, '@media print') !== false ? 'PASS' : 'FAIL');

// Override
$html = $engine->generatePrintHTML($catalog, [$item], ['orientation' => 'landscape', 'columns' => 4]);
$results[] = '13. o-orient: ' . (strpos($html, 'data-orientation="landscape"') !== false ? 'PASS' : 'FAIL');
$results[] = '14. o-cols: ' . (strpos($html, 'data-columns="4"') !== false ? 'PASS' : 'FAIL');

// Architecture
$r = new ReflectionClass(PrintEngine::class);
$ctor = $r->getConstructor();
$param = $ctor->getParameters()[0] ?? null;
$results[] = '15. dep: ' . ($param && $param->getType()->getName() === TemplateEngineInterface::class ? 'PASS' : 'FAIL');

// Settings
$m = $r->getMethod('buildPrintSettings');
$m->setAccessible(true);
$s = $m->invoke($engine, $catalog, ['columns' => 10]);
$results[] = '16. clamp-max: ' . ($s['columns'] === 4 ? 'PASS' : 'FAIL');
$s = $m->invoke($engine, $catalog, ['columns' => 0]);
$results[] = '17. clamp-min: ' . ($s['columns'] === 1 ? 'PASS' : 'FAIL');
$s = $m->invoke($engine, $catalog, ['orientation' => 'invalid']);
$results[] = '18. orient-fb: ' . ($s['orientation'] === 'portrait' ? 'PASS' : 'FAIL');
$s = $m->invoke($engine, $catalog, ['page_size' => 'letter']);
$results[] = '19. size-up: ' . ($s['page_size'] === 'LETTER' ? 'PASS' : 'FAIL');
$s = $m->invoke($engine, $catalog, []);
$results[] = '20. margin-def: ' . ($s['margins']['top'] === 20.0 ? 'PASS' : 'FAIL');

// Head injection
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
$results[] = '21. inject-head: ' . (strpos($output, '<style') !== false && strpos($output, '</head>') !== false ? 'PASS' : 'FAIL');

// No head
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
$results[] = '22. no-head: ' . (strpos($output, '<style') !== false ? 'PASS' : 'FAIL');

// Preview URL
$url = $engine->generatePrintPreviewURL(42);
$results[] = '23. preview: ' . (strpos($url, 'catalogist_print=1') !== false && strpos($url, 'catalog_id=42') !== false ? 'PASS' : 'FAIL');

file_put_contents('D:/wordpress/Catalogist/tests/verify_result.txt', implode(PHP_EOL, $results));
