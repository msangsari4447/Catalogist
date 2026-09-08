# گزارش Verification — Stage 6 (Catalog Configuration)

## وضعیت نهایی

`STAGE NOT VERIFIED`

## خلاصه

Stage 6 با commit `f2ea9c5` پیاده‌سازی شده است. ۱۷ تست واحد جدید و ۴۸ تست یکپارچه جدید اضافه شده. تست‌های واحدall موفق هستند (۱۱۲ تست، ۲۶۱ assertion). Docker برای اجرای تست‌های یکپارچه در دسترس نیست. PHPCS دارای ۱ خطای non-filename در `src/Catalog.php` (خط ۳51 — کامنت translators قبل از `__()`) و ۳۲ خطا + ۵۶ هشدار در `tests/Integration/CatalogCrudTest.php` (مشکلات فرمت) است. Changes کاری برای رفع PHPCS در حال انجام است اما کامل نشده.

## محدوده بررسی

- Stage Number: 6
- Stage Name: Catalog Configuration
- هدف: تبدیل Catalog CPT به یک configuration container واقعی
- Scope: Query, Filters, Selection, Sorting, Variation behavior, Template, Layout/Print settings, Output settings
- Out of Scope: Rendering, Elementor, PDF, سیستم کامل Template
- Dependencies: Stage 1, Stage 4, Stage 5

## WordPress Skills استفاده‌شده

- catalogist-stage-verification

## Stage Contract

### منبع

`prompt.txt` lines 114–162

### Acceptance Criteria

| # | معیار | وضعیت | Evidence |
|---|---|---|---|
| 1 | Catalog قابل Create / Save / Load باشد | PASS | `Catalog::save()`, `Catalog::get_data()`, `Admin::save_meta_box_data()` |
| 2 | Configuration پایدار و versionable باشد | PASS | `CTLG_META_CONFIGURATION`, `CTLG_META_VERSION`, `CONFIG_VERSION = '1.0.0'` |
| 3 | invalid configuration fail safely کند | PASS | `Catalog::validate_configuration()`, `Catalog::sanitize_input()`, 48 integration test cases |
| 4 | Admin behavior واقعی باشد | PASS | 4 meta boxes: Settings, Pipeline Config, Status, Products |

### Exit Criteria

| معیار | وضعیت |
|---|---|
| Catalog بتواند configuration واقعی pipeline را نگه دارد | PASS |

## Implementation

### فایل‌های تغییر یافته (committed)

| فایل | تغییرات |
|---|---|
| `src/Catalog.php` | +582 خط: configuration model, versioning, validation, sanitization, backward-compatible load/save |
| `src/Admin.php` | +201 خط: 4 meta boxes, save handler, AJAX product search |
| `src/FilterEngine.php` | +9 خط: `get_allowed_filter_types()` |
| `tests/Unit/CatalogConfigurationTest.php` | 17 تست واحد جدید |
| `tests/Unit/CatalogTest.php` | 2 تست (existing, minor update) |
| `tests/Integration/CatalogCrudTest.php` | 48 تست یکپارچه جدید |

### ویژگی‌های پیاده‌سازی شده

1. **Structured Configuration Model**: `CTLG_META_CONFIGURATION` با version `1.0.0`
2. **Backward Compatibility**: fallback از `ctlg_catalog_settings` و `ctlg_catalog_products` به configuration جدید
3. **Validation**: `validate_configuration()`, `validate_filter()`, `validate_sort_config()`, `validate_selection_config()`, `validate_layout_config()`
4. **Sanitization**: `sanitize_configuration()`, `sanitize_input()` — whitelist-based برای layout, sort, selection, status
5. **Admin UI**: 4 meta box (Settings, Pipeline Configuration, Status, Products)
6. **AJAX Product Search**: `ajax_search_products()` با nonce و capability
7. **Status Management**: draft / active / archived

## Tests

### Unit Tests

| Test | Command | Result |
|---|---|---|
| All Unit | `vendor/bin/phpunit --testsuite Unit` | **PASS** (112 tests, 261 assertions) |
| Stage 6 Unit | `--filter CatalogConfigurationTest` | **PASS** (17 tests, 67 assertions) |
| Catalog Unit | `--filter CatalogTest` | **PASS** (2 tests, 19 assertions) |

### Integration Tests

| Test | Command | Result |
|---|---|---|
| Catalog CRUD | `docker compose exec wordpress ... phpunit --testsuite Integration tests/Integration/CatalogCrudTest.php` | **NOT VERIFIED** (Docker unavailable) |

تعداد تست‌های یکپارچه Stage 6: 48 تست (`tests/Integration/CatalogCrudTest.php`)
موضوعات پوشش داده شده:
- Create/Save/Load/Delete catalog
- Admin save handler (nonce, autosave, capability)
- Sanitization (empty, description, settings, columns, products, layout)
- Validation (version, status, sort key/direction, offset/limit, layout, columns, filters)
- Backward compatibility (legacy meta → new configuration)
- Configuration persistence

**تست‌های یکپارچه قابل اجرا نیستند** — Docker environment در دسترس نیست.

## Code Quality

### PHPCS (WordPress-Extra)

| فایل | وضعیت | جزئیات |
|---|---|---|
| `src/Catalog.php` | **PARTIAL** | ۱ خطای non-filename: خط 351 — `__() with placeholders` بدون translators comment مستقیم |
| `src/Admin.php` | **CLEAN** | فقط خطاهای filename (pre-existing) |
| `src/FilterEngine.php` | **CLEAN** | فقط خطاهای filename (pre-existing) |
| `tests/Unit/CatalogConfigurationTest.php` | **CLEAN** | فقط خطاهای filename + 2 warning alignment |
| `tests/Integration/CatalogCrudTest.php` | **NOT CLEAN** | 32 errors + 56 warnings (array alignment, multi-line function call formatting) |

**نکته:** خطاهای filename (`Catalog.php` vs `class-catalog.php`) یک مشکل pre-existing سراسر پروژه هستند و مربوط به Stage 6 نیستند.

### خطای PHPCS خط 351 Catalog.php

کد فعلی:
```php
// Translators: %d is the filter index number. %s is the invalid filter type string.
$errors[] = sprintf(
    __( 'Filter at index %1$d has an invalid type: "%2$s".', 'catalogist' ),
    $index,
    $filter['type']
);
```

کامنت `// Translators:` باید **مستقیماً** قبل از `__()` باشد، نه قبل از `sprintf(`. PHPCS sniff `WordPress.Security.EscapeOutput.ValidatedTranslators` این قاعده را اعمال می‌کند.

## Security

| مورد | وضعیت | Evidence |
|---|---|---|
| Nonce (save) | PASS | `wp_verify_nonce( sanitize_key( wp_unslash( $_POST[ self::NONCE_FIELD ] ) ), self::NONCE_ACTION )` |
| Nonce (AJAX) | PASS | `check_ajax_referer( 'catalogist_search_products', 'nonce' )` |
| Capability (save) | PASS | `current_user_can( self::EDIT_CAPABILITY, $post_id )` where `EDIT_CAPABILITY = 'edit_posts'` |
| Capability (AJAX) | PASS | `current_user_can( self::EDIT_CAPABILITY )` |
| Sanitization | PASS | `sanitize_text_field()`, `sanitize_key()`, `intval()`, `sanitize_textarea_field()`, whitelist validation |
| Escaping | PASS | `esc_html_e()`, `esc_attr()`, `esc_textarea()`, `esc_js_e()` |
| CSRF | PASS | Nonce on all form submissions and AJAX calls |
| Input validation | PASS | Whitelist-based validation for layout, sort keys, sort directions, statuses |

## مشکلات پیدا شده

### 1. PHPCS Error — Catalog.php Line 351 (Severity: Medium)

**محل:** `src/Catalog.php:351`
**توضیح:** تابع `__()` با placeholderها بدون کامنت `// Translators:` مستقیم قبل از آن فراخوانی شده. کامنت فعلی قبل از `sprintf(` است نه قبل از `__()`.
**Evidence:** `vendor/bin/phpcs --standard=WordPress-Extra src/Catalog.php` → `351 | ERROR | A function call to __() with texts containing placeholders was found, but was not accompanied by a "translators:" comment on the line above`
**راهکار:** جابجایی کامنت translators به خط قبل از `__()` یا استفاده از `wp_kses()` pattern مناسب.

### 2. PHPCS Errors — CatalogCrudTest.php (Severity: Low)

**محل:** `tests/Integration/CatalogCrudTest.php` — ۳۲ خطا + ۵۶ هشدار
**توضیح:** مشکلات alignment آرایه‌ها و multi-line function calls. همه fixable با PHPCBF.
**راهکار:** اجرای `vendor/bin/phpcbf tests/Integration/CatalogCrudTest.php`

### 3. Integration Tests Not Executed (Severity: Medium)

**توضیح:** Docker environment در دسترس نیست. 48 تست یکپارچه Stage 6 اجرا نشده‌اند.
**راهکار:** اجرای `docker compose up -d` و سپس `composer test:integration`

### 4. Uncommitted Working Tree Changes (Severity: Low)

**توضیح:** `src/Admin.php` و `src/Catalog.php` دارای changes کاری هستند که مربوط به رفع PHPCS می‌باشند ولی commit نشده‌اند.
**راهکار:** commit تغییرات پس از اطمینان از صحت.

## موارد خارج از Scope

- Rendering Engine (Stage 9)
- Elementor Integration (Stage 13)
- PDF Output (future)
- Complete Template System (Stage 8)
- Print Engine (Stage 10)
- Preview Engine (Stage 11)

## Git وضعیت

- Branch: `master`
- Working Tree: 2 فایل modified (`src/Admin.php`, `src/Catalog.php`) — PHPCS fixes
- Last Commit: `f2ea9c5` — `feat: implement Stage 6 Catalog Configuration`
- Commit Ahead of Origin: 1 commit

## Architecture Review

### تصمیمات معماری

1. **Structured Configuration**: استفاده از `CTLG_META_CONFIGURATION` به عنوان کلید اصلی meta به جای چندین کلید جداگانه. این رویکرد Stage 7+ را ساده‌تر می‌کند.
2. **Backward Compatibility**: `get_data()` ابتدا configuration جدید را بررسی می‌کند، سپس به legacy `ctlg_catalog_settings` fallback می‌دهد.
3. **Versioning**: `CONFIG_VERSION = '1.0.0'` در `CTLG_META_VERSION` ذخیره می‌شود — آماده برای migrationهای آتی.
4. **Separation of Concerns**: `Catalog` مسئول data/validation, `Admin` مسئول UI/save/AJAX. Core بدون وابستگی به Elementor.
5. **FilterEngine Integration**: `FilterEngine::get_allowed_filter_types()` برای validation Type-safe exposed شده.

### بررسی Coupling

- Core → FilterEngine: one-way dependency for validation (acceptable)
- Admin → Catalog: one-way (acceptable)
- No Elementor dependency in Core (PASS)
- No WooCommerce dependency in Core classes (PASS — WooCommerce only via `wc_get_product()` in Admin AJAX)

## نتیجه

Stage 6 از نظر **Implementation** و **Unit Tests** کامل و صحیح است. پیاده‌سازی شامل configuration model با versioning, validation, sanitization, backward compatibility, و admin UI می‌باشد.

**دلیل NOT VERIFIED:**
1. Integration tests (48 tests) قابل اجرا نیستند (Docker unavailable) — Requirement Stage Verification skill: "If integration tests cannot run, mark as NOT VERIFIED"
2. PHPCS line 351 error در `src/Catalog.php` unresolved است
3. PHPCS 32 errors + 56 warnings در `tests/Integration/CatalogCrudTest.php` وجود دارد

## اقدام بعدی

1. رفع PHPCS line 351 در `src/Catalog.php` (جابجایی translators comment)
2. رفع PHPCS errors در `tests/Integration/CatalogCrudTest.php` (با PHPCBF)
3. اجرای Docker و اجرای integration tests
4. Commit تغییرات working tree
5. Re-run verification پس از رفع موارد بالا

---

## Report Signature

| Field | Value |
|---|---|
| Generated By | Agent |
| Agent | Claude Code |
| Reporter | catalogist-stage-verification |
| Stage | Stage 6 |
| Report Status | Final |
| Generated At | 2026-09-07 |

**REPORT_SIGNATURE:** `catalogist-stage-verification`
.