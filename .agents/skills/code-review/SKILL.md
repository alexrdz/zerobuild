---
name: code-review
description: Production Readiness Code Review is a skill that guides an AI agent to review pull requests with the same mindset as a senior engineer performing a pre-production gate. It evaluates each change against a structured checklist covering correctness, testing, security, performance, observability, rollout strategy, maintainability, and documentation. For every PR, it inspects the diff, tests, and relevant project files, then produces a structured report with PASS/WARN/FAIL statuses, concrete evidence (file and line references), and actionable recommendations. The skill’s goal is to decide whether the change is fully ready for production, ready with manageable risks, or not ready, and to clearly communicate what must be fixed before deployment.
---

## Skill: Production Readiness Code Review

### Purpose

Ensure that any pull request is **production ready** by validating correctness, safety, observability, security, and operability criteria before approval.

### When to run

- On every PR that:
  - Touches production code paths (backend services, APIs, critical frontend flows)
  - Modifies infra, configs, or feature flags
  - Introduces new dependencies or migrations

### Inputs the agent should use

- PR diff and full context for changed files
- Relevant test results (unit, integration, e2e)
- Lint/formatter output
- CI pipeline status
- Service documentation (README, runbooks, architecture docs)
- Monitoring/alert definitions (if available in repo as config)

***

## Review Objectives

The agent must answer these questions and include them in its report:

1. **Correctness & risk**
   - Does the change obviously break existing behavior or contracts?
   - Are there high-risk changes (data migrations, auth changes, concurrency, money flows)?
   - Are edge cases and error paths handled explicitly?

2. **Testing & CI**
   - Are there automated tests covering the main behavior of the change?
   - Do tests meaningfully assert behavior, not just increase coverage numbers?
   - Does the CI pipeline pass, including lint, type-checking, and security scans?

3. **Security & data protection**
   - Are authz/authn rules unchanged or explicitly updated and justified?
   - Are secrets, tokens, and keys kept out of code and config committed to VCS?
   - Are new dependencies reasonably trusted and free of obvious security red flags?

4. **Performance & scalability**
   - Could this change introduce N+1 queries, unbounded loops, or heavy computations on hot paths?
   - For new APIs or jobs, is there any obvious risk under expected peak load?
   - Is caching, pagination, or batching used where appropriate?

5. **Observability & operations**
   - Is there sufficient logging/metrics for new behavior, especially failures and critical paths?
   - Do logs avoid leaking PII or sensitive data?
   - Are alerts or dashboards updated if this change affects critical SLIs?

6. **Config, rollout, and rollback**
   - Are risky changes guarded behind feature flags or gradual rollout mechanisms where appropriate?
   - Is the migration/rollout plan clear and reversible?
   - Would reverting this PR be a clean rollback if needed?

7. **Code quality & maintainability**
   - Is the code readable, consistent with existing style, and modular enough?
   - Is duplication minimized and existing abstractions used?
   - Are comments and naming sufficient for another engineer to debug this in production?

8. **Documentation & knowledge transfer**
   - Is README / ADR / runbook / API docs updated for behavior, config, or operational changes?
   - Are any breaking changes clearly documented for downstream consumers?
   - Are any “gotchas” or operational quirks documented for on-call engineers?

***

## Step-by-step behavior for the agent

1. **Summarize the change**
   - Generate a short summary of:
     - What the PR does
     - Which systems/endpoints/user flows it affects
     - Risk level (Low / Medium / High) with a one-sentence justification

2. **Run a structured checklist**

   For each section, the agent should output:

   - Status: `PASS`, `WARN`, or `FAIL`
   - Evidence: concrete pointers to code lines and files
   - Recommendation: what to change or confirm

   Sections:

   - Correctness & risk
   - Testing & CI
   - Security & data protection
   - Performance & scalability
   - Observability & operations
   - Config, rollout, and rollback
   - Code quality & maintainability
   - Documentation & knowledge transfer

3. **Testing & CI details**
   - Verify that:
     - New or changed logic has at least one associated test (unit/integration/e2e as appropriate).
     - Tests are non-trivial (assert behavior, not just existence).
     - The test suite is not obviously flaky (no TODOs or `@ignore` on critical tests).
   - If gaps exist, propose:
     - Concrete test cases with example inputs/outputs.
     - Where to place them (file names and test descriptions).

4. **Security pass**
   - Scan for:
     - Hard-coded credentials, tokens, keys, or connection strings.
     - Relaxed authorization checks (e.g., removed guards, widened ACLs).
     - New external calls (APIs, queues, storage) without error handling or timeouts.
   - For each issue:
     - Mark `FAIL` if it exposes or weakens security.
     - Suggest remediation with concrete code patterns (e.g., move secret to env var, add auth check).

5. **Performance & hot paths**
   - Identify:
     - DB queries inside tight loops.
     - Large payload processing on request thread.
     - O(n²) or worse algorithms in potentially large collections.
   - If found:
     - Mark `WARN` or `FAIL` depending on impact.
     - Recommend specific optimizations (pagination, prefetching, indexing, caching).

6. **Observability & operations**
   - Check:
     - Important new branches and failure paths log something actionable (with IDs, not PII).
     - Metrics (counters/timers) exist for new critical operations, if the project uses metrics.
     - Any change that affects SLIs has a corresponding update to alerts or dashboards (if config in repo).
   - If missing, recommend:
     - Where to add logs/metrics.
     - Example log/metric messages or labels.

7. **Rollout & rollback**
   - If the change is high-risk (schema changes, auth changes, critical flows):
     - Check for feature flags or staged rollout mechanisms.
     - Check migration scripts for idempotency and rollback notes.
   - If no safe path:
     - Mark `FAIL` for “Rollout & rollback”.
     - Propose a concrete plan (e.g., “add feature flag X, default off; ship in two stages”).

8. **Documentation**
   - Verify:
     - Public behavior changes reflected in docs (API docs, changelog, README).
     - New configs/env vars documented with defaults and implications.
     - Operational runbooks updated if on-call procedures change.
   - If missing:
     - Mark `WARN` and provide bullet-point text the author can paste into docs.

9. **Overall verdict**

   The agent must output a final block:

   - `Overall status`: `READY`, `READY WITH WARNINGS`, or `NOT READY`
   - Blocking issues:
     - List all sections with `FAIL`, each with a one-line summary and links to details.
   - Non-blocking recommendations:
     - Short list of improvements that can be done now or post-merge.

***

## Example high-level prompt you can embed

> You are a senior engineer performing a production readiness review of this pull request.  
> Your goal is not just to catch bugs, but to ensure this change is safe, observable, secure, and operable in production.  
> Use the following checklist: Correctness & risk, Testing & CI, Security & data protection, Performance & scalability, Observability & operations, Config/rollout/rollback, Code quality & maintainability, Documentation & knowledge transfer.  
> For each section, return a status (PASS/WARN/FAIL), evidence (file and line references), and concrete recommendations.  
> End with an overall status (READY / READY WITH WARNINGS / NOT READY) and a short list of blocking vs non-blocking items.
