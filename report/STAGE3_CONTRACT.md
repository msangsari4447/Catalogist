# Stage 3 — Variation Engine: First Vertical Slice

## Stage

Stage 3

## Goal

Implement a Variation Engine that provides a clean boundary between Catalogist and WooCommerce variation queries. The engine must determine whether a product has variations, return variation IDs and metadata, and integrate with the existing ProductQueryEngine from Stage 2.

## Scope

### In Scope

1. **`src/VariationEngine.php`** — New final class with static methods:
   - `get_variation_ids( int $product_id ): list<int>` — Returns variation child IDs for a variable product, empty list for simple products or invalid input
   - `expand_product_ids( array $product_ids, bool $include_variations = false ): list<int>` — Given product IDs from ProductQueryEngine, expands variable products to include their variation IDs
   - `get_variation_data( int $product_id ): array|null` — Returns structured variation metadata (ID, parent ID, formatted label) for a single variation, or null if invalid
   - `is_variable_product( int $product_id ): bool` — Checks if a product is a WooCommerce variable product
   - `resolve_product_ids( array $product_ids, string $mode = 'parent' ): list<int>` — Resolves product IDs based on variation mode ('parent' or 'variations')

2. **Integration with ProductQueryEngine** — `expand_product_ids` accepts product IDs returned by `ProductQueryEngine::query()` and expands variable products

3. **Tests**:
   - `tests/Unit/VariationEngineTest.php` — Pure PHP logic tests (class existence, method signatures, constant validation)
   - `tests/Integration/VariationEngineTest.php` — WordPress+WooCommerce runtime tests

### Out of Scope (Explicitly Excluded)

- Variation selection modes 3–5 from prompt.txt (manual selection, multiple selected, parent with table)
- Catalog Item normalization (belongs to Stage 4+)
- Template / Rendering / Print / Preview / Output
- Elementor integration
- Variation editing/creation UI
- Variation-specific query filters (e.g., filter by variation attribute)
- Caching layer for variations
- REST/AJAX endpoints for variations

## Dependencies

- Stage 2 `ProductQueryEngine` (product ID resolution)
- WooCommerce `wc_get_product()` API
- WordPress `wp_insert_post`, `wp_delete_post`, `wp_set_object_terms` (test infrastructure)

## Architecture Decision

**Decision**: Implement `VariationEngine` as a simple final class with static methods, mirroring the `ProductQueryEngine` pattern from Stage 2.

**Rationale**:
- Stage 2 established this pattern successfully (61 tests, PHPCS clean)
- No factory, registry, or service layer is needed for the current scope
- The class is a thin wrapper around WooCommerce's `WC_Product` API
- Simple code that can evolve: `get_variation_ids()` returns raw IDs, `expand_product_ids()` integrates with ProductQueryEngine, `get_variation_data()` provides richer metadata for future template consumption
- Two modes supported (`parent`, `variations`) are the minimum viable set per prompt.txt §18: "Do not build all modes simultaneously unless the current Stage requires them"

**Data flow**:
```
ProductQueryEngine::query() → list<int> product IDs
              ↓
VariationEngine::expand_product_ids() → list<int> product + variation IDs
              ↓
(future: Catalog Item normalization in Stage 4+)
```

## Relevant Skills

- `wordpress-pro` — WordPress API patterns, WooCommerce integration
- `catalogist-stage-verification` — Stage verification workflow
- `security-review` — Security review patterns

## Files / Areas Expected to Change

- `src/VariationEngine.php` — **new**
- `tests/Unit/VariationEngineTest.php` — **new**
- `tests/Integration/VariationEngineTest.php` — **new**
- `report/STAGE3_REPORT.md` — **new**

## Public API

```php
// VariationEngine.php
namespace Catalogist;

final class VariationEngine {
    /**
     * Returns variation child IDs for a variable product.
     * Empty list for simple products, invalid IDs, or non-existent products.
     */
    public static function get_variation_ids( int $product_id ): list<int>;

    /**
     * Expands product IDs: variable products are replaced by their variation IDs
     * when $include_variations is true. Simple products are preserved.
     */
    public static function expand_product_ids( array $product_ids, bool $include_variations = false ): list<int>;

    /**
     * Returns structured variation data for a single variation.
     * Null if product is invalid or not a variation child.
     */
    public static function get_variation_data( int $product_id ): ?array;

    /**
     * Checks if a product is a WooCommerce variable product.
     */
    public static function is_variable_product( int $product_id ): bool;

    /**
     * Resolves product IDs based on mode.
     * 'parent' → returns only parent product IDs (no expansion).
     * 'variations' → expands variable products to include variation IDs.
     */
    public static function resolve_product_ids( array $product_ids, string $mode = 'parent' ): list<int>;
}
```

## Acceptance Criteria

1. `get_variation_ids()` returns empty list for simple products
2. `get_variation_ids()` returns non-empty list for variable products with variations
3. `get_variation_ids()` returns empty list for non-existent product IDs
4. `expand_product_ids()` preserves simple product IDs
5. `expand_product_ids()` expands variable products to include variation IDs when flag is true
6. `expand_product_ids()` returns parent IDs when flag is false
7. `is_variable_product()` returns true for variable products, false for simple products
8. `get_variation_data()` returns structured array with id, parent_id, label for valid variations
9. `get_variation_data()` returns null for invalid inputs
10. `resolve_product_ids('parent')` = identity (no expansion)
11. `resolve_product_ids('variations')` = expanded IDs
12. All inputs are sanitized and validated (no raw user input reaches WooCommerce APIs)
13. PHPCS passes with no errors
14. All new tests pass
15. Stage 2 tests remain passing (regression)

## Tests

### Unit Tests (non-WordPress)
- Class exists and is loadable
- All public methods exist with correct signatures
- Constants exist
- No unexpected public methods

### Integration Tests (WordPress + WooCommerce runtime)
- Simple product → `get_variation_ids()` returns empty array
- Variable product with variations → `get_variation_ids()` returns variation IDs
- Non-existent product → `get_variation_ids()` returns empty array
- `is_variable_product()` for simple, variable, and non-existent products
- `expand_product_ids()` with mixed simple + variable products
- `expand_product_ids()` with flag false (no expansion)
- `get_variation_data()` for valid variation
- `get_variation_data()` for non-variation product
- `get_variation_data()` for non-existent product
- `resolve_product_ids('parent')` returns original IDs
- `resolve_product_ids('variations')` returns expanded IDs
- Edge case: variable product with no variations (empty children)
- Edge case: deleted/invalid variation ID
- Type validation: all return types correct
- Regression: Stage 2 `ProductQueryEngine` tests unchanged

## Security Checks

| Check | Detail |
|---|---|
| Input sanitization | All integer IDs cast with `intval()`, all strings validated against allow-lists |
| Output escaping | IDs returned as integers, labels use `wc_get_product()` which returns sanitized data |
| Database safety | Uses `wc_get_product()` — no raw SQL |
| Capability/nonce | N/A — query-only engine, no admin/AJAX endpoints |
|wc_get_product null check | All `wc_get_product()` results checked for null before method calls |
| No eval/dynamic code | No `eval`, `include` of user input, or dynamic code generation |
| Edge case safety | Invalid/non-existent products return safe defaults (empty array, null, false) |

## Risks

| Risk | Mitigation |
|---|---|
| `wc_get_product()` returns null for invalid IDs | Explicit null checks before all method calls |
| Variable product with no variations (edge case) | `get_children()` returns empty array, handled gracefully |
| Deleted variation still in parent's children cache | `wc_get_product()` on each variation ID; null result filtered out |
| Docker/Elementor init conflict affects integration tests | Same pre-existing issue as Stage 2; Unit tests unaffected |
| WooCommerce API changes in future versions | Uses stable `WC_Product` APIs (`get_children`, `get_type`, `get_name`) |

## Exit Criteria

- [ ] All acceptance criteria met
- [ ] Unit tests pass (100%)
- [ ] Integration tests pass (100%)
- [ ] Stage 2 regression tests pass
- [ ] PHPCS clean
- [ ] Security review clean
- [ ] Architecture review clean
- [ ] Stage report written
- [ ] Git checkpoint created
