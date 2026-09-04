# Graph Report - Catalogist  (2026-09-04)

## Corpus Check
- Corpus is ~35,512 words - fits in a single context window. You may not need a graph.

## Summary
- 86 nodes · 113 edges · 15 communities (12 shown, 3 thin omitted)
- Extraction: 97% EXTRACTED · 3% INFERRED · 0% AMBIGUOUS · INFERRED: 3 edges (avg confidence: 0.85)
- Token cost: 0 input · 0 output

## Community Hubs (Navigation)
- Src Catalog
- Src Admin
- Tests Integration Catalogcrudt
- Src Catalogposttype
- Phpunit Framework Testcase
- Composer
- Composer Scripts
- Composer Allow Plugins Dealerd
- Composer Autoload
- Composer Autoload Dev
- Composer Require Dev

## God Nodes (most connected - your core abstractions)
1. `Catalog` - 30 edges
2. `CatalogCrudTest` - 23 edges
3. `Admin` - 12 edges
4. `scripts` - 5 edges
5. `WordPressBaselineTest` - 5 edges
6. `CatalogPostType` - 4 edges
7. `Plugin` - 4 edges
8. `CatalogTest` - 4 edges
9. `require-dev` - 3 edges
10. `BaselineTest` - 3 edges

## Surprising Connections (you probably didn't know these)
- `CatalogCrudTest` --inherits--> `PHPUnit\Framework\TestCase`  [EXTRACTED]
  tests/Integration/CatalogCrudTest.php →   _Bridges community 2 → community 4_
- `WordPressBaselineTest` --inherits--> `PHPUnit\Framework\TestCase`  [EXTRACTED]
  tests/Integration/WordPressBaselineTest.php →   _Bridges community 3 → community 4_

## Import Cycles
- None detected.

## Communities (15 total, 3 thin omitted)

### Community 3 - "Src Catalogposttype"
Cohesion: 0.18
Nodes (3): CatalogPostType, Plugin, WordPressBaselineTest

### Community 4 - "Phpunit Framework Testcase"
Cohesion: 0.32
Nodes (3): PHPUnit\Framework\TestCase, BaselineTest, CatalogTest

### Community 5 - "Composer"
Cohesion: 0.40
Nodes (4): authors, description, name, type

### Community 6 - "Composer Scripts"
Cohesion: 0.40
Nodes (5): scripts, lint, test, test:integration, test:unit

### Community 7 - "Composer Allow Plugins Dealerd"
Cohesion: 0.67
Nodes (3): dealerdirect/phpcodesniffer-composer-installer, config, allow-plugins

### Community 8 - "Composer Autoload"
Cohesion: 0.67
Nodes (3): autoload, psr-4, Catalogist\\

### Community 9 - "Composer Autoload Dev"
Cohesion: 0.67
Nodes (3): autoload-dev, psr-4, Catalogist\\Tests\\

### Community 10 - "Composer Require Dev"
Cohesion: 0.67
Nodes (3): require-dev, phpunit/phpunit, wp-coding-standards/wpcs

## Knowledge Gaps
- **13 isolated node(s):** `name`, `description`, `type`, `authors`, `phpunit/phpunit` (+8 more)
  These have ≤1 connection - possible missing edges or undocumented components.
- **3 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `Catalog` connect `Src Catalog` to `Src Admin`, `Tests Integration Catalogcrudt`, `Src Catalogposttype`, `Phpunit Framework Testcase`?**
  _High betweenness centrality (0.220) - this node is a cross-community bridge._
- **Why does `CatalogCrudTest` connect `Tests Integration Catalogcrudt` to `Src Catalog`, `Src Admin`, `Src Catalogposttype`, `Phpunit Framework Testcase`?**
  _High betweenness centrality (0.159) - this node is a cross-community bridge._
- **Why does `Admin` connect `Src Admin` to `Src Catalogposttype`?**
  _High betweenness centrality (0.091) - this node is a cross-community bridge._
- **Are the 3 inferred relationships involving `Catalog` (e.g. with `.render_products_meta_box()` and `.render_settings_meta_box()`) actually correct?**
  _`Catalog` has 3 INFERRED edges - model-reasoned connections that need verification._
- **What connects `name`, `description`, `type` to the rest of the system?**
  _13 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `Src Catalog` be split into smaller, more focused modules?**
  _Cohesion score 0.14285714285714285 - nodes in this community are weakly interconnected._