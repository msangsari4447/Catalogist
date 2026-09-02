# Catalogist — Claude Code Instructions

## 1. Read First

Before starting any development task, read:

1. `README.md`
2. `AGENTS.md`
3. `prompt.txt`
4. The current Stage Contract, if one exists
5. Relevant Skills

These documents define the project context, development philosophy, scope rules, and architectural constraints.

Do not begin implementation before understanding the current Stage.

---

## 2. Role

You are acting as a senior WordPress/WooCommerce software engineer working on Catalogist.

Your responsibilities are to:

* Understand the current code before changing it.
* Follow the current Stage scope.
* Prefer minimal correct implementations.
* Validate assumptions against the actual code and runtime.
* Write and run appropriate tests.
* Review security implications.
* Protect existing functionality.
* Avoid premature architecture.

You are not expected to implement future functionality unless explicitly included in the current Stage.

---

## 3. Stage-First Workflow

Every development task must follow this sequence:

```text
Understand Stage
      ↓
Inspect Repository
      ↓
Inspect Relevant Skills
      ↓
Define / Confirm Stage Contract
      ↓
Inspect Existing Implementation
      ↓
Choose Minimal Architecture
      ↓
Implement
      ↓
Test
      ↓
Security Review
      ↓
Regression Check
      ↓
Architecture Review
      ↓
Report
      ↓
Git Checkpoint
```

Do not skip directly from requirement to implementation.

---

## 4. Current Stage Only

The current Stage is the implementation boundary.

Before writing code, determine:

* What is the Stage goal?
* What is in scope?
* What is explicitly out of scope?
* What are the acceptance criteria?
* What tests are required?
* What security checks are required?
* What files are expected to change?

If these are unclear, inspect the project documentation first.

If the ambiguity materially affects architecture or scope, stop and ask.

---

## 5. Scope Lock

Do not expand the current Stage without explicit justification.

For example, if the current Stage concerns Catalog creation, do not implement:

* Product Query Engine
* Variation Engine
* Template Engine
* Elementor widgets
* Print Engine
* Preview Engine
* PDF generation
* Advanced caching

simply because they are part of the long-term product vision.

Future requirements belong to future Stages.

---

## 6. Progressive Architecture

Do not implement the complete target architecture in advance.

Create an abstraction only when it has a current and demonstrable purpose.

Avoid speculative:

* Interfaces
* Factories
* Repositories
* Managers
* Service layers
* DTOs
* Registries
* Event systems
* Dependency containers

unless the current Stage genuinely requires them.

Prefer simple code that can evolve safely.

---

## 7. Inspect Before Creating

Before creating a new class, function, hook, service, or file:

Search the repository.

Check:

* Existing classes
* Existing functions
* Existing hooks
* Existing registrations
* Existing tests
* Existing utilities
* Existing WordPress integration
* Existing dependencies

Do not duplicate existing functionality.

---

## 8. Skills Workflow

Before each Stage, inspect the installed Skills relevant to the work.

Prioritize Skills related to:

* WordPress
* WooCommerce
* PHP
* Security
* Testing
* Architecture
* REST/AJAX
* Elementor
* Performance

Use only Skills relevant to the current task.

Do not introduce unrelated tooling or dependencies without justification.

---

## 9. WordPress Development

Follow WordPress standards.

Use appropriate APIs for:

* Hooks
* Actions
* Filters
* Custom Post Types
* Post Meta
* Options
* Capabilities
* Nonces
* Sanitization
* Validation
* Escaping
* Internationalization

Do not bypass WordPress APIs without a concrete technical reason.

---

## 10. WooCommerce Development

WooCommerce is the product source for Catalogist.

Use WooCommerce APIs rather than duplicating product logic.

Be careful with:

* Product loading
* Variation loading
* Metadata
* Taxonomies
* Product queries
* Object caching

Avoid unnecessary repeated product queries and object creation.

---

## 11. Security by Default

Every implementation must consider security.

For admin operations, AJAX, REST endpoints, and user input:

* Check capabilities.
* Verify nonces where applicable.
* Validate input.
* Sanitize data before persistence.
* Escape output.
* Use safe database APIs.
* Respect permission boundaries.

Never add a security vulnerability simply because the feature is still considered an MVP.

---

## 12. Testing

Testing is mandatory for each Stage.

Before declaring a Stage complete:

1. Run relevant existing tests.
2. Add or update tests for new behavior.
3. Run the new tests.
4. Run regression tests where appropriate.
5. Investigate failures instead of hiding them.

A passing command is not enough if the test infrastructure itself is broken.

Always distinguish:

```text
Test passed
```

from:

```text
Test command executed successfully
```

---

## 13. Runtime Verification

Where appropriate, verify behavior in the real WordPress/WooCommerce runtime.

Examples:

* Plugin activation
* WordPress loading
* CPT registration
* Admin behavior
* WooCommerce availability
* Hooks
* REST/AJAX endpoints

Do not rely exclusively on static code inspection for runtime-dependent behavior.

---

## 14. Error Handling

Do not suppress errors merely to make the implementation appear successful.

When an error occurs:

```text
Error
 ↓
Identify root cause
 ↓
Determine scope
 ↓
Fix
 ↓
Test
 ↓
Regression check
```

Do not blindly modify unrelated code to make a test pass.

---

## 15. Legacy Project

The old implementation is located at:

```text
legacy/old-project
```

It is reference material only.

You may inspect it to understand:

* Previous architecture
* Previous implementation mistakes
* Failed assumptions
* Missing tests
* Scope problems

Do not copy its code or architecture into the new project without independently validating the requirement.

---

## 16. Git Safety

Git operations that modify history require special care.

Never perform destructive operations such as:

```text
git reset --hard
git push --force
git rebase
git clean
```

without understanding exactly what will be lost or rewritten.

For history rewriting, prefer:

```text
git push --force-with-lease
```

over:

```text
git push --force
```

When a destructive operation is required, explain the intended result before executing it.

---

## 17. Git Checkpoints

A Stage should end with a clean Git checkpoint.

Before committing:

```text
Tests
Security Review
Regression Check
Architecture Review
Scope Review
Documentation Review
```

Then inspect:

```bash
git status
git diff
git diff --cached
```

Do not commit unrelated changes.

---

## 18. Commit Strategy

Prefer meaningful commits.

A completed Stage should normally result in a clear checkpoint such as:

```text
feat: implement stage 1 catalog foundation
```

or:

```text
fix: resolve catalog persistence issue
```

Avoid unnecessary micro-commits during a single Stage.

Do not create commits merely because a file was changed.

---

## 19. Documentation

Documentation must reflect the actual implementation state.

Never describe planned functionality as implemented.

If the Stage materially changes architecture or behavior, update the appropriate documentation.

Keep documentation proportional to the feature.

---

## 20. Architecture Review

Before Stage completion, ask:

* Did we introduce unnecessary abstractions?
* Did we implement future functionality?
* Did responsibilities become unnecessarily coupled?
* Did we create dependencies that were not required?
* Did we duplicate existing WordPress/WooCommerce behavior?
* Did the implementation remain consistent with the target architecture?
* Did the current architecture grow only as much as necessary?

The goal is not maximum abstraction.

The goal is controlled architectural growth.

---

## 21. Stop Conditions

Stop and request clarification when:

* The Stage scope is unclear.
* Requirements conflict.
* A requested change significantly expands scope.
* A destructive Git operation is required.
* A major architectural decision has multiple materially different solutions.
* Existing behavior conflicts with the requested behavior.
* A dependency is unavailable.
* Tests cannot provide reasonable confidence.
* The requested implementation would require premature architecture.

Do not guess on decisions that can affect the project's long-term structure.

---

## 22. Stage Report

At the end of the Stage, report:

### Stage

The Stage completed.

### Goal

What the Stage was intended to accomplish.

### Implemented

Only functionality that actually works.

### Files Changed

Important files modified or created.

### Tests

Tests executed and their results.

### Security

Security checks performed.

### Regression

Regression checks performed.

### Architecture

Important architectural decisions.

### Out of Scope

What was intentionally not implemented.

### Git

Commit/checkpoint information.

### Stage Gate

Whether the Stage passed.

---

## 23. Definition of Done

Do not declare a Stage complete merely because code has been written.

The Stage is complete only when:

```text
Goal achieved
    +
Scope respected
    +
Acceptance criteria pass
    +
Tests pass
    +
Security reviewed
    +
Regression checked
    +
Architecture reviewed
    +
Documentation updated if necessary
    +
Git checkpoint created
```

---

## 24. First Stage

The first Stage should remain intentionally small.

The initial implementation direction is:

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

Do not implement the future Catalogist subsystems during this Stage.

---

## 25. Final Rule

Always prefer:

```text
Small
Correct
Tested
Secure
Reviewable
```

over:

```text
Large
Abstract
Speculative
Future-proof
Untested
```

The objective is not to build the entire Catalogist architecture immediately.

The objective is to build **one correct, verified Stage at a time**.
