---
name: feature-builder
description: Implement new features or enhancements in your app from a natural‑language spec, then prepare the changes for review by your production‑readiness and fixer agents.
---

## Feature Builder

### Purpose

Implement new features or enhancements in your app from a natural‑language spec, then prepare the changes for review by your production‑readiness and fixer agents.

### When to run

- You have a clearly described feature or change request (user story, bug, or refactor).
- The repo already has your review + fixer skills configured and passing.
- You want Oz to do the bulk of the implementation in a branch/PR, not just cleanup. [warp](https://www.warp.dev/blog/oz-orchestration-platform-cloud-agents)

### Inputs

- Feature spec (markdown or plain text): behavior, UX, constraints, acceptance criteria.
- Repo + environment (Oz environment pointing at your Next.js repo, with tests/tooling ready). [docs.warp](https://docs.warp.dev/agent-platform/cloud-agents/deployment-patterns)
- Optional:
  - Design docs, API contracts, example payloads.
  - A “done” checklist (e.g., routes added, tests updated, docs touched).

### Behavior

1. Understand and scope the feature  
   - Read the feature spec and summarize: goals, affected areas, and acceptance criteria.  
   - Identify which parts of the codebase are likely involved (pages/components/api/lib). [youtube](https://www.youtube.com/watch?v=I9BZRuw5c80)

2. Plan the implementation  
   - Draft a short plan (as a comment or markdown artifact) with steps: schema/contract changes, components, API routes, state management, tests, docs.  
   - Keep plan small enough to fit in a single PR unless explicitly told otherwise.

3. Implement the feature in code  
   - Create or update components, routes, and utilities as per the plan.  
   - Reuse existing patterns (styling, hooks, data fetching, folder structure) rather than inventing new architectures.  
   - Add or update tests (using your Vitest agent skill or directly writing tests) to cover new behavior and edge cases.

4. Run checks locally in the environment  
   - Run `pnpm/npm/yarn lint`, `test`, and `build` as appropriate.  
   - Fix obvious issues directly until the app builds and tests pass.

5. Prepare for production review  
   - Produce a concise implementation summary: files touched, main behaviors added, and any tradeoffs or TODOs.  
   - Open or update a branch/PR with the changes.

6. Hand off to your other agents  
   - Trigger the Production Readiness Code Review skill on the resulting PR.  
   - After that finishes, trigger the Code Fixer skill to resolve blocking issues.  
   - If the fixer makes substantial changes, optionally re‑run the review once. [warp](https://www.warp.dev/blog/oz-orchestration-platform-cloud-agents)
