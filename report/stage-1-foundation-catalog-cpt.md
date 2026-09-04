# گزارش Verification — Stage 1: Foundation — Catalog CPT

## وضعیت نهایی

**STAGE NOT VERIFIED**

---

## خلاصه

پیاده‌سازی Stage 1 (Catalog CPT) از نظر کد منبع و امنیت صحیح است، اما تست‌های یکپارچه (Integration Tests) دارای 3 خطا و 1 شکست هستند که مانع تأیید نهایی می‌شود.

---

## محدوده بررسی

- ثبت Custom Post Type `ctlg_catalog`
- ذخیره و بارگذاری متادیتای کاتالوگ
- رابط کاربری ادمین با متا باکس
- جستجوی AJAX محصولات ووکامرس
- امنیت (Nonce، Capability، Sanitization، Escaping)

---

## WordPress Skills استفاده‌شده

- catalogist-stage-verification
- wordpress-pro (بررسی استانداردهای امنیتی و API)

---

## Acceptance Criteria

| مورد | وضعیت | Evidence |
|---|---|---|
| CPT `ctlg_catalog` ثبت شده باشد | PASS | `src/CatalogPostType.php:9` — `register_post_type('ctlg_catalog', ...)` |
| `capability_type = post` و `map_meta_cap = true` | PASS | `src/CatalogPostType.php:21-22` |
| متادیتا ذخیره و بازیابی شود | PASS | `src/Catalog.php:128-154` — `save()` و `get_data()` |
| غیرفعال‌سازی Autosave | PASS | `src/Admin.php:411-415` — `wp_verify_nonce` |
| Nonce در ذخیره‌سازی بررسی شود | PASS | `src/Admin.php:408-409` |
| Capability بررسی شود | PASS | `src/Admin.php:419` — `current_user_can( self::EDIT_CAPABILITY, $post_id )` |
| جستجوی AJAX محصولات | PASS | `src/Admin.php:440-477` — `check_ajax_referer` + `wc_get_product` |
| Sanitization ورودی‌ها | PASS | `src/Catalog.php:96-120` — `sanitize_textarea_field`, `sanitize_text_field` |
| Escaping خروجی‌ها | PASS | `src/Admin.php` — `esc_html_e`, `esc_attr`, `esc_js` |
| تست‌ها پاس شوند | FAIL | 3 error + 1 failure در Integration Tests |

---

## Implementation

### فایل‌های اصلی

| فایل | وضعیت | توضیح |
|---|---|---|
| `src/CatalogPostType.php` | PASS | ثبت CPT با تنظیمات صحیح |
| `src/Catalog.php` | PASS | مدل داده و CRUD متا |
| `src/Admin.php` | PARTIAL | امنیت صحیح، اما constants به صورت `private` تعریف شده‌اند |
| `src/Plugin.php` | PASS | bootstrapping hooks |

### جزئیات

- **CPT**: `ctlg_catalog` با `public=false`، `show_ui=true`، `capability_type='post'`، `map_meta_cap=true`
- **Meta keys**: `ctlg_catalog_description`, `ctlg_catalog_settings`, `ctlg_catalog_products`
- **Admin**: دو متا باکس (Settings + Products) با AJAX search
- **AJAX**: action `catalogist_search_products` با nonce و capability check

---

## Tests

| Test Suite | Command | نتایج |
|---|---|---|
| PHPUnit Unit | `vendor/bin/phpunit --testsuite Unit` | **PASS** — 3 tests, 17 assertions |
| PHPUnit Integration | `docker compose exec wordpress ... phpunit --testsuite Integration` | **FAIL** — 20 tests, 62 assertions, **3 errors, 1 failure** |

### جزئیات خطاها (Errors)

```
1) CatalogCrudTest::testAdminSaveHandlerSimulation
Error: Cannot access private constant Catalogist\Admin::NONCE_FIELD
Line 187

2) CatalogCrudTest::testAdminSaveHandlerInvalidNonce
Error: Cannot access private constant Catalogist\Admin::NONCE_FIELD
Line 223

3) CatalogCrudTest::testAdminSaveHandlerAutosave
Error: Cannot access private constant Catalogist\Admin::NONCE_FIELD
Line 256
```

### جزئیات شکست (Failure)

```
1) CatalogCrudTest::testDeleteMeta
Failed asserting that '' is identical to Array &0 [].
Line 295
```

---

## Code Quality

| مورد | وضعیت | توضیح |
|---|---|---|
| PHPCS — src/ | PARTIAL | 5 error (mostly file naming and doc comments) |
| PHPCS — tests/ | FAIL | 80 errors, 28 warnings (short array syntax, line endings) |
| Short Array Syntax | FAIL | پروژه `[]` را مجاز نمی‌داند (Standard: WordPress) |
| Line Endings | FAIL | فایل‌های Windows با CRLF ذخیره شده‌اند |

---

## Security

| مورد | وضعیت | Evidence |
|---|---|---|
| Nonce/CSRF | PASS | `wp_verify_nonce` در `save_meta_box_data` (line 409)، `check_ajax_referer` در AJAX (line 441) |
| Capability | PASS | `current_user_can('edit_posts', $post_id)` (line 419, 444) |
| Sanitization | PASS | `sanitize_key`, `sanitize_text_field`, `sanitize_textarea_field`, `wp_unslash` |
| Escaping | PASS | `esc_html_e`, `esc_attr`, `esc_js` در تمام خروجی‌ها |
| SQL Safety | PASS | استفاده از `update_post_meta`, `get_post_meta` — هیچ SQL مستقیم |
| AJAX Security | PASS | `check_ajax_referer('catalogist_search_products', 'nonce')` |

---

## Architecture

| مورد | وضعیت | توضیح |
|---|---|---|
| Separation of Concerns | PASS | CatalogPostType / Catalog / Admin / Plugin جدا هستند |
| Stage Boundary | PASS | هیچ کدی مربوط به Stage بعد (Template, Query, Elementor) وجود ندارد |
| Core vs WooCommerce | PASS | وابستگی به WooCommerce فقط در `Admin::ajax_search_products` با `wc_get_product` |
| Namespace | PASS | `Catalogist\` |
| Testability | PASS | Unit tests بدون WordPress، Integration tests با WordPress |

---

## مشکلات پیدا شده

### 1. خطای تست — دسترسی به Constants خصوصی (Severity: High)

**مکان:** `src/Admin.php:18,23`
**مشکل:** Constants `NONCE_ACTION` و `NONCE_FIELD` به صورت `private` تعریف شده‌اند، اما تست‌ها (`CatalogCrudTest.php:187,223,256`) سعی در دسترسی به آنها دارند.

**راهکار:** تغییر visibility constants از `private` به `public` در `src/Admin.php`.

### 2. شکست تست — مقدار حذف‌شده متا (Severity: High)

**مکان:** `tests/Integration/CatalogCrudTest.php:295`
**مشکل:** پس از `delete_post_meta`، تابع `get_post_meta($post_id, $key, true)` مقدار `''` (رشته خالی) برمی‌گرداند، نه `[]` (آرایه خالی).

**راهکار:** اصلاح assertion در تست:
```php
// قبل (غلط):
$this->assertSame( [], get_post_meta( $post_id, Catalog::META_SETTINGS, true ) );
// بعد (درست):
$this->assertSame( '', get_post_meta( $post_id, Catalog::META_SETTINGS, true ) );
```

### 3. خطاهای PHPCS — Short Array Syntax (Severity: Medium)

**مکان:** `tests/Integration/CatalogCrudTest.php` و سایر فایل‌ها
**مشکل:** استفاده از `[]` به جای `array()` خلاف استاندارد WordPress Coding Standards.

**راهکار:** اصلاح تمام short array syntax به `array()`.

### 4. خطاهای PHPCS — Line Endings (Severity: Low)

**مکان:** چندین فایل PHP
**مشکل:** فایل‌ها با CRLF (Windows) ذخیره شده‌اند، استاندارد LF می‌خواهد.

---

## موارد خارج از Scope

- هیچ کدی مربوط به Stage بعد (Template Engine, Query Engine, Elementor) مشاهده نشد.
- گزارش قبلی Developer Agent مربوط به branch `stage-1-catalog-cpt` بود که در main repository وجود ندارد.

---

## Evidence

### Git Status
```
M  src/Plugin.php
M  tests/Integration/Bootstrap.php
M  tests/Integration/WordPressBaselineTest.php
M  tests/Unit/BaselineTest.php
?? src/Admin.php
?? src/Catalog.php
?? tests/Integration/CatalogCrudTest.php
?? tests/Unit/CatalogTest.php
```

### Unit Tests (Pass)
```
PHPUnit 13.3.2
OK (3 tests, 17 assertions)
```

### Integration Tests (Fail)
```
PHPUnit 13.3.2
Tests: 20, Assertions: 62, Errors: 3, Failures: 1, Notices: 301
```

### PHPCS (src/)
```
FOUND 5 ERRORS AFFECTING 3 LINES
```

---

## نتیجه نهایی

**STAGE NOT VERIFIED**

دلیل:
1. 3 خطای تست به دلیل دسترسی به constants خصوصی
2. 1 شکست تست به دلیل رفتار نادرست assertion
3. خطاهای PHPCS در کد (شامل short array syntax)

کد پیاده‌سازی صحیح است اما تست‌ها و code style نیاز به اصلاح دارند.

---

## اقدام بعدی

1. تغییر `NONCE_ACTION` و `NONCE_FIELD` در `src/Admin.php` از `private` به `public`
2. اصلاح assertion در `tests/Integration/CatalogCrudTest.php:295`
3. اصلاح short array syntax در تمام فایل‌ها به `array()`
4. اصلاح line endings به LF
5. اجرای مجدد تست‌ها و PHPCS
