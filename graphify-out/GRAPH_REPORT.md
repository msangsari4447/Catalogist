# Graph Report - Catalogist  (2026-09-06)

## Corpus Check
- 56 files · ~66,166 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 456 nodes · 770 edges · 18 communities (11 shown, 7 thin omitted)
- Extraction: 100% EXTRACTED · 0% INFERRED · 0% AMBIGUOUS
- Token cost: 0 input · 0 output

## Community Hubs (Navigation)
- Catalog Core
- Engine Pipeline
- WordPress Integration
- Test Infrastructure
- Stage Reports
- Agent & Skills
- Documentation
- Plugin Bootstrap
- Variation Engine
- Stage 1 Foundation
- Stage 2 Query
- Stage 3 Variation
- Stage 4 Filter
- Product Query
- Filter Engine
- Sort Engine
- Admin Interface

## God Nodes (most connected - your core abstractions)

## Surprising Connections (you probably didn't know these)
- `ProductQueryEngine` ----> `Integration/ProductQueryEngineTest.php`  [EXTRACTED]
   →   _Bridges community 0 → community 6_
- `SortEngine` ----> `Integration/SelectionEngineTest.php`  [EXTRACTED]
   →   _Bridges community 1 → community 3_
- `SortEngineTest` ----> `PHPUnit\Framework\TestCase`  [EXTRACTED]
   →   _Bridges community 1 → community 6_
- `VariationEngine` ----> `Integration/VariationEngineTest.php`  [EXTRACTED]
   →   _Bridges community 2 → community 6_
- `SelectionEngine` ----> `Unit/SelectionEngineTest.php`  [EXTRACTED]
   →   _Bridges community 3 → community 6_

## Import Cycles
- None detected.

## Communities (18 total, 7 thin omitted)

### Community 0 - "Catalog Core"
Cohesion: 0.05
Nodes (3): ProductQueryEngine, ProductQueryEngineTest, ProductQueryEngine.php

### Community 1 - "Engine Pipeline"
Cohesion: 0.06
Nodes (7): SortEngine, SortEngineTest, SortEngineTest, SortEngine.php, Integration/SortEngineTest.php, Unit/SortEngineTest.php, WC_Product

### Community 2 - "WordPress Integration"
Cohesion: 0.06
Nodes (5): VariationEngine, VariationEngineTest, VariationEngineTest, VariationEngine.php, Unit/VariationEngineTest.php

### Community 3 - "Test Infrastructure"
Cohesion: 0.07
Nodes (5): SelectionEngine, SelectionEngineTest, SelectionEngineTest, SelectionEngine.php, Integration/SelectionEngineTest.php

### Community 4 - "Stage Reports"
Cohesion: 0.07
Nodes (9): Admin, Catalog, CatalogCrudTest, CatalogTest, Admin.php, Catalog.php, CatalogCrudTest.php, CatalogTest.php (+1 more)

### Community 6 - "Documentation"
Cohesion: 0.07
Nodes (15): CatalogPostType, Plugin, WordPressBaselineTest, BaselineTest, ProductQueryEngineTest, PHPUnit\Framework\TestCase, CatalogPostType.php, Plugin.php (+7 more)

### Community 8 - "Product Query"
Cohesion: 0.09
Nodes (22): composer.json, dealerdirect/phpcodesniffer-composer-installer, authors, autoload, autoload-dev, psr-4, psr-4, config (+14 more)

### Community 10 - "Filter Engine"
Cohesion: 0.28
Nodes (17): Stage Reporter Agent, AGENTS.md — Catalogist Agent Instructions, README.md — Catalogist Project Overview, Catalogist Stage Verification, CLAUDE.md — Catalogist Development Instructions, Catalogist Stage Verification Skill, Graphify Skill, Development Prompt (prompt.txt) (+9 more)

### Community 11 - "Sort Engine"
Cohesion: 0.38
Nodes (7): WordPress Elementor Skill, Gutenberg Blocks Reference, Hooks & Filters Reference, Performance & Security Reference, Plugin Architecture Reference, Theme Development Reference, WordPress Pro Skill

### Community 13 - "Admin Interface"
Cohesion: 0.50
Nodes (4): WordPress Playground Skill, WordPress Playground CLI Reference, WordPress Playground Debugging Reference, WordPress Playground Website Reference

## Knowledge Gaps
- **7 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Should `Catalog Core` be split into smaller, more focused modules?**
  _Cohesion score 0.05268065268065268 - nodes in this community are weakly interconnected._
- **Should `Engine Pipeline` be split into smaller, more focused modules?**
  _Cohesion score 0.06038961038961039 - nodes in this community are weakly interconnected._
- **Should `WordPress Integration` be split into smaller, more focused modules?**
  _Cohesion score 0.06219426974143955 - nodes in this community are weakly interconnected._
- **Should `Test Infrastructure` be split into smaller, more focused modules?**
  _Cohesion score 0.06588235294117648 - nodes in this community are weakly interconnected._
- **Should `Stage Reports` be split into smaller, more focused modules?**
  _Cohesion score 0.06859903381642513 - nodes in this community are weakly interconnected._
- **Should `Agent & Skills` be split into smaller, more focused modules?**
  _Cohesion score 0.058823529411764705 - nodes in this community are weakly interconnected._
- **Should `Documentation` be split into smaller, more focused modules?**
  _Cohesion score 0.06628787878787878 - nodes in this community are weakly interconnected._