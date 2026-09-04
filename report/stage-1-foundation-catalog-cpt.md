# گزارش Verification — Stage 1: Foundation — Catalog CPT

## وضعیت نهایی

**STAGE VERIFIED**

---

## خلاصه

Stage 1 (Catalog CPT) به‌طور کامل پیاده‌سازی، تست و امنیتی بازبینی شده است.
تمام تست‌ها (واحدی + یکپارچه) با موفقیت پاس می‌شوند و PHPCS هیچ خطایی گزارش نمی‌کند.

---

## محدوده بررسی

- ثبت Custom Post Type `ctlg_catalog`
- ذخیره و بارگذاری متادیتای کاتالوگ
- رابط کاربری ادمین با متا باکس
- جستجوی AJAX محصولات ووکامرس
- امنیت (Nonce، Capability， Sanitization، Escaping)

---

## Acceptance Criteria

| مورد | وضعیت | Evidence |
|---|---|---|
| CPT `ctlg_catalog` ثبت شود | PASS | `src/CatalogPostType.php:12` — `register_post_type` با `public=false`, `show_ui=true`, `capability_type='post'`, `map_meta_cap=true` |
| متا باکس‌ها ثبت شوند | PASS | `src/Admin.php:46-67` — `add_meta_boxes_{ctlg_catalog}` hook |
| ذخیره متا با nonce و capability | PASS | `src/Admin.php:421-436` — `wp_verify_nonce` + `current_user_can` + `DOING_AUTOSAVE` |
| AJAX product search | PASS | `src/Admin.php:451-490` — `check_ajax_referer` + `current_user_can` + `sanitize_text_field` |
| Sanitization داده‌ها | PASS | `src/Catalog.php:96-140` — `sanitize_textarea_field`, `intval`, `max(1,...)`, `array_map` |
| Escaping خروجی | PASS | `src/Admin.php` — `esc_html_e`, `esc_attr_e`, `esc_js_e`, `esc_textarea`, `esc_attr` |
| Unit tests پاس شوند | PASS | 3 tests, 17 assertions, 0 failures |
| Integration tests پاس شوند | PASS | 20 tests, 71 assertions, 0 failures |
| PHPCS src/ تمیز | PASS | 0 errors, 0 warnings |
| PHPCS tests/ تمیز | PASS | 0 errors, 0 warnings |
| Scope lock (بدون Stage 2) | PASS | هیچ ویژگی Stage 2 پیاده نشده |

---

## تست‌ها

| Test Suite | Command | Result |
|---|---|---|
| Unit (محلی) | `php vendor/bin/phpunit --testsuite Unit` | **PASS** — 3 tests, 17 assertions |
| Integration (Docker) | `docker exec wordpress-wordpress-1 ... phpunit --testsuite Integration --bootstrap tests/Integration/bootstrap.php` | **PASS** — 20 tests, 71 assertions, 0 failures |
| PHPCS src/ | `php vendor/bin/phpcs --standard=phpcs.xml.dist src/` | **CLEAN** — 4 files, 0 errors, 0 warnings |
| PHPCS tests/ | `php vendor/bin/phpcs --standard=phpcs.xml.dist tests/` | **CLEAN** — 5 files, 0 errors, 0 warnings |

---

## جزئیات تست‌های کلیدی Integration

| تست | نتیجه | توضیح |
|---|---|---|
| `testCreateCatalog` | PASS | CPT `ctlg_catalog` قابل ایجاد است |
| `testSaveCatalogMeta` | PASS | متادیتا ذخیره و بازیابی می‌شود |
| `testLoadCatalogDataWithDefaults` | PASS | دیتای پیش‌فرض (layout=grid, columns=3) |
| `testAdminSaveHandlerSimulation` | PASS | ذخیره از طریق handler ادمین |
| `testAdminSaveHandlerInvalidNonce` | PASS | nonce نامعتبر رد می‌شود |
| `testAdminSaveHandlerAutosave` | PASS | autosave رد می‌شود |
| `testDeleteMeta` | PASS | حذف متa مطابق رفتار WordPress (`''`) |
| `testSanitizeInput*` (9 تست) | PASS | sanitization description, settings, columns, products |

---

## Security Review

| مورد | وضعیت | Evidence |
|---|---|---|
| Nonce verification (meta box) | PASS | `src/Admin.php:421` — `wp_verify_nonce(sanitize_key(...), NONCE_ACTION)` |
| Nonce verification (AJAX) | PASS | `src/Admin.php:453` — `check_ajax_referer('catalogist_search_products', 'nonce')` |
| Capability check (meta box) | PASS | `src/Admin.php:431` — `current_user_can(self::EDIT_CAPABILITY, $post_id)` |
| Capability check (AJAX) | PASS | `src/Admin.php:456` — `current_user_can(self::EDIT_CAPABILITY)` |
| Input sanitization | PASS | `sanitize_key`, `sanitize_text_field`, `sanitize_textarea_field`, `intval` |
| Output escaping | PASS | `esc_html_e`, `esc_attr_e`, `esc_js_e`, `esc_textarea`, `esc_attr` |
| AJAX JSON response | PASS | `wp_send_json_success` / `wp_send_json_error` |
| Query sanitization | PASS | `sanitize_text_field(wp_unslash($_GET['query']))` |
| Private constants | PASS | `NONCE_ACTION` و `NONCE_FIELD` هر دو `private` باقی ماندند |

### Private Constants — توضیح معماری

`Admin::NONCE_ACTION` و `Admin::NONCE_FIELD` به‌صورت `private const` حفظ شدند.
برای تست، متد `protected static function get_nonce_config()` اضافه شد که آرایه `[action, field]` را برمی‌گرداند.
تست‌ها از `ReflectionClass` برای فراخوانی این متد استفاده می‌کنند.
این رویکرد معماری را حفظ می‌کند — constants خصوصی باقی می‌مانند.

---

## Architecture Review

| مورد | نتیجه |
|---|---|
| Abstractions اضافی | ❌ هیچ |
| Stage 2 features | ❌ پیاده نشده |
| Duplicate WP/WC behavior | ❌ از APIs اصلی استفاده شده |
| Coupling | ✅ کم — هر class مسئولیت مشخص دارد |
| Progressive architecture | ✅ مطابق CLAUDE.md |

---

## Scope Check

**PASS** — فقط Stage 1 پیاده شده:
- ✅ Catalog CPT (`ctlg_catalog`)
- ✅ Meta box settings
- ✅ Meta box product search
- ✅ AJAX product search
- ✅ Sanitization / escaping / nonce / capability
- ❌ Product Query Engine — پیاده نشده
- ❌ Template Engine — پیاده نشده
- ❌ Render / Print / Preview — پیاده نشده

---

## مشکلات و ملاحظات

### 1. PHP 8.5 Deprecation — ReflectionMethod::setAccessible()
تست‌ها از `ReflectionMethod::setAccessible(true)` استفاده می‌کنند که در PHP 8.5 deprecated است.
این یک `Deprecation` (نه `Error` یا `Failure`) است و باعث شکست تست نمی‌شود.
دلیل: دسترسی به متد `protected` از خارج namespace برای تست.
راه‌حل آینده: می‌توان `get_nonce_config` را `public` کرد (بدون تغییر visibility constants) یا یک متد test-only عمومی اضافه شد.

### 2. Line Endings — Git CRLF/LF
فایل‌های `tests/Integration/CatalogCrudTest.php` و `tests/Unit/BaselineTest.php`
در ویندوز با CRLF ذخیره می‌شوند اما در Docker (LF) اجرا می‌شوند.
Git هشدار می‌دهد: `LF will be replaced by CRLF the next time Git touches it`.
این مشکل line-ending است، نه محتوای کد.

### 3. SKU Search Limitation
AJAX handler از `WP_Query` با پارامتر `s` استفاده می‌کند.
پارامتر `s` در WordPress فقط `post_content` و `post_title` را جستجو می‌کند، نه `_sku` meta.
این یک محدودیت شناخته‌شده WordPress است، نه باگ کد.
در حال حاضر SKU در نتایج نمایش داده می‌شود (`product._sku`) اما جستجوی فعال SKU پیاده نشده.
برای Stage 1 این قابل قبول است — مرحله بعدی می‌تواند meta query برای SKU اضافه کند.

---

## Files Changed (مقایسه با آخرین commit)

| فایل | تغییرات |
|---|---|
| `src/Admin.php` | +12 line — `get_nonce_config()` method اضافه شد |
| `tests/Integration/CatalogCrudTest.php` | بازنویسی کامل — nonce config از reflection, `setUp()` با `wp_set_current_user(1)`, short array → `array()`, line endings LF |
| `tests/Unit/BaselineTest.php` | line endings → LF, newline at EOF |

فایل‌های موجود (بدون تغییر در این session):
- `src/CatalogPostType.php` — CPT registration
- `src/Catalog.php` — Data model
- `src/Plugin.php` — Bootstrap
- `catalogist.php` — Plugin entry point
- `phpcs.xml.dist` — PHPCS config
- `phpunit.xml` — PHPUnit config

---

## نتیجه نهایی

**STAGE VERIFIED**

تمام acceptace criteriaها پاس شده‌اند:
1. ✅ CPT ثبت و در runtime قابل دسترسی است
2. ✅ CRUD meta via `Catalog::save/get_data/delete_meta`
3. ✅ Admin UI با meta box و nonce/capability
4. ✅ AJAX product search با security کامل
5. ✅ تمام تست‌ها پاس می‌شوند (Unit: 3/3, Integration: 20/20)
6. ✅ PHPCS تمیز (src/ و tests/)
7. ✅ Security review بدون blocker
8. ✅ Scope lock رعایت شده
