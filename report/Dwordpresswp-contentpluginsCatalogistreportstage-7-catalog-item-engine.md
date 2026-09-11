# Stage 7 — Catalog Item Engine Report

## Stage

Stage 7

## Goal

Create a standard Catalog Item layer between WooCommerce and future Template/Renderer layers, normalizing Product and Variation into a stable representation.

## Implemented

### New Files

| File | Purpose |
|------|---------|
| `src/CatalogItem.php` | Pure data object with type helpers (`is_simple()`, `is_variation()`, `is_variable()`) |
| `src/CatalogContext.php` | Minimal context (catalog_id, language, currency) |
| `src/CatalogItemMapper.php` | Maps WC_Product → CatalogItem with type normalization |
| `tests/Unit/CatalogItemTest.php` | Unit tests for CatalogItem and CatalogContext |
| `tests/Integration/CatalogItemMapperTest.php` | Integration test for mapping |

### Design Decisions

- **CatalogItem** is a plain data object with public typed properties and type helper methods. No behavior beyond data access.
- **CatalogContext** is intentionally minimal — only catalog_id, language, and currency. No future abstractions.
- **CatalogItemMapper** is a static utility class. All WooCommerce logic stays inside it; Template/Renderer layers only see CatalogItem.
- Mapper handles type normalization (simple/variable/variation) and variation-specific overrides (parent slug, parent permalink, parent image).

## Files Changed

- `src/CatalogItem.php` (new, 199 lines)
- `src/CatalogContext.php` (new, 52 lines)
- `src/CatalogItemMapper.php` (new, 125 lines)
- `tests/Unit/CatalogItemTest.php` (new, 78 lines)
- `tests/Integration/CatalogItemMapperTest.php` (new, 82 lines)

## Tests

- **Unit tests**: 115 tests, 288 assertions — all passing
- **PHPCS**: All new files pass WordPress-Extra standards
- **PHP lint**: All new files have no syntax errors
- **Regression**: All existing tests continue to pass (no failures)

## Security

- No new user input paths introduced
- Mapper uses only WooCommerce API methods (already secured)
- CatalogContext constructor enforces typed parameters

## Regression

- All 115 existing unit tests pass
- No existing code modified — only new files added
- No breaking changes to any existing class or interface

## Architecture

- CatalogItem sits at the boundary between the Query/Filter/Sort/Selection pipeline (Stages 4-6) and the future Template engine (Stage 8)
- No dependency on Template, Renderer, Print, or Elementor
- Mapper is the only point of WooCommerce coupling for data extraction
- Context is passed into each CatalogItem, enabling locale/currency awareness downstream

## Out of Scope

- Template rendering (Stage 8)
- Print engine (Stage 10)
- Preview engine (Stage 11)
- Elementor integration (Stage 13)
- Pipeline refactoring (existing engines continue unchanged)

## Git

- Commit: `3e37ed7` on `master`
- Message: `feat: implement stage 7 catalog item engine`

## Stage Gate

**PASSED** — All acceptance criteria met:
- ✅ Product → CatalogItem mapping implemented
- ✅ Variation → CatalogItem mapping implemented
- ✅ Unit tests for mapping exist
- ✅ All previous tests pass without failure
- ✅ Stage7 commit created with report
