# Elementor Docker Conflict — Root Cause Report

## Root Cause

**Elementor 4.2.4 uses `require` (not `require_once`) to load element class files inside `Elements_Manager::require_files()`.**

When `do_action('init')` is called a second time, Elementor's `Plugin::init()` (registered at priority 0) runs again, which calls `init_components()`, which creates a **new** `Elements_Manager` instance. The constructor calls `require_files()`, which executes:

```php
require ELEMENTOR_PATH . 'includes/elements/column.php';
require ELEMENTOR_PATH . 'includes/elements/section.php';
require ELEMENTOR_PATH . 'includes/elements/repeater.php';
```

Because `require` (not `require_once`) is used, PHP attempts to re-declare `Elementor\Element_Column`, `Elementor\Element_Sections`, and `Elementor\Element_Repeater` — producing the fatal error.

## Evidence

### Evidence 1 — Elementor uses `require` for element files (not `require_once`)

File: `/var/www/html/wp-content/plugins/elementor/includes/managers/elements.php` lines 469–474:

```php
private function require_files() {
    require_once ELEMENTOR_PATH . 'includes/base/element-base.php';

    require ELEMENTOR_PATH . 'includes/elements/column.php';      // ← require, not require_once
    require ELEMENTOR_PATH . 'includes/elements/section.php';     // ← require, not require_once
    require ELEMENTOR_PATH . 'includes/elements/repeater.php';    // ← require, not require_once
}
```

### Evidence 2 — `Elements_Manager::__construct()` calls `require_files()`

File: `/var/www/html/wp-content/plugins/elementor/includes/managers/elements.php` lines 59–60:

```php
public function __construct() {
    $this->require_files();
}
```

### Evidence 3 — `Plugin::init_components()` creates a new `Elements_Manager` on every `init`

File: `/var/www/html/wp-content/plugins/elementor/includes/plugin.php` lines 623, 692:

```php
// In Plugin::init() at priority 0:
public function init() {
    $this->add_cpt_support();
    $this->init_components();   // ← called every time 'init' fires
    do_action('elementor/init');
}

// In Plugin::init_components():
$this->elements_manager = new Elements_Manager();  // ← new instance every time
```

### Evidence 4 — Second `do_action('init')` reproduces the fatal error deterministically

```bash
# Inside Docker container:
php -r '
require "wp-load.php";        # First init fires here → Elementor loads Element_Column
do_action("init");            # Second init → new Elements_Manager → require column.php again → FATAL
'
```

Output:
```
PHP Fatal error: Cannot redeclare class Elementor\Element_Column
(previously declared in /var/www/html/wp-content/plugins/elementor/includes/elements/column.php:19)
in /var/www/html/wp-content/plugins/elementor/includes/elements/column.php on line 19
```

Stack trace confirms the path:
```
Command line code(7): do_action('init')
/var/www/html/wp-includes/plugin.php(523): WP_Hook->do_action(Array)
/var/www/html/wp-includes/class-wp-hook.php(377): WP_Hook->apply_filters(NULL, Array)
/var/www/html/wp-includes/class-wp-hook.php(353): Elementor\Plugin->init('')
/var/www/html/wp-content/plugins/elementor/includes/plugin.php(623): Elementor\Plugin->init_components()
/var/www/html/wp-content/plugins/elementor/includes/plugin.php(692): new Elements_Manager()
/var/www/html/wp-content/plugins/elementor/includes/managers/elements.php(60): require_files()
/var/www/html/wp-content/plugins/elementor/includes/managers/elements.php(472): require()
```

### Evidence 5 — Error occurs without Catalogist

Running just `wp-load.php` + `do_action('init')` in a plain WordPress context (no Catalogist plugin) also produces the same fatal error. Catalogist is **not** involved in triggering the duplicate load.

## Reproduction

```bash
# Inside the WordPress Docker container:
cd /var/www/html/wp-content/plugins/Catalogist
./vendor/bin/phpunit --testsuite Integration \
    --bootstrap tests/Integration/bootstrap.php \
    tests/Integration/CatalogCrudTest.php
```

Or more minimally:

```bash
php -r '
require "vendor/autoload.php";
require "/var/www/html/wp-load.php";   # fires do_action("init") once
require_once "./catalogist.php";       # Catalogist registers its own init hook
do_action("init");                     # SECOND init → Elementor re-initializes → FATAL
'
```

The same error also occurs with bare WordPress (no Catalogist):

```bash
php -r '
require "/var/www/html/wp-load.php";
do_action("init");  # Second init → fatal
'
```

## Catalogist Relation

**ENVIRONMENT RELATED — CATALOGIST NOT THE CAUSE**

- The fatal error occurs with **bare WordPress + Elementor** when `do_action('init')` is called a second time.
- Catalogist's `setUpBeforeClass()` calls `do_action('init')` at line 39 of `CatalogCrudTest.php`, but this is incidental — the same fatal occurs even without Catalogist loaded.
- The root cause is entirely within Elementor 4.2.4's use of `require` instead of `require_once` in `Elements_Manager::require_files()`, combined with WordPress's `init` action being fireable multiple times in CLI/test contexts.

## Recommended Fix

### Option A — Fix Elementor (preferred, requires Elementor update or patch)

Change `require` to `require_once` in `/var/www/html/wp-content/plugins/elementor/includes/managers/elements.php` line 472–474:

```php
private function require_files() {
    require_once ELEMENTOR_PATH . 'includes/base/element-base.php';
    require_once ELEMENTOR_PATH . 'includes/elements/column.php';
    require_once ELEMENTOR_PATH . 'includes/elements/section.php';
    require_once ELEMENTOR_PATH . 'includes/elements/repeater.php';
}
```

This is a one-line-per-file fix inside Elementor's source. It should be reported as a bug to Elementor.

### Option B — Skip the redundant `do_action('init')` in the test bootstrap

In `CatalogCrudTest.php`, the `setUpBeforeClass()` method calls `do_action('init')` a second time. Since `wp-load.php` already fires `init` once, this call is redundant and causes the fatal. The test could instead check whether `init` has already fired:

```php
public static function setUpBeforeClass(): void {
    require_once dirname( __DIR__, 2 ) . '/catalogist.php';
    global $wp_actions;
    if ( empty( $wp_actions['init'] ) ) {
        do_action( 'init' );
    }
}
```

However, this is a workaround for an Elementor bug, not a fix.

### Option C — Deactivate Elementor in the test environment

If Elementor is not needed for Stage 6 integration tests, deactivate it in the WordPress container:

```bash
docker exec wordpress-wordpress-1 wp plugin deactivate elementor --path=/var/www/html
```

This avoids the conflict entirely but may break tests that depend on Elementor being present.

## Files Changed

**Files changed: NONE**

No changes were made to any files during this investigation. All commands were read-only diagnostics inside the Docker container.

## Environment Details

| Component | Version |
|-----------|---------|
| WordPress | 7.1 |
| PHP | 8.5.10 |
| Elementor | 4.2.4 |
| WooCommerce | present |
| Docker image | `wordpress:7.1.0-php8.5-apache` |
| Volume mount | `D:\wordpress` → `/var/www/html` (bind mount, rw) |
