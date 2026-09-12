# Graph Report - Catalogist  (2026-09-11)

## Corpus Check
- 76 files · ~90,131 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 1321 nodes · 1856 edges · 59 communities (43 shown, 16 thin omitted)
- Extraction: 98% EXTRACTED · 2% INFERRED · 0% AMBIGUOUS · INFERRED: 33 edges (avg confidence: 0.85)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `6dea5d27`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- ProductQueryEngine
- VariationEngine
- SortEngine
- Installer
- SelectionEngine
- CatalogCrudTest
- FilterEngineTest
- FilterEngineTest
- Catalog
- composer.json
- FilterEngine
- CatalogConfigurationTest
- Admin
- PHPUnit\Framework\TestCase
- Catalogist — Agent Instructions
- CatalogTest
- گزارش Verification — Stage X
- Template
- Catalogist — Claude Code Instructions
- Catalogist
- CatalogContext
- stage-reporter.md
- Gutenberg Blocks
- گزارش Verification — Stage 5 (Fix Pass)
- گزارش Verification — Stage 2: Product Query Engine — First Vertical Slice
- Theme Development
- PrintEngine
- Stage 3 — Variation Engine: First Vertical Slice
- Security Hardening
- Plugin Architecture
- Hooks & Filters
- Stage 3 — Variation Engine: First Vertical Slice
- گزارش Verification — Stage 1: Foundation — Catalog CPT
- گزارش Verification — Stage 8
- Stage 2 — Product Query Engine: First Vertical Slice
- گزارش Verification — Stage 9
- Workflow
- 报告 Verification — Stage 3
- گزارش Verification — Stage 4
- Renderer
- WordPress Pro
- Stage 7 — Catalog Item Engine Report
- Stage 7 — Catalog Item Engine Report
- TemplateTest
- Stage 6 — Catalog Configuration
- cli.md
- website.md
- WordPress Playground
- CatalogItemTest
- Integration/TemplateTest.php
- debugging.md

## God Nodes (most connected - your core abstractions)
1. `FilterEngine` - 90 edges
2. `Catalog` - 83 edges
3. `Template` - 67 edges
4. `ProductQueryEngine` - 60 edges
5. `CatalogCrudTest` - 59 edges
6. `ProductQueryEngineTest` - 52 edges
7. `CatalogContext` - 47 edges
8. `FilterEngineTest` - 47 edges
9. `SortEngine` - 43 edges
10. `VariationEngine` - 42 edges

## Surprising Connections (you probably didn't know these)
- `CatalogItemMapperTest` --references--> `CatalogContext`  [EXTRACTED]
  tests/Integration/CatalogItemMapperTest.php → src/CatalogContext.php
- `RendererTest` --references--> `Renderer`  [EXTRACTED]
  tests/Unit/RendererTest.php → src/Renderer.php
- `PrintEngineTest` --references--> `PrintEngine`  [EXTRACTED]
  tests/Unit/PrintEngineTest.php → src/PrintEngine.php
- `CatalogItem` --references--> `CatalogContext`  [EXTRACTED]
  src/CatalogItem.php → src/CatalogContext.php

## Import Cycles
- None detected.

## Communities (59 total, 16 thin omitted)

### Community 1 - "VariationEngine"
Cohesion: 0.06
Nodes (3): VariationEngine, VariationEngineTest, VariationEngineTest

### Community 2 - "SortEngine"
Cohesion: 0.06
Nodes (4): WC_Product, SortEngine, SortEngineTest, SortEngineTest

### Community 3 - "Installer"
Cohesion: 0.08
Nodes (21): checkParams(), checkPlatform(), displayHelp(), ErrorHandler, getHomeDir(), getIniMessage(), getOptValue(), getPlatformIssues() (+13 more)

### Community 4 - "SelectionEngine"
Cohesion: 0.07
Nodes (3): SelectionEngine, SelectionEngineTest, SelectionEngineTest

### Community 9 - "composer.json"
Cohesion: 0.09
Nodes (21): dealerdirect/phpcodesniffer-composer-installer, authors, autoload, autoload-dev, psr-4, psr-4, config, allow-plugins (+13 more)

### Community 13 - "PHPUnit\Framework\TestCase"
Cohesion: 0.05
Nodes (10): PHPUnit\Framework\Attributes\DataProvider, PHPUnit\Framework\TestCase, CatalogItemMapper, WC_Product, CatalogPostType, Plugin, CatalogItemMapperTest, WordPressBaselineTest (+2 more)

### Community 14 - "Catalogist — Agent Instructions"
Cohesion: 0.04
Nodes (45): 10. WooCommerce Rules, 11. Security, 12. Testing, 13. Definition of Implemented, 14. Regression Protection, 15. Skills, 16. Legacy Project, 17. Error Handling (+37 more)

### Community 21 - "گزارش Verification — Stage X"
Cohesion: 0.05
Nodes (43): 10. WordPress Security Verification, 11. Architecture Verification, 12. Regression Verification, 13. Previous Reports, 14. Evidence Rules, 15. Stage Gate, 16. Report, 17. Final Chat Response (+35 more)

### Community 23 - "Catalogist — Claude Code Instructions"
Cohesion: 0.05
Nodes (39): 10. WooCommerce Development, 11. Security by Default, 12. Testing, 13. Runtime Verification, 14. Error Handling, 15. Legacy Project, 16. Git Safety, 17. Git Checkpoints (+31 more)

### Community 24 - "Catalogist"
Cohesion: 0.05
Nodes (39): 1. Vertical Slice, 2. Progressive Architecture, 3. Strict Scope Isolation, 4. Evidence Over Assumption, Anti-Overengineering, Catalog, Catalog CPT, Catalog Items (+31 more)

### Community 25 - "CatalogContext"
Cohesion: 0.13
Nodes (3): CatalogContext, CatalogItem, RendererTest

### Community 26 - "stage-reporter.md"
Cohesion: 0.06
Nodes (31): 10. Test Environment, 11. Test Verification, 12. Code Quality, 13. Security Verification, 14. Architecture Verification, 15. Previous Reports, 16. Evidence Rules, 17. Stage Gate (+23 more)

### Community 27 - "Gutenberg Blocks"
Cohesion: 0.07
Nodes (28): Best Practices, Block Development Overview, block.json for Dynamic Block, block.json (WordPress 6.4+), Block Patterns, Block Registration, Block Types, Do (+20 more)

### Community 28 - "گزارش Verification — Stage 5 (Fix Pass)"
Cohesion: 0.07
Nodes (26): Architecture, Code Quality, Evidence, Final Decision, Fix A: SortEngine.php line 108 — Tuple Bug, Fix B: Fixture — Missing Price Products, Fix C: Fixture — Missing SKU Products, Fix D: Fixture — WooCommerce 11 Price Persistence (+18 more)

### Community 29 - "گزارش Verification — Stage 2: Product Query Engine — First Vertical Slice"
Cohesion: 0.08
Nodes (25): API عمومی, Architecture, Code Quality, Evidence, Final Decision, Implementation, Known Issues, Regressions (+17 more)

### Community 30 - "Theme Development"
Cohesion: 0.08
Nodes (24): Best Practices, Block Patterns, Block Template Example (templates/single.html), Block Theme Development (FSE), Block Theme Structure, Child Theme Development, Child Theme functions.php, Child Theme Structure (+16 more)

### Community 32 - "Stage 3 — Variation Engine: First Vertical Slice"
Cohesion: 0.10
Nodes (20): Architecture, Files Changed, Git, Goal, Implementation Details, Implemented, Integration Tests (Docker WordPress + WooCommerce), Known Issues (+12 more)

### Community 33 - "Security Hardening"
Cohesion: 0.10
Nodes (19): Asset Optimization, Backup Strategy, Best Practices Summary, Capability Checks, Database Cleanup, Database Query Optimization, File Upload Security, Image Optimization (+11 more)

### Community 34 - "Plugin Architecture"
Cohesion: 0.10
Nodes (19): Activation & Deactivation, Activator Class, Best Practices, Custom Post Types & Taxonomies, Deactivator Class, Do, Do Not, Full Plugin Structure (Enterprise) (+11 more)

### Community 35 - "Hooks & Filters"
Cohesion: 0.11
Nodes (18): Action Basics, Actions, Best Practices, Creating Custom Hooks, Custom Actions, Custom Filter with Default Value, Do, Do Not (+10 more)

### Community 36 - "Stage 3 — Variation Engine: First Vertical Slice"
Cohesion: 0.11
Nodes (18): Acceptance Criteria, Architecture Decision, Dependencies, Exit Criteria, Files / Areas Expected to Change, Goal, In Scope, Integration Tests (WordPress + WooCommerce runtime) (+10 more)

### Community 37 - "گزارش Verification — Stage 1: Foundation — Catalog CPT"
Cohesion: 0.11
Nodes (16): 2. Line Endings — Git CRLF/LF, 3. SKU Search Limitation, Acceptance Criteria, Architecture Review, Files Changed (مقایسه با آخرین commit), Private Constants — توضیح معماری, Scope Check, Security Review (+8 more)

### Community 38 - "گزارش Verification — Stage 8"
Cohesion: 0.11
Nodes (17): Architecture, Code Quality, Evidence, Final Decision, Implementation, Known Issues, Out of Scope (تأیید شده), Regressions (+9 more)

### Community 39 - "Stage 2 — Product Query Engine: First Vertical Slice"
Cohesion: 0.12
Nodes (16): Architecture, Files Changed, Git, Goal, Implemented, New Files, Out of Scope, Query Features (+8 more)

### Community 40 - "گزارش Verification — Stage 9"
Cohesion: 0.12
Nodes (16): Architecture, Code Quality, Evidence, Final Decision, Implementation, Out of Scope (تأیید شده), Regressions, Report Signature (+8 more)

### Community 41 - "Workflow"
Cohesion: 0.12
Nodes (15): Common Elementor WP-CLI Commands, Critical Patterns, CSS Cache, Elementor Data Format, Elementor Pro vs Free, Global Widgets, Prerequisites, Step 1: Identify the Page (+7 more)

### Community 42 - "报告 Verification — Stage 3"
Cohesion: 0.12
Nodes (15): Architecture, Code Quality, Final Decision, Git, Implementation, Known Issues, Out of Scope, Regressions (+7 more)

### Community 43 - "گزارش Verification — Stage 4"
Cohesion: 0.12
Nodes (15): Architecture, Code Quality, Evidence, Final Decision, Implementation, Known Issues, Regressions, Report Signature (+7 more)

### Community 45 - "WordPress Pro"
Cohesion: 0.13
Nodes (14): Capability Checks, Constraints, Core Workflow, Enqueuing Scripts & Styles, Key Implementation Patterns, Knowledge Reference, MUST DO, MUST NOT DO (+6 more)

### Community 46 - "Stage 7 — Catalog Item Engine Report"
Cohesion: 0.13
Nodes (14): Architecture, Design Decisions, Files Changed, Git, Goal, Implemented, New Files, Out of Scope (+6 more)

### Community 47 - "Stage 7 — Catalog Item Engine Report"
Cohesion: 0.13
Nodes (14): Architecture, Design Decisions, Files Changed, Git, Goal, Implemented, New Files, Out of Scope (+6 more)

### Community 49 - "Stage 6 — Catalog Configuration"
Cohesion: 0.15
Nodes (12): Architecture review, Files Changed (created), Git checkpoint status, Goal, Implemented (verified by code + tests), Out of Scope (confirmed), Regression, Security review (static + test-backed) (+4 more)

### Community 50 - "cli.md"
Cohesion: 0.17
Nodes (11): Advanced local server, Build a snapshot, Failure modes, Manual mounts, Playground CLI workflows, Prerequisites, Quick local development, Run a Blueprint (+3 more)

### Community 51 - "website.md"
Cohesion: 0.22
Nodes (8): Create or open a site with a URL, Decide what to do, Interact with the active WordPress site, Manage browser sites with `window.playgroundSites`, Playground website workflows, Route first, Verification, Working model for agents

### Community 52 - "WordPress Playground"
Cohesion: 0.25
Nodes (7): Escalation, Failure modes, Guardrails, Inputs required, Procedure, Verification, WordPress Playground

## Knowledge Gaps
- **511 isolated node(s):** `name`, `description`, `type`, `authors`, `phpunit/phpunit` (+506 more)
  These have ≤1 connection - possible missing edges or undocumented components.
- **16 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `TemplateTest` connect `Template` to `PHPUnit\Framework\TestCase`, `Integration/TemplateTest.php`?**
  _High betweenness centrality (0.037) - this node is a cross-community bridge._
- **Why does `FilterEngine` connect `FilterEngine` to `SortEngine`, `SelectionEngine`, `FilterEngineTest`, `FilterEngineTest`, `PHPUnit\Framework\TestCase`, `.assertSameSorted`?**
  _High betweenness centrality (0.034) - this node is a cross-community bridge._
- **Why does `ProductQueryEngineTest` connect `ProductQueryEngine` to `PHPUnit\Framework\TestCase`?**
  _High betweenness centrality (0.034) - this node is a cross-community bridge._
- **Are the 5 inferred relationships involving `Catalog` (e.g. with `.render_pipeline_config_meta_box()` and `.render_products_meta_box()`) actually correct?**
  _`Catalog` has 5 INFERRED edges - model-reasoned connections that need verification._
- **What connects `name`, `description`, `type` to the rest of the system?**
  _511 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `ProductQueryEngine` be split into smaller, more focused modules?**
  _Cohesion score 0.05268065268065268 - nodes in this community are weakly interconnected._
- **Should `VariationEngine` be split into smaller, more focused modules?**
  _Cohesion score 0.06219426974143955 - nodes in this community are weakly interconnected._