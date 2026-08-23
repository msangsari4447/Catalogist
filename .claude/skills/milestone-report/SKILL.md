---
name: milestone-report
description: Generate structured pre/post milestone reports for Catalogist
disable-model-invocation: true
---

# Milestone Report Skill

This skill enforces the milestone reporting requirements defined in `prompt.txt` section 52 and `CLAUDE.md`.

## Usage

```
/milestone-report pre <milestone-number>
/milestone-report post <milestone-number>
```

## Pre-Milestone Report

Before starting any milestone, provide this report:

```markdown
## Pre-Milestone Report: Milestone [N] — [Name]

### 1. Goal of This Milestone
[Clear statement of what this milestone accomplishes]

### 2. Files Affected
[List all files that will be created or modified]

### 3. Architecture Decision
[Key architectural choices for this milestone]

### 4. Implementation Plan
[Step-by-step plan for implementation]

### 5. Risks
[Potential risks and mitigation strategies]
```

## Post-Milestone Report

After completing any milestone, provide this report:

```markdown
## Post-Milestone Report: Milestone [N] — [Name]

### 1. What Changed
[Summary of changes made]

### 2. Files Changed
[List all files that were created or modified with brief descriptions]

### 3. Tests Performed
[Tests run and their results]

### 4. Known Issues
[Any known issues or limitations]

### 5. Next Milestone
[Which milestone comes next and any preparation needed]
```

## Milestone Reference

The 10 milestones for Catalogist:

1. **Core Architecture** — Plugin Bootstrap, Autoloading, Namespaces, Dependency Checks, Admin Menu, Catalog CPT/Model, Basic Settings
2. **Product Query Engine** — Product Query, Category, Tag, Search, Product Selection, Sorting
3. **Variation Engine** — Variable Products, Variation Search, Single/Multi Selection, All Variations, Variation Context
4. **Catalog Processor** — Catalog Item, Data Context, Product Normalization
5. **Template Engine** — Catalog Template, Header, Footer, Product Loop, Product Card
6. **Elementor Integration** — Dynamic Data, Dynamic Tags, Widgets, Template Context
7. **Print Engine** — A4, Portrait/Landscape, Margins, Columns, Page Break, Print CSS
8. **Preview** — Preview Engine, Admin Preview, Print Preview
9. **Output** — HTML, Print, PDF Architecture
10. **Security / Performance / Testing** — Security Audit, Query Optimization, Cache, Unit Tests, Compatibility

## Definition of Done Checklist

Before marking any milestone complete, verify:

- [ ] Feature is fully implemented
- [ ] Code structure is clean
- [ ] Security has been reviewed
- [ ] Error handling exists
- [ ] No regression has been introduced
- [ ] Proper testing has been performed
- [ ] Usage has been documented

## Rules

1. Never skip a milestone or proceed out of order
2. Always complete the pre-milestone report before implementation
3. Always complete the post-milestone report after implementation
4. Do not implement files unrelated to the current milestone
5. Get user approval on pre-milestone report before proceeding
