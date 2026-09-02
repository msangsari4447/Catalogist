# Catalogist

**Catalogist** is a modular WooCommerce catalog builder for WordPress, designed to generate professional, printable product catalogs with a clean separation between product data, catalog processing, templates, rendering, and output.

The project is being rebuilt from the ground up with a **progressive architecture** and **vertical-slice development approach**.

---

## Project Status

**Current Status:** Foundation / Stage 1 Preparation

The current repository represents the new Catalogist project foundation.

No major catalog features are considered implemented yet.

The project will be developed incrementally through small, independently verified **Stages**.

---

## Vision

Catalogist is intended to provide a professional catalog-building system for WooCommerce.

The target product flow is:

```text
WordPress
    ↓
Catalogist
    ↓
WooCommerce Products
    ↓
Product Query Engine
    ↓
Filter Engine
    ↓
Catalog Item Processing
    ↓
Template Engine
    ↓
Rendering / Layout
    ↓
Print / Preview
    ↓
Output
```

The final system is intended to support:

* WooCommerce product catalogs
* Product and variation handling
* Filtering and sorting
* Catalog item normalization
* Customizable templates
* Elementor integration
* A4 printing
* Print preview
* HTML output
* Future PDF output
* QR codes
* Custom fields
* RTL / LTR
* Secure WordPress-native integration

---

## Development Philosophy

Catalogist is **not** being developed using a big-bang architecture.

The project follows four core principles:

### 1. Vertical Slice

Each Stage should deliver a small but functional part of the system.

A Stage should prefer:

```text
small working feature
        ↓
test
        ↓
review
        ↓
checkpoint
```

over creating large amounts of infrastructure for future features.

### 2. Progressive Architecture

The target architecture is known, but the complete architecture is not implemented in advance.

New abstractions are introduced only when they are justified by the current Stage.

### 3. Strict Scope Isolation

Every Stage has an explicit scope.

Features outside the current Stage must not be implemented simply because they are related or technically convenient.

### 4. Evidence Over Assumption

Architecture and implementation decisions should be based on:

* Existing WordPress behavior
* WooCommerce behavior
* Existing code
* Tests
* Documentation
* Real runtime behavior
* Measurable performance where relevant

---

## Target Architecture

The intended long-term architecture is:

```text
WooCommerce
     ↓
Product Query Engine
     ↓
Filter Engine
     ↓
Catalog Item Processor
     ↓
Template Engine
     ↓
Layout / Rendering
     ↓
Print / Preview
     ↓
Output
```

Integrations such as Elementor should remain optional adapters rather than becoming dependencies of the core system.

---

## WordPress Integration

Catalogist is designed as a native WordPress plugin.

The project follows WordPress conventions for:

* Hooks
* Capabilities
* Nonces
* Sanitization
* Validation
* Escaping
* Internationalization
* Metadata
* Options
* REST/AJAX where appropriate

The plugin should avoid unnecessary custom infrastructure when WordPress already provides a suitable mechanism.

---

## WooCommerce Integration

WooCommerce is the primary product source.

Catalogist should work with:

* Simple products
* Variable products
* Product variations
* Product categories
* Product attributes
* Product metadata

WooCommerce remains a dependency of the product/catalog functionality, while the architecture should keep responsibilities separated.

---

## Catalog

The main catalog entity is planned to use a WordPress Custom Post Type:

```text
ctlg_catalog
```

Catalogs will eventually contain configuration such as:

* Product selection
* Filters
* Sorting
* Variation behavior
* Template selection
* Print settings
* Output settings

The exact implementation is introduced progressively as required by each Stage.

---

## Catalog Items

Catalog products are normalized into **Catalog Items**.

A Catalog Item provides a stable representation of what is rendered in a catalog.

It may represent:

* A WooCommerce product
* A product variation
* A selected variation configuration

The Catalog Item abstraction should not duplicate unnecessary WooCommerce data.

---

## Standard Context

The standard rendering context is:

```php
[
    'catalog'   => $catalog,
    'item'      => $catalogItem,
    'product'   => $product,
    'variation' => $variation,
]
```

Only the values relevant to a specific rendering operation need to be populated.

---

## Elementor

Elementor is an optional integration.

The core Catalogist architecture must not depend on Elementor.

The intended direction is:

```text
Catalogist Core
      ↑
Elementor Adapter
```

rather than:

```text
Catalogist Core
      ↓
Elementor
```

This keeps the core system usable independently of Elementor.

---

## Rendering and Print

Web rendering and print rendering should remain conceptually separated.

The target system should support:

* Browser preview
* A4 layouts
* Page breaks
* Print-specific styles
* Headers and footers
* Future PDF generation

The initial implementation should not introduce a hard dependency on a PDF library unless it is justified by an actual requirement.

---

## Storage

Catalogist prefers WordPress-native storage:

* Custom Post Types
* Post Meta
* Options
* WordPress object/cache APIs where appropriate

Custom database tables should only be introduced when profiling demonstrates that WordPress-native storage is insufficient.

---

## Security

Security is part of every Stage.

Implementation must consider:

* Capability checks
* Nonces
* Input validation
* Input sanitization
* Output escaping
* Safe database access
* Secure AJAX/REST endpoints
* Permission boundaries

Security is not deferred to the end of the project.

---

## Performance

The target system should be capable of handling catalogs containing approximately:

```text
500–1000 products
```

Performance principles include:

* Avoiding N+1 queries
* Avoiding unnecessary WooCommerce API calls
* Avoiding repeated metadata access
* Avoiding unnecessary object creation
* Caching only where justified
* Measuring before introducing complex optimizations

Performance optimizations should not create unnecessary architectural complexity.

---

## Testing

Testing is mandatory throughout development.

Each Stage should include appropriate tests for the functionality introduced by that Stage.

Depending on the Stage, this may include:

* Unit tests
* Integration tests
* WordPress behavior tests
* WooCommerce integration tests
* Security checks
* Regression tests

A feature is not considered complete simply because its classes, interfaces, or files exist.

---

## Stage Development Model

Catalogist development follows this workflow:

```text
Pre-Stage Analysis
        ↓
Inspect Current Code
        ↓
Inspect Relevant Skills
        ↓
Define Stage Contract
        ↓
Architecture Decision
        ↓
Minimal Implementation
        ↓
Tests
        ↓
Security Review
        ↓
Regression Check
        ↓
Architecture Review
        ↓
Stage Report
        ↓
Git Checkpoint
        ↓
STOP
```

---

## Stage Contract

Every Stage should define:

* Goal
* Scope
* Out of Scope
* Dependencies
* Architecture Decision
* Relevant Skills
* Expected Files / Areas of Change
* Acceptance Criteria
* Tests
* Security Checks
* Risks
* Exit Criteria

The Stage Contract acts as a scope lock.

---

## Stage Gate

A Stage is complete only when:

* Its goal has been achieved
* Scope has been respected
* Acceptance criteria pass
* Tests pass
* No regression is introduced
* Security has been reviewed
* Architecture has been reviewed
* No unnecessary implementation remains
* Documentation is updated where necessary
* A Git checkpoint has been created

Only after passing the Stage Gate should the next Stage begin.

---

## Anti-Overengineering

Catalogist intentionally avoids premature abstraction.

Do not create:

* Unused interfaces
* Empty service layers
* Future-only abstractions
* Speculative repositories
* Unused factories
* Placeholder subsystems
* Complete architectures for features that are not yet being implemented

A small amount of real, tested code is preferred over a large amount of theoretical infrastructure.

---

## Project Conventions

### Text Domain

```text
catalogist
```

### PHP Namespace

```php
Catalogist\
```

### Function Prefix

```text
catalogist_
```

### Catalog CPT

```text
ctlg_catalog
```

### Template CPT

```text
ctlg_template
```

---

## Internationalization

Catalogist should be prepared for internationalization from the beginning.

The primary text domain is:

```text
catalogist
```

All user-facing strings should be prepared for translation according to WordPress standards.

---

## RTL / LTR

Catalogist must support both:

* RTL
* LTR

Layouts and print output should not depend on a single text direction.

---

## Legacy Project

The previous Catalogist implementation exists in:

```text
legacy/old-project
```

It is preserved for historical reference and lessons learned.

The legacy implementation is **not** the foundation of the new project.

Code should not be copied from the legacy implementation simply to accelerate development.

The legacy project may be inspected to identify:

* Architectural mistakes
* Failed assumptions
* Implementation problems
* Missing tests
* Scope-management problems
* Useful lessons

---

## Current Development Direction

The first development Stage is intentionally small.

The initial direction is:

```text
WordPress
    ↓
Catalogist Bootstrap
    ↓
Requirements / Dependency Check
    ↓
Catalog CPT
    ↓
Create Catalog
    ↓
Save Catalog
    ↓
Load Catalog
    ↓
Tests
    ↓
Security Review
    ↓
Git Checkpoint
```

Features such as Product Query, Variation Engine, Template Engine, Elementor integration, Print Engine, Preview, and Output must not be implemented prematurely.

---

## Definition of Implemented

A feature is considered implemented only when it:

1. Exists in the appropriate code.
2. Is actually wired into the application.
3. Works at runtime.
4. Has appropriate validation/security handling.
5. Has appropriate tests.
6. Meets the Stage acceptance criteria.
7. Has survived regression checks.

A class, interface, directory, placeholder, or TODO does **not** constitute an implementation.

---

## Quality Bar

Catalogist aims to be:

* Modular
* Secure
* Testable
* Maintainable
* Performant
* WordPress-native
* WooCommerce-compatible
* Elementor-independent at the core
* RTL/LTR compatible
* Extensible without premature abstraction

The project should prioritize **correctness and controlled growth over implementation speed**.

---

## Development Specification

The detailed development rules and architectural constraints are defined in:

```text
prompt.txt
```

Agent-specific instructions are defined in:

```text
AGENTS.md
CLAUDE.md
```

Relevant development Skills should be inspected and used before implementing each Stage.

These documents complement each other:

```text
README.md
    ↓
Project overview

prompt.txt
    ↓
Master development specification

AGENTS.md
    ↓
Agent execution rules

CLAUDE.md
    ↓
Claude Code instructions
```

---

## Repository

The project repository is hosted on GitHub.

The `master` branch represents the current active development line.

The legacy implementation is preserved separately under:

```text
legacy/old-project
```

---

## License

License information will be defined as the project approaches its release stage.
