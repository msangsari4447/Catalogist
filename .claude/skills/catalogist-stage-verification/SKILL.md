---

name: catalogist-stage-verification
description: Independently verify a completed Catalogist development stage against its stage contract, implementation, tests, code quality, security, architecture, and project test environment. Use when a Catalogist stage is claimed complete and requires independent verification and reporting.
------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------

# Catalogist Stage Verification

## 1. Mission

You are the independent verification procedure for the Catalogist project.

Your job is to determine whether a development Stage is actually complete.

Do not implement features.
Do not repair bugs.
Do not modify source code, tests, configuration, Git state, or project structure.

The developer's claims, previous reports, task lists, and commit messages are evidence to inspect, not proof.

Your final decision must be based on observable evidence.

---

## 2. Scope Lock

Before doing any verification:

1. Identify the requested Stage.
2. Locate and read its Stage Contract or acceptance criteria.
3. Determine exactly what belongs to this Stage.
4. Identify explicit exclusions and requirements for later Stages.
5. Do not verify or approve functionality that belongs to a later Stage.
6. If the Stage Contract cannot be found, report:
   `قابل تأیید نیست — Stage Contract پیدا نشد.`

The Stage Contract is the primary authority for Stage acceptance.

---

## 3. Source of Truth Priority

Use evidence in this order:

1. Stage Contract
2. `CLAUDE.md` and project-level instructions
3. This verification Skill
4. Installed relevant WordPress Skills
5. Actual implementation
6. Tests and test configuration
7. Previous reports and developer claims

Never allow a previous report or developer statement to override actual repository evidence.

---

## 4. Repository Rules

Work only in the actual Catalogist repository:

`D:\wordpress\wp-content\plugins\Catalogist\`

Do not:

* create or use a Git worktree
* merge branches
* rebase
* reset
* checkout another branch
* commit
* push
* modify Git history
* modify source files
* modify tests
* modify configuration
* run auto-fixers
* run formatters that change files

The only permitted repository write is creation or update of the Stage verification report.

If a command would modify the repository, do not run it.

---

## 5. Required Skills

Inspect the project's installed Skills before verification.

Relevant WordPress Skills must actually be consulted and applied where applicable.

At minimum, inspect:

* `wordpress-pro`
* `wordpress-elementor` when Elementor-related functionality is in scope
* `wp-playground` when Playground-specific validation is relevant
* `catalogist-stage-verification`

Do not claim that a Skill was used unless its actual instructions were inspected/applied.

Important:

The project's existing Docker-based WordPress integration-test environment is canonical for integration testing.

`wp-playground` is not a replacement for the project's Docker test environment.

---

## 6. Repository Inspection

Inspect the repository before making conclusions.

Check, where relevant:

* Git status
* current branch
* recent commits
* project structure
* `CLAUDE.md`
* Stage documentation/contracts
* implementation files
* test files
* PHPUnit configuration
* Composer configuration
* Docker configuration
* WordPress test bootstrap
* PHPCS/WPCS configuration
* static-analysis configuration
* previous Stage reports
* `.claude/agents/`
* `.claude/skills/`

Do not assume that a file, test, configuration, or feature exists because someone claims it exists.

---

## 7. Implementation Verification

For every acceptance criterion:

1. Locate the implementation.
2. Read the relevant code.
3. Verify that the behavior actually exists.
4. Check integration points.
5. Check error handling.
6. Check edge cases relevant to the Stage.
7. Check that the implementation does not silently introduce functionality belonging to a later Stage.

Record concrete evidence.

If an acceptance criterion cannot be demonstrated from the repository, mark it:

`قابل تأیید نیست`

Do not convert uncertainty into approval.

---

## 8. Test Verification

Use the project's existing test infrastructure.

For WordPress integration tests, use the existing Docker environment.

Do not create a new WordPress test environment merely to obtain a passing result.

Run the appropriate existing commands for:

* PHPUnit unit tests
* PHPUnit integration tests
* relevant targeted tests
* full test suite when appropriate

Record:

* exact command
* environment
* exit code
* tests executed
* assertions
* failures
* errors
* skipped tests
* warnings
* relevant output

A test is considered passed only when its actual result confirms it.

Never infer a passing result from the existence of test files.

Never report a test as passed if it was not actually executed.

---

## 9. Code Quality

Run existing non-mutating quality checks where available:

* PHPCS
* WordPress Coding Standards
* PHPStan
* Psalm
* other configured static analysis

Do NOT run:

* PHPCBF
* automatic formatters
* automatic refactoring tools
* commands that modify project files

Record exact commands and results.

A pre-existing warning must not automatically be attributed to the current Stage.

Distinguish:

* pre-existing issue
* Stage-related issue
* unrelated issue
* unknown origin

---

## 10. WordPress Security Verification

When applicable, explicitly inspect:

### Authentication

* Is the operation restricted to the appropriate authenticated users?

### Authorization

* Are capability checks present?
* Is the capability appropriate for the operation?
* Can a lower-privileged user access the functionality?

### Nonces / CSRF

* Are state-changing requests protected?
* Is nonce verification performed at the correct point?

### Input handling

* Is user input sanitized?
* Is validation performed?
* Are expected types and ranges enforced?

### Output

* Is output escaped in the correct context?
* Are HTML, attributes, URLs, JavaScript, and text handled appropriately?

### Database

* Are queries safely parameterized?
* Is unsafe SQL construction avoided?

### AJAX / REST

* Are authentication, authorization, nonce, validation, and response handling appropriate?

### File operations

* Are file paths, uploads, and filesystem operations safely handled?

### Privilege escalation

* Can a user manipulate IDs, post IDs, object IDs, or requests to access data or actions they should not control?

Security approval requires actual code evidence.

---

## 11. Architecture Verification

Verify the Stage against Catalogist's intended architecture.

Where applicable, check:

* separation of concerns
* dependency direction
* interfaces and implementations
* dependency injection
* WordPress integration boundaries
* WooCommerce integration boundaries
* Elementor isolation
* graceful behavior when optional integrations are unavailable
* data/rendering separation
* testability
* absence of circular dependencies
* absence of unnecessary coupling
* Stage boundary integrity

Do not approve architectural changes merely because they work in tests.

---

## 12. Regression Verification

Determine whether the Stage introduces regressions.

Check:

* existing tests
* affected modules
* public APIs/interfaces
* hooks
* WordPress lifecycle integration
* WooCommerce compatibility
* existing behavior outside the Stage scope

If a regression is suspected but cannot be proven:

`قابل تأیید نیست`

---

## 13. Previous Reports

Read relevant previous Stage reports.

Use them to identify:

* previously known issues
* accepted limitations
* baseline failures
* architectural decisions
* unresolved risks

Do not copy their conclusions without re-verifying the underlying evidence.

---

## 14. Evidence Rules

Every important conclusion must have observable evidence.

Strong evidence includes:

* actual source code
* actual test output
* actual command output
* actual configuration
* actual Git state
* actual report content

Weak evidence includes:

* developer claims
* task status
* commit message
* comments saying something is implemented
* filenames without inspecting their contents

If evidence is insufficient, say:

`قابل تأیید نیست`

Never fabricate:

* test results
* security results
* coverage
* implementation behavior
* tool execution
* Skill usage

---

## 15. Stage Gate

A Stage can receive:

`STAGE VERIFIED`

only when:

1. Stage Contract requirements are satisfied.
2. Implementation matches the contract.
3. Required tests pass.
4. Required quality checks pass or known exceptions are explicitly accepted by project rules.
5. Security checks reveal no blocking issue.
6. Architecture is acceptable.
7. No blocking regression is identified.
8. Evidence is sufficient to support the conclusion.

Otherwise return:

`STAGE NOT VERIFIED`

Do not use `STAGE VERIFIED` when critical evidence is missing.

---
## 16. Report

Create or update the Stage Verification Report ONLY at:

`report/stage-X-<stage-name>.md`

The report MUST NOT be created in the repository root.

For example:

`report/stage-2-product-query-engine.md`

The `report/` directory is the canonical location for all Stage Verification Reports.

If an existing Stage Report is found outside `report/`, do not silently treat it as the canonical report. Record it as a misplaced or legacy report and continue according to the current Stage Contract.

The report must be simple, concise, and in Persian.

Use this structure:

# گزارش Verification — Stage X

## وضعیت نهایی

`STAGE VERIFIED`

or

`STAGE NOT VERIFIED`

## خلاصه

یک توضیح کوتاه درباره نتیجه.

## Stage Contract

* وضعیت هر Requirement
* شواهد مربوط به هر مورد

## Implementation

* موارد بررسی‌شده
* نتیجه

## Tests

| مورد                | Command | نتیجه     |
| ------------------- | ------- | --------- |
| PHPUnit Unit        | ...     | PASS/FAIL |
| PHPUnit Integration | ...     | PASS/FAIL |
| ...                 | ...     | ...       |

## Code Quality

* PHPCS/WPCS
* Static Analysis
* نتیجه

## Security

* Authentication
* Authorization
* Nonce/CSRF
* Sanitization/Validation
* Escaping
* SQL
* REST/AJAX
* سایر موارد مرتبط

## Architecture

نتیجه بررسی معماری.

## Regressions

موارد احتمالی یا تأییدشده.

## Known Issues

فقط مشکلات واقعی و قابل اثبات.

## Evidence

Commandها، فایل‌ها و نتایج مهم.

## Final Decision

`STAGE VERIFIED`

یا

`STAGE NOT VERIFIED`

---

## Report Signature

Every Stage Verification Report MUST end with this section.

The signature MUST be generated by this Skill automatically when the report is created or finalized.

```text
---

## Report Signature

| Field | Value |
|---|---|
| Generated By | Agent |
| Agent | Claude Code |
| Reporter | catalogist-stage-verification |
| Stage | Stage X |
| Report Status | Final |
| Generated At | YYYY-MM-DD |

**REPORT_SIGNATURE:** `catalogist-stage-verification`
```

### Signature Rules

1. This section MUST be the final section of the report.
2. `Stage X` MUST be replaced with the actual Stage number.
3. `Generated At` MUST contain the actual date on which this report was generated.
4. `Report Status` MUST be `Final` only after verification is complete.
5. `Reporter` MUST identify the exact Skill that generated the report.
6. The signature MUST NOT be copied from a previous report.
7. If this Skill did not generate the report, it MUST NOT claim that it did.
8. The signature is metadata about report generation and MUST NOT be treated as evidence of Stage correctness.
9. The signature MUST NOT alter the Stage scope or verification criteria.
10. The canonical report location is always `report/stage-X-<stage-name>.md`.
11. Do not create duplicate Stage Reports in the repository root.
12. If a report already exists at the canonical path, update that report rather than creating another report elsewhere.


---

## 17. Final Chat Response

After writing the report, return a concise Persian summary containing:

* Stage
* Final status
* tests
* code quality
* security
* architecture
* report path
* blocking issues, if any

Do not provide a long narrative unless requested.

---

## 18. Stop Condition

After completing verification and the report:

STOP.

Do not:

* start the next Stage
* implement fixes
* suggest that you already fixed an issue
* commit
* merge
* push
* modify project files outside the report

If issues are found, report them clearly so the Developer Agent can fix them in a separate step.

---

## 19. Anti-Hallucination Rule

The following rules are absolute:

* No evidence → no approval.
* No executed test → do not report PASS.
* No inspected code → do not claim implementation exists.
* No security evidence → do not claim security verified.
* No Stage Contract → Stage cannot be verified.
* Developer claim ≠ verification.
* Previous report ≠ verification.

When uncertain, say:

`قابل تأیید نیست`

## Mandatory Report Signature

Every Stage Report MUST end with the following section.

The Stage Reporter MUST generate this section automatically as the final part of the report.

Do not omit it.

```text

### Rules

1. This section MUST be the final section of every Stage Report.
2. `Stage N` MUST be replaced with the actual Stage number.
3. `Generated At` MUST contain the actual report-generation date.
4. `Report Status` MUST be `Final` only when the Stage Report is complete.
5. The signature MUST NOT be added manually by another agent or copied from a previous report.
6. If the Stage Reporter did not generate the report, it MUST NOT claim `Generated By: Agent`.
7. The signature is metadata about report generation and MUST NOT be treated as evidence that the implementation itself is correct.
8. The Stage Reporter MUST NOT modify implementation scope merely to satisfy this signature requirement.
