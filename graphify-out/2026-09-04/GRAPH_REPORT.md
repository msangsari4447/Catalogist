# Graph Report - Catalogist  (2026-09-04)

## Corpus Check
- 41 files · ~47,802 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 679 nodes · 777 edges · 31 communities (28 shown, 3 thin omitted)
- Extraction: 99% EXTRACTED · 1% INFERRED · 0% AMBIGUOUS · INFERRED: 5 edges (avg confidence: 0.85)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `f2fab8bb`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- Catalog
- ProductQueryEngine
- Catalogist — Agent Instructions
- Catalogist — Claude Code Instructions
- VariationEngine
- composer.json
- Catalogist
- stage-reporter.md
- Gutenberg Blocks
- گزارش Verification — Stage 2: Product Query Engine — First Vertical Slice
- catalogist-stage-verification/SKILL.md
- Theme Development
- Security Hardening
- Plugin Architecture
- گزارش Verification — Stage X
- Hooks & Filters
- Stage 3 — Variation Engine: First Vertical Slice
- گزارش Verification — Stage 1: Foundation — Catalog CPT
- Stage 2 — Product Query Engine: First Vertical Slice
- Workflow
- WordPress Pro
- ProductQueryEngineTest
- cli.md
- website.md
- WordPress Playground
- debugging.md

## God Nodes (most connected - your core abstractions)
1. `ProductQueryEngine` - 60 edges
2. `ProductQueryEngineTest` - 52 edges
3. `VariationEngine` - 42 edges
4. `VariationEngineTest` - 34 edges
5. `Catalog` - 30 edges
6. `Catalogist — Agent Instructions` - 30 edges
7. `Catalogist` - 30 edges
8. `Catalogist — Claude Code Instructions` - 28 edges
9. `CatalogCrudTest` - 23 edges
10. `گزارش Verification — Stage X` - 18 edges

## Surprising Connections (you probably didn't know these)
- `CatalogCrudTest` --inherits--> `PHPUnit\Framework\TestCase`  [EXTRACTED]
  tests/Integration/CatalogCrudTest.php →   _Bridges community 0 → community 4_
- `ProductQueryEngineTest` --inherits--> `PHPUnit\Framework\TestCase`  [EXTRACTED]
  tests/Integration/ProductQueryEngineTest.php →   _Bridges community 1 → community 4_
- `ProductQueryEngineTest` --inherits--> `PHPUnit\Framework\TestCase`  [EXTRACTED]
  tests/Unit/ProductQueryEngineTest.php →   _Bridges community 25 → community 4_

## Import Cycles
- None detected.

## Communities (31 total, 3 thin omitted)

### Community 0 - "Catalog"
Cohesion: 0.05
Nodes (8): Admin, Catalog, CatalogPostType, Plugin, CatalogCrudTest, WordPressBaselineTest, CatalogTest, WP_Post

### Community 2 - "Catalogist — Agent Instructions"
Cohesion: 0.04
Nodes (45): 10. WooCommerce Rules, 11. Security, 12. Testing, 13. Definition of Implemented, 14. Regression Protection, 15. Skills, 16. Legacy Project, 17. Error Handling (+37 more)

### Community 3 - "Catalogist — Claude Code Instructions"
Cohesion: 0.05
Nodes (39): 10. WooCommerce Development, 11. Security by Default, 12. Testing, 13. Runtime Verification, 14. Error Handling, 15. Legacy Project, 16. Git Safety, 17. Git Checkpoints (+31 more)

### Community 4 - "VariationEngine"
Cohesion: 0.06
Nodes (5): PHPUnit\Framework\TestCase, VariationEngine, VariationEngineTest, BaselineTest, VariationEngineTest

### Community 5 - "composer.json"
Cohesion: 0.09
Nodes (21): dealerdirect/phpcodesniffer-composer-installer, authors, autoload, autoload-dev, psr-4, psr-4, config, allow-plugins (+13 more)

### Community 6 - "Catalogist"
Cohesion: 0.05
Nodes (39): 1. Vertical Slice, 2. Progressive Architecture, 3. Strict Scope Isolation, 4. Evidence Over Assumption, Anti-Overengineering, Catalog, Catalog CPT, Catalog Items (+31 more)

### Community 7 - "stage-reporter.md"
Cohesion: 0.06
Nodes (31): 10. Test Environment, 11. Test Verification, 12. Code Quality, 13. Security Verification, 14. Architecture Verification, 15. Previous Reports, 16. Evidence Rules, 17. Stage Gate (+23 more)

### Community 8 - "Gutenberg Blocks"
Cohesion: 0.07
Nodes (28): Best Practices, Block Development Overview, block.json for Dynamic Block, block.json (WordPress 6.4+), Block Patterns, Block Registration, Block Types, Do (+20 more)

### Community 9 - "گزارش Verification — Stage 2: Product Query Engine — First Vertical Slice"
Cohesion: 0.08
Nodes (25): API عمومی, Architecture, Code Quality, Evidence, Final Decision, Implementation, Known Issues, Regressions (+17 more)

### Community 10 - "catalogist-stage-verification/SKILL.md"
Cohesion: 0.08
Nodes (24): 10. WordPress Security Verification, 11. Architecture Verification, 12. Regression Verification, 13. Previous Reports, 14. Evidence Rules, 15. Stage Gate, 16. Report, 2. Scope Lock (+16 more)

### Community 15 - "Theme Development"
Cohesion: 0.08
Nodes (24): Best Practices, Block Patterns, Block Template Example (templates/single.html), Block Theme Development (FSE), Block Theme Structure, Child Theme Development, Child Theme functions.php, Child Theme Structure (+16 more)

### Community 16 - "Security Hardening"
Cohesion: 0.10
Nodes (19): Asset Optimization, Backup Strategy, Best Practices Summary, Capability Checks, Database Cleanup, Database Query Optimization, File Upload Security, Image Optimization (+11 more)

### Community 17 - "Plugin Architecture"
Cohesion: 0.10
Nodes (19): Activation & Deactivation, Activator Class, Best Practices, Custom Post Types & Taxonomies, Deactivator Class, Do, Do Not, Full Plugin Structure (Enterprise) (+11 more)

### Community 18 - "گزارش Verification — Stage X"
Cohesion: 0.11
Nodes (19): 17. Final Chat Response, 18. Stop Condition, 19. Anti-Hallucination Rule, Architecture, Code Quality, Evidence, Final Decision, Implementation (+11 more)

### Community 19 - "Hooks & Filters"
Cohesion: 0.11
Nodes (18): Action Basics, Actions, Best Practices, Creating Custom Hooks, Custom Actions, Custom Filter with Default Value, Do, Do Not (+10 more)

### Community 20 - "Stage 3 — Variation Engine: First Vertical Slice"
Cohesion: 0.11
Nodes (18): Acceptance Criteria, Architecture Decision, Dependencies, Exit Criteria, Files / Areas Expected to Change, Goal, In Scope, Integration Tests (WordPress + WooCommerce runtime) (+10 more)

### Community 21 - "گزارش Verification — Stage 1: Foundation — Catalog CPT"
Cohesion: 0.11
Nodes (16): 2. Line Endings — Git CRLF/LF, 3. SKU Search Limitation, Acceptance Criteria, Architecture Review, Files Changed (مقایسه با آخرین commit), Private Constants — توضیح معماری, Scope Check, Security Review (+8 more)

### Community 22 - "Stage 2 — Product Query Engine: First Vertical Slice"
Cohesion: 0.12
Nodes (16): Architecture, Files Changed, Git, Goal, Implemented, New Files, Out of Scope, Query Features (+8 more)

### Community 23 - "Workflow"
Cohesion: 0.12
Nodes (15): Common Elementor WP-CLI Commands, Critical Patterns, CSS Cache, Elementor Data Format, Elementor Pro vs Free, Global Widgets, Prerequisites, Step 1: Identify the Page (+7 more)

### Community 24 - "WordPress Pro"
Cohesion: 0.13
Nodes (14): Capability Checks, Constraints, Core Workflow, Enqueuing Scripts & Styles, Key Implementation Patterns, Knowledge Reference, MUST DO, MUST NOT DO (+6 more)

### Community 26 - "cli.md"
Cohesion: 0.17
Nodes (11): Advanced local server, Build a snapshot, Failure modes, Manual mounts, Playground CLI workflows, Prerequisites, Quick local development, Run a Blueprint (+3 more)

### Community 27 - "website.md"
Cohesion: 0.22
Nodes (8): Create or open a site with a URL, Decide what to do, Interact with the active WordPress site, Manage browser sites with `window.playgroundSites`, Playground website workflows, Route first, Verification, Working model for agents

### Community 28 - "WordPress Playground"
Cohesion: 0.25
Nodes (7): Escalation, Failure modes, Guardrails, Inputs required, Procedure, Verification, WordPress Playground

## Knowledge Gaps
- **379 isolated node(s):** `name`, `description`, `type`, `authors`, `phpunit/phpunit` (+374 more)
  These have ≤1 connection - possible missing edges or undocumented components.
- **3 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `ProductQueryEngineTest` connect `ProductQueryEngine` to `VariationEngine`?**
  _High betweenness centrality (0.030) - this node is a cross-community bridge._
- **Why does `CatalogCrudTest` connect `Catalog` to `VariationEngine`?**
  _High betweenness centrality (0.015) - this node is a cross-community bridge._
- **Are the 3 inferred relationships involving `Catalog` (e.g. with `.render_products_meta_box()` and `.render_settings_meta_box()`) actually correct?**
  _`Catalog` has 3 INFERRED edges - model-reasoned connections that need verification._
- **What connects `name`, `description`, `type` to the rest of the system?**
  _379 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `Catalog` be split into smaller, more focused modules?**
  _Cohesion score 0.053246753246753244 - nodes in this community are weakly interconnected._
- **Should `ProductQueryEngine` be split into smaller, more focused modules?**
  _Cohesion score 0.05384615384615385 - nodes in this community are weakly interconnected._
- **Should `Catalogist — Agent Instructions` be split into smaller, more focused modules?**
  _Cohesion score 0.043478260869565216 - nodes in this community are weakly interconnected._