# Stage 10 — Print Engine Report

## Stage

Stage 10 — Print Engine.

## Goal

Prepare HTML produced by the Stage 9 Renderer for professional A4 browser printing without introducing PDF generation, a second renderer, or coupling to Elementor.

## Implemented

- Added `Catalogist\\PrintEngine`, a final and stateless print preparation service.
- Added normalized print configuration with safe defaults:
  - A4 page size
  - portrait orientation
  - LTR direction
  - 10mm margins on all sides
- Added configuration validation and sanitization for page size, orientation, direction, and 0–50mm margins.
- Added a print wrapper with validated `dir`, page-size, and orientation attributes.
- Added CSS `@page` output for A4 portrait or landscape dimensions and millimetre margins.
- Added `@media print` rules for RTL/LTR direction, image sizing, color preservation, and legacy/current page-break properties.
- Prevented page breaks inside catalog cards and their immediate sections, and avoided breaks after the header or before the footer.
- Kept the service independent of WordPress queries, WooCommerce, Elementor, Preview, and Output routing. It consumes the already-rendered HTML fragment from Stage 9.

## Files Changed

- `src/PrintEngine.php`
- `tests/Unit/PrintEngineTest.php`
- `report/stage-10-print-engine.md`

## Tests

- PHP lint:
  - `php -l src/PrintEngine.php` — PASS
  - `php -l tests/Unit/PrintEngineTest.php` — PASS
- Focused unit tests in the WordPress container:
  - `./vendor/bin/phpunit --testsuite Unit --filter PrintEngineTest` — PASS (11 tests, 37 assertions)
- Full unit regression suite in the WordPress container:
  - `./vendor/bin/phpunit --testsuite Unit` — PASS (164 tests, 426 assertions)
- Integration regression suite in the WordPress container:
  - `./vendor/bin/phpunit --testsuite Integration --bootstrap tests/Integration/bootstrap.php` — PASS (244 tests, 1465 assertions)
- WordPress Coding Standards:
  - `./vendor/bin/phpcs src/PrintEngine.php tests/Unit/PrintEngineTest.php` — PASS (2 files)
- Runtime smoke test with WordPress loaded — PASS; the service produced an A4 landscape print fragment containing the catalog card markup.

The host PHPUnit bootstrap cannot run outside the project container because it requires `/var/www/html/wp-load.php`; the same tests passed in the configured WordPress runtime.

## Security

- Only allow-listed page size, orientation, and direction values are placed in markup or CSS.
- Margins must be finite numeric values between 0 and 50 millimetres and are normalized before CSS generation.
- Unknown configuration keys are discarded.
- Wrapper attributes are escaped with `esc_attr()`.
- The HTML argument is intentionally treated as trusted output from `Renderer`, which remains responsible for escaping catalog data; PrintEngine does not create a second HTML rendering path.
- No endpoint, persistence operation, capability boundary, or user-controlled CSS surface was added.
- Unit coverage verifies that CSS injection attempts in print configuration are rejected.

## Regression

All existing Unit and Integration tests passed. No existing source files were changed by the Print Engine implementation.

## Architecture

The data flow remains:

```text
Catalog data → Context / Template / Items → Renderer HTML → PrintEngine wrapper + CSS → Browser Print
```

PrintEngine is a small stateless boundary between rendered HTML and browser print behavior. It uses CSS print mechanisms rather than a PDF library and does not couple rendering to WordPress, WooCommerce, Elementor, Preview, or Output concerns.

Graphify was refreshed after the architectural change; the generated graph now contains the new PrintEngine and test relationships.

## Out of Scope

- PDF libraries and PDF generation
- Preview endpoints or UI (Stage 11)
- Output routing or downloadable output (Stage 12)
- Elementor widgets/editor integration (Stage 13)
- JavaScript print controls; browser-native print remains the Stage 10 mechanism

## Git

A Stage 10 checkpoint is created after the source, tests, and report pass review. Existing generated/unrelated working-tree changes (`graphify-out/*` and `.claude/worktrees/`) are not included.

## Stage Gate

**STAGE VERIFIED**

The Stage 10 contract is satisfied by the tested A4 print wrapper, configurable margins and orientation, RTL/LTR support, page-break controls, browser-print CSS, runtime loading, security validation, regression coverage, and documented scope boundaries.
