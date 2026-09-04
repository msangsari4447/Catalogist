# Stage 3 — Variation Engine: First Vertical Slice

## Stage
Stage 3

## Goal
Implement a Variation Engine that provides a clean boundary between Catalogist and WooCommerce variation queries. The engine must determine whether a product has variations, return variation IDs and metadata, and integrate with the existing ProductQueryEngine from Stage 2.

## Implemented

### New Files
- **`src/VariationEngine.php`** (206 lines) — Final class with five public static methods and one private helper:
  - `get_variation_ids( int $product_id ): list<int>` — Returns variation child IDs for a variable product, empty list for simple products or invalid input
  - `expand_product_ids( array $product_ids, bool $include_variations = false ): list<int>` — Given product IDs from ProductQueryEngine, expands variable products to include their variation IDs
  - `get_variation_data( int $product_id ): ?array` — Returns structured variation metadata (ID, parent ID, formatted label) for a single variation, or null if invalid
  - `is_variable_product( int $product_id ): bool` — Checks if a product is a WooCommerce variable product
  - `resolve_product_ids( array $product_ids, string $mode = 'parent' ): list<int>` — Resolves product IDs based on variation mode ('parent' or 'variations')
  - `sanitize_mode( string $mode ): ?string` (private) — Allow-list validation against `['parent', 'variations']`

### Implementation Details
- **Two modes only** (`parent`, `variations`) — per prompt.txt §18: "Do not build all modes simultaneously unless the current Stage requires them"
- **WordPress function avoidance in unit tests**: Uses `max(0, (int) $x)` instead of `absint()` so unit tests run without WordPress bootstrap
- **PHPStan type**: `@phpstan-type VariationData array{id: int, parent_id: int, label: string}`
- **Input sanitization**: All integer IDs cast with `(int)`, all strings validated against allow-lists
- **Silent failure mode**: Invalid/non-existent products return safe defaults (empty array, null, false)

### Files Changed
| File | Status | Lines |
|---|---|---|
| `src/VariationEngine.php` | **New** | 206 |
| `tests/Unit/VariationEngineTest.php` | **New** | 146 |
| `tests/Integration/VariationEngineTest.php` | **New** | 612 |
| `report/STAGE3_CONTRACT.md` | **New** | 193 |

## Tests

### Unit Tests (non-WordPress, native PHP)
| Command | Result |
|---|---|
| `php vendor/bin/phpunit --testsuite Unit --filter VariationEngine` | **PASS** — 11 tests, 17 assertions, 0 failures |

### Integration Tests (Docker WordPress + WooCommerce)
| Command | Result |
|---|---|
| `docker compose exec wordpress sh -lc "... ./vendor/bin/phpunit --testsuite Integration --bootstrap tests/Integration/bootstrap.php --filter VariationEngine"` | **PASS** — 26 tests, 71 assertions, 0 failures |

### Regression Tests (Stage 2 ProductQueryEngine)
| Command | Result |
|---|---|
| `docker compose exec wordpress sh -lc "... ./vendor/bin/phpunit --testsuite Integration --bootstrap tests/Integration/bootstrap.php --filter ProductQueryEngine"` | **PASS** — 47 tests, 132 assertions, 0 failures |

### PHPCS
| Command | Result |
|---|---|
| `vendor/bin/phpcs --standard=phpcs.xml.dist src/VariationEngine.php tests/Unit/VariationEngineTest.php tests/Integration/VariationEngineTest.php` | **CLEAN** — 3/3 files passed |

### Test Coverage Summary
- **Unit**: 11 tests covering class existence, method signatures, constant validation, `sanitize_mode` via reflection, `expand_product_ids` edge cases, `resolve_product_ids` with invalid/empty input
- **Integration**: 26 tests covering `get_variation_ids`, `is_variable_product`, `get_variation_data`, `expand_product_ids`, `resolve_product_ids`, ProductQueryEngine integration, deleted variation handling, return type validation
- **Total**: 37 tests, 88 assertions across both suites

## Security
| Check | Detail |
|---|---|
| Input sanitization | All integer IDs cast with `(int)` and bounded with `max(0, ...)`; all mode strings validated against hardcoded allow-list |
| Output escaping | IDs returned as integers, labels sourced from `wc_get_product()` which returns sanitized data |
| Database safety | Uses `wc_get_product()` and `get_children()` — no raw SQL |
| Capability/nonce | N/A — query-only engine, no admin/AJAX endpoints |
| `wc_get_product` null check | All results checked for null and `is_wp_error()` before method calls |
| No eval/dynamic code | No `eval`, `include` of user input, or dynamic code generation |
| Edge case safety | Invalid/non-existent products return safe defaults (empty array, null, false) |

## Architecture
- **Single final class** with static methods — mirrors `ProductQueryEngine` pattern from Stage 2 (consistent architecture)
- **Progressive**: No speculative abstractions; thin wrapper around WooCommerce `WC_Product` API
- **Clean dependency direction**: `ProductQueryEngine` → `VariationEngine` (Stage2 feeds Stage3, not vice versa)
- **No new dependencies**: Uses only existing WooCommerce APIs (`wc_get_product`, `WC_Product_Variation`)
- **Data flow**:
  ```
  ProductQueryEngine::query() → list<int> product IDs
                ↓
  VariationEngine::expand_product_ids() → list<int> product + variation IDs
                ↓
  (future: Catalog Item normalization in Stage 4+)
  ```

## Regression
- Stage1 `CatalogCrudTest` and `WordPressBaselineTest` fail due to pre-existing Elementor 4.2.4 + WordPress 7.1 init conflict (class redeclaration on `do_action('init')`). This is an environment issue unrelated to Stage3 changes — our `VariationEngineTest` and `ProductQueryEngineTest` do not trigger this path.
- Stage2 `ProductQueryEngineTest` (47 tests) remains fully passing — no regressions.

## Known Issues
- Pre-existing Elementor 4.2.4 / WordPress 7.1 class-redeclaration bug affects `CatalogCrudTest` and `WordPressBaselineTest` only (same as Stage 2). Our Stage 3 tests are unaffected.

## Out of Scope
- Variation selection modes 3–5 from prompt.txt (manual selection, multiple selected, parent with table)
- Catalog Item normalization (Stage 4+)
- Template / Rendering / Print / Preview / Output
- Elementor integration
- Variation editing/creation UI
- Variation-specific query filters
- Caching layer for variations
- REST/AJAX endpoints for variations

## Git
Branch: `master`
Untracked files (Stage 3 deliverables):
- `src/VariationEngine.php`
- `tests/Unit/VariationEngineTest.php`
- `tests/Integration/VariationEngineTest.php`
- `report/STAGE3_CONTRACT.md`
- `report/STAGE3_REPORT.md`

## Stage Gate
**PASSED** — All 37 Stage 3 tests pass (11 unit + 26 integration), PHPCS clean, security review clean, architecture review clean, Stage 2 regression confirmed unaffected.
