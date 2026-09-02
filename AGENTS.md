# Catalogist — Agent Instructions

## 1. Project Context

Catalogist is a WordPress/WooCommerce plugin for building professional product catalogs.

The project is being rebuilt from the ground up.

The previous implementation is preserved only for historical reference and lessons learned. It must not be treated as the implementation foundation.

---

## 2. Source of Truth

Before making implementation decisions, use the following hierarchy:

1. Current code and runtime behavior
2. Current Stage Contract
3. `prompt.txt`
4. `AGENTS.md`
5. Relevant project Skills
6. `CLAUDE.md` when working through Claude Code
7. Legacy implementation only for lessons and historical reference

Do not assume that a planned feature is already implemented.

---

## 3. Development Philosophy

Catalogist follows:

* Vertical Slice Development
* Progressive Architecture
* Strict Scope Isolation
* Evidence-Based Development
* Test-Driven Verification where practical
* Stage Gates
* Small Git Checkpoints

Do not use Big-Bang Architecture.

Do not implement the entire target architecture before it is needed.

---

## 4. Current Stage Only

The current Stage defines what may be implemented.

Before modifying code:

1. Identify the current Stage.
2. Read its Stage Contract.
3. Understand its goal and acceptance criteria.
4. Identify what is explicitly out of scope.
5. Inspect the existing implementation.
6. Inspect relevant Skills.
7. Implement only what is required.

If a requested change belongs to a future Stage, do not silently implement it.

---

## 5. Scope Isolation

Every Stage must have clear boundaries.

Do not expand the scope because:

* A future feature would be easier to implement now.
* A new abstraction appears architecturally elegant.
* A related subsystem is incomplete.
* A folder or interface seems useful.
* A feature is easy to add while touching the code.

If a necessary change affects another subsystem, explain why it is required before expanding the scope.

---

## 6. Progressive Architecture

Build architecture as the real requirements emerge.

Prefer:

```text
Real requirement
      ↓
Minimal implementation
      ↓
Test
      ↓
Observed need
      ↓
Small abstraction
```

Avoid:

```text
Future possibility
      ↓
Interface
      ↓
Factory
      ↓
Repository
      ↓
Service
      ↓
Manager
      ↓
Unused abstraction
```

Do not create abstractions without a current consumer or demonstrable architectural need.

---

## 7. Target Architecture vs Current Architecture

The project has a long-term target architecture.

That architecture is a direction, not a requirement to implement everything immediately.

Target architecture may include:

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
Rendering
    ↓
Print / Preview
    ↓
Output
```

Only the parts required by the current Stage should exist in the current implementation.

Never assume that because a component exists in the target architecture, it must already exist in the codebase.

---

## 8. Inspect Before Editing

Before making significant changes:

* Inspect the relevant files.
* Search for existing implementations.
* Check hooks and registrations.
* Check dependencies.
* Check tests.
* Check namespaces.
* Check existing conventions.
* Check whether the behavior already exists.

Do not create duplicate implementations because an existing implementation was not discovered.

---

## 9. WordPress Rules

Follow WordPress conventions.

Use appropriate:

* Hooks
* Actions
* Filters
* Capabilities
* Nonces
* Sanitization
* Validation
* Escaping
* Internationalization
* Options
* Post Meta
* Custom Post Types

Do not bypass WordPress APIs without a documented reason.

Do not introduce custom infrastructure when a suitable WordPress mechanism already exists.

---

## 10. WooCommerce Rules

WooCommerce is the product source for Catalogist.

Use WooCommerce APIs appropriately.

Do not duplicate WooCommerce product data unnecessarily.

Avoid unnecessary:

* Product reloads
* Metadata queries
* API calls
* Database queries
* Object creation

Do not implement WooCommerce-related abstractions before the current Stage requires them.

---

## 11. Security

Security applies to every Stage.

Any code handling user input, admin actions, AJAX, REST requests, or persisted data must consider:

* Capability checks
* Nonces
* Validation
* Sanitization
* Escaping
* Safe database access
* Permission boundaries

Never postpone obvious security requirements to a future security Stage.

---

## 12. Testing

Every Stage must include appropriate verification.

Tests should be added or updated for the behavior introduced by the Stage.

Depending on the feature, verification may include:

* Unit tests
* Integration tests
* WordPress runtime tests
* WooCommerce tests
* Security checks
* Regression tests
* Manual verification

A test suite passing does not automatically prove that the implementation is correct.

Runtime behavior must also be considered where appropriate.

---

## 13. Definition of Implemented

A feature is not implemented merely because:

* A file exists.
* A class exists.
* An interface exists.
* A directory exists.
* A method exists.
* A placeholder exists.
* A TODO exists.
* A configuration value exists.

A feature is implemented only when it is:

1. Present in the appropriate code.
2. Properly wired into the application.
3. Executable at runtime.
4. Correctly secured.
5. Tested appropriately.
6. Verified against the Stage acceptance criteria.

---

## 14. Regression Protection

Before declaring a Stage complete:

* Run relevant tests.
* Check previously working behavior.
* Inspect changed areas for unintended effects.
* Verify WordPress loading.
* Verify relevant WooCommerce behavior.
* Check PHP errors and warnings where applicable.

Do not consider a Stage complete merely because the new feature works in isolation.

---

## 15. Skills

Relevant Skills should be inspected before implementation.

Examples include Skills related to:

* WordPress
* WooCommerce
* PHP
* Security
* Testing
* Architecture
* REST/AJAX
* Elementor
* Performance

Use Skills when they materially improve correctness or consistency.

Do not invoke unrelated Skills merely for procedural reasons.

---

## 16. Legacy Project

The previous implementation is available under:

```text
legacy/old-project
```

It is reference material only.

Use it to understand:

* Previous architectural decisions
* Previous failures
* Previous assumptions
* Missing tests
* Scope problems
* Lessons learned

Do not copy its architecture or code into the new implementation without independently validating the need.

The new project must remain independent from the legacy implementation.

---

## 17. Error Handling

Do not hide errors simply to make tests or execution appear successful.

When something fails:

1. Identify the actual failure.
2. Determine its scope.
3. Fix the root cause where appropriate.
4. Verify the fix.
5. Check for regression.

Do not suppress warnings, exceptions, or failures without understanding why they occur.

---

## 18. Performance

Performance matters, but premature optimization is prohibited.

Prefer simple correct code first.

Optimize when:

* A real bottleneck is identified.
* The current Stage requires it.
* Profiling or measurement justifies it.

Do not introduce complex caching or infrastructure merely because performance might become a problem in the future.

---

## 19. File Structure

The project structure must evolve progressively.

Do not create the complete future directory structure in advance.

A new directory or subsystem should be introduced when the current Stage requires it.

Keep files focused and avoid artificial fragmentation.

---

## 20. Naming Conventions

Use the established Catalogist conventions.

### PHP Namespace

```php
Catalogist\
```

### Function Prefix

```text
catalogist_
```

### Text Domain

```text
catalogist
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

## 21. Elementor

Elementor is an optional integration.

The core system must not depend on Elementor.

Do not introduce Elementor dependencies unless the current Stage explicitly covers Elementor integration.

---

## 22. Rendering Separation

Keep data processing separate from presentation.

Do not mix:

* Product querying
* Catalog processing
* Business logic
* Template logic
* Print logic

unless the current Stage explicitly requires a temporary minimal implementation.

As the system grows, responsibilities should be separated based on actual requirements.

---

## 23. Git Checkpoints

Each completed Stage should produce a clean Git checkpoint.

Before the checkpoint:

* Tests pass.
* Security has been reviewed.
* Scope has been respected.
* No unnecessary changes remain.
* Documentation is updated where necessary.
* Working tree is understood.

Do not create commits containing unrelated work.

---

## 24. Documentation

Documentation should describe the actual state of the project.

Never document planned functionality as implemented functionality.

When behavior or architecture changes materially, update the appropriate documentation.

Keep documentation proportional to the complexity of the feature.

---

## 25. Communication

When reporting work, use this structure:

### Stage

State the Stage being worked on.

### Goal

State what the Stage is intended to achieve.

### Implemented

List only functionality that actually works.

### Files Changed

List important changed files.

### Tests

Report the tests that were actually run and their results.

### Security

Report relevant security verification.

### Regression

Report relevant regression checks.

### Architecture

Explain important architectural decisions or changes.

### Out of Scope

Mention important things intentionally not implemented.

### Result

State whether the Stage Gate passed.

---

## 26. Stop Conditions

Stop and ask for clarification when:

* The Stage scope is ambiguous.
* A requirement conflicts with the current architecture.
* A change would significantly expand the Stage.
* Multiple architectural approaches have materially different consequences.
* A destructive Git operation is required.
* Existing behavior contradicts the expected behavior.
* A dependency is missing.
* Tests cannot provide sufficient confidence.
* The requested implementation would create premature architecture.

Do not guess when the decision materially affects the project.

---

## 27. Stage Completion

A Stage is complete only when the Stage Gate is satisfied.

Minimum gate:

```text
Goal achieved
    ↓
Scope respected
    ↓
Acceptance criteria pass
    ↓
Tests pass
    ↓
Security reviewed
    ↓
Regression checked
    ↓
Architecture reviewed
    ↓
Documentation updated if needed
    ↓
Git checkpoint
```

Only then should the next Stage begin.

---

## 28. First Stage Direction

The initial Stage is intentionally small.

The expected direction is:

```text
WordPress
    ↓
Catalogist Bootstrap
    ↓
Dependency / Requirements Check
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

Do not implement Product Query, Variation Engine, Template Engine, Elementor, Print, Preview, or Output during the initial Stage unless explicitly added to the Stage Contract.

---

## 29. Final Rule

When in doubt:

> Prefer the smallest correct implementation that satisfies the current Stage, can be tested, can be reviewed, and does not create unnecessary future architecture.

**Do not build the project you imagine will be needed later. Build the smallest correct part of the project that is needed now.**
