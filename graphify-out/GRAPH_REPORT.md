# Graph Report - Catalogist  (2026-09-09)

## Corpus Check
- 59 files · ~78,055 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 551 nodes · 958 edges · 21 communities (11 shown, 10 thin omitted)
- Extraction: 97% EXTRACTED · 3% INFERRED · 0% AMBIGUOUS · INFERRED: 26 edges (avg confidence: 0.85)
- Token cost: 0 input · 0 output

## Community Hubs (Navigation)
- Src Productqueryengine
- Phpunit Framework Testcase
- Src Sortengine
- Composer Setup
- Src Selectionengine
- Tests Integration Catalogcrudt
- Tests Integration Filterengine
- Tests Unit Filterenginetest
- Src Catalog
- Composer
- Src Filterengine
- Tests Unit Catalogconfiguratio
- Src Admin
- Tests Unit Productqueryenginet
- Phpunit Framework Attributes D
- Tests Unit Catalogtest

## God Nodes (most connected - your core abstractions)
1. `FilterEngine` - 90 edges
2. `Catalog` - 83 edges
3. `ProductQueryEngine` - 60 edges
4. `CatalogCrudTest` - 59 edges
5. `ProductQueryEngineTest` - 52 edges
6. `FilterEngineTest` - 47 edges
7. `SortEngine` - 43 edges
8. `VariationEngine` - 42 edges
9. `SelectionEngine` - 41 edges
10. `VariationEngineTest` - 34 edges

## Surprising Connections (you probably didn't know these)
- `CatalogCrudTest` --inherits--> `PHPUnit\Framework\TestCase`  [EXTRACTED]
  tests/Integration/CatalogCrudTest.php →   _Bridges community 5 → community 1_
- `FilterEngineTest` --inherits--> `PHPUnit\Framework\TestCase`  [EXTRACTED]
  tests/Integration/FilterEngineTest.php →   _Bridges community 6 → community 1_
- `ProductQueryEngineTest` --inherits--> `PHPUnit\Framework\TestCase`  [EXTRACTED]
  tests/Integration/ProductQueryEngineTest.php →   _Bridges community 0 → community 1_
- `SelectionEngineTest` --inherits--> `PHPUnit\Framework\TestCase`  [EXTRACTED]
  tests/Integration/SelectionEngineTest.php →   _Bridges community 4 → community 1_
- `SortEngineTest` --inherits--> `PHPUnit\Framework\TestCase`  [EXTRACTED]
  tests/Integration/SortEngineTest.php →   _Bridges community 2 → community 1_

## Import Cycles
- None detected.

## Communities (21 total, 10 thin omitted)

### Community 1 - "Phpunit Framework Testcase"
Cohesion: 0.06
Nodes (5): PHPUnit\Framework\TestCase, VariationEngine, VariationEngineTest, BaselineTest, VariationEngineTest

### Community 2 - "Src Sortengine"
Cohesion: 0.06
Nodes (4): SortEngine, SortEngineTest, SortEngineTest, WC_Product

### Community 3 - "Composer Setup"
Cohesion: 0.08
Nodes (21): checkParams(), checkPlatform(), displayHelp(), ErrorHandler, getHomeDir(), getIniMessage(), getOptValue(), getPlatformIssues() (+13 more)

### Community 4 - "Src Selectionengine"
Cohesion: 0.07
Nodes (3): SelectionEngine, SelectionEngineTest, SelectionEngineTest

### Community 9 - "Composer"
Cohesion: 0.09
Nodes (21): dealerdirect/phpcodesniffer-composer-installer, authors, autoload, autoload-dev, psr-4, psr-4, config, allow-plugins (+13 more)

### Community 14 - "Phpunit Framework Attributes D"
Cohesion: 0.17
Nodes (4): PHPUnit\Framework\Attributes\DataProvider, CatalogPostType, Plugin, WordPressBaselineTest

## Knowledge Gaps
- **13 isolated node(s):** `name`, `description`, `type`, `authors`, `phpunit/phpunit` (+8 more)
  These have ≤1 connection - possible missing edges or undocumented components.
- **10 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `ProductQueryEngineTest` connect `Src Productqueryengine` to `Phpunit Framework Testcase`?**
  _High betweenness centrality (0.182) - this node is a cross-community bridge._
- **Why does `CatalogCrudTest` connect `Tests Integration Catalogcrudt` to `Src Catalog`, `Phpunit Framework Testcase`, `Src Admin`, `Phpunit Framework Attributes D`?**
  _High betweenness centrality (0.166) - this node is a cross-community bridge._
- **Why does `FilterEngine` connect `Src Filterengine` to `Phpunit Framework Testcase`, `Src Sortengine`, `Src Selectionengine`, `Tests Integration Filterengine`, `Tests Unit Filterenginetest`, `Tests Integration Filterengine`?**
  _High betweenness centrality (0.122) - this node is a cross-community bridge._
- **Are the 5 inferred relationships involving `Catalog` (e.g. with `.render_pipeline_config_meta_box()` and `.render_products_meta_box()`) actually correct?**
  _`Catalog` has 5 INFERRED edges - model-reasoned connections that need verification._
- **What connects `name`, `description`, `type` to the rest of the system?**
  _13 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `Src Productqueryengine` be split into smaller, more focused modules?**
  _Cohesion score 0.05268065268065268 - nodes in this community are weakly interconnected._
- **Should `Phpunit Framework Testcase` be split into smaller, more focused modules?**
  _Cohesion score 0.055191256830601096 - nodes in this community are weakly interconnected._