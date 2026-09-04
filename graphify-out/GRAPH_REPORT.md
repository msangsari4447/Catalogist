# Graph Report - Catalogist  (2026-09-03)

## Corpus Check
- Corpus is ~11,443 words - fits in a single context window. You may not need a graph.

## Summary
- 43 nodes · 44 edges · 11 communities (8 shown, 3 thin omitted)
- Extraction: 100% EXTRACTED · 0% INFERRED · 0% AMBIGUOUS
- Token cost: 0 input · 0 output

## Community Hubs (Navigation)
- Src Catalogposttype
- Composer
- Composer Scripts
- Phpunit Framework Testcase
- Src Plugin
- Composer Allow Plugins Dealerd
- Composer Autoload
- Composer Require Dev

## God Nodes (most connected - your core abstractions)
1. `WordPressBaselineTest` - 8 edges
2. `CatalogPostType` - 7 edges
3. `scripts` - 5 edges
4. `require-dev` - 3 edges
5. `Plugin` - 3 edges
6. `BaselineTest` - 3 edges
7. `autoload` - 2 edges
8. `psr-4` - 2 edges
9. `autoload-dev` - 2 edges
10. `psr-4` - 2 edges

## Surprising Connections (you probably didn't know these)
- `WordPressBaselineTest` --inherits--> `PHPUnit\Framework\TestCase`  [EXTRACTED]
  tests/Integration/WordPressBaselineTest.php →   _Bridges community 0 → community 3_

## Import Cycles
- None detected.

## Communities (11 total, 3 thin omitted)

### Community 1 - "Composer"
Cohesion: 0.25
Nodes (7): authors, autoload-dev, psr-4, description, name, Catalogist\\Tests\\, type

### Community 2 - "Composer Scripts"
Cohesion: 0.40
Nodes (5): scripts, lint, test, test:integration, test:unit

### Community 5 - "Composer Allow Plugins Dealerd"
Cohesion: 0.67
Nodes (3): dealerdirect/phpcodesniffer-composer-installer, config, allow-plugins

### Community 6 - "Composer Autoload"
Cohesion: 0.67
Nodes (3): autoload, psr-4, Catalogist\\

### Community 7 - "Composer Require Dev"
Cohesion: 0.67
Nodes (3): require-dev, phpunit/phpunit, wp-coding-standards/wpcs

## Knowledge Gaps
- **13 isolated node(s):** `name`, `description`, `type`, `authors`, `phpunit/phpunit` (+8 more)
  These have ≤1 connection - possible missing edges or undocumented components.
- **3 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `scripts` connect `Composer Scripts` to `Composer`?**
  _High betweenness centrality (0.086) - this node is a cross-community bridge._
- **Why does `WordPressBaselineTest` connect `Src Catalogposttype` to `Phpunit Framework Testcase`, `Src Plugin`?**
  _High betweenness centrality (0.067) - this node is a cross-community bridge._
- **Why does `CatalogPostType` connect `Src Catalogposttype` to `Src Plugin`?**
  _High betweenness centrality (0.049) - this node is a cross-community bridge._
- **What connects `name`, `description`, `type` to the rest of the system?**
  _13 weakly-connected nodes found - possible documentation gaps or missing edges._