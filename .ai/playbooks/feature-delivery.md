# Feature Delivery Playbook

Use this playbook when the user asks to build a new feature or module.

The goal is to run a predictable sequence of agent roles, not to let one agent freestyle the whole job.

## Inputs

At least one of these should exist:

- a GitHub issue URL or number
- a clear feature brief from the user
- an existing `.ai/tasks/issue-<number>-<slug>.md` task file

If there is no GitHub issue, create a local task file first and ask whether a GitHub issue should be created.

## Tracking Progress On The Issue

The GitHub issue's "Acceptance criteria" checklist is the human-visible progress signal for this whole sequence — don't leave every box unchecked until a human has to read the PR/task file to find out what's actually done.

As soon as an acceptance criterion is genuinely verified true — by a passing test, a live smoke test, or direct inspection — check its box on the issue (`gh issue edit <number> --body-file <file>` with the box flipped from `- [ ]` to `- [x]`). Do this incrementally as work progresses, not all at once at the end:

- Senior Developer Agent checks off criteria its own implementation and tests directly prove.
- QA Agent checks off (or unchecks, if a regression is found) criteria it independently verified, including anything the Senior Developer Agent missed.
- Never check a box on the strength of "I wrote code for this" alone — only when it's actually been exercised (a test ran green, or a live check confirmed it).

## Agent Sequence

### 1. Team Lead Agent

Read:

- `.ai/agents/team-lead.md`
- `.ai/guidelines/*`
- GitHub issue, if provided
- existing task file, if present

Actions:

1. Understand the feature.
2. Inspect the existing code enough to identify affected modules.
3. Create or update `.ai/tasks/issue-<number>-<slug>.md`.
4. Split work into backend, frontend, tests, QA, reviewer, migration, permission, and rollout tasks.
5. Record assumptions, blocked questions, risks, and expected PR scope.

Stop here and ask humans if:

- acceptance criteria are missing
- permissions are unclear
- data migration or destructive changes are required
- the feature affects money, stock, accounting, or audit behaviour and the business rule is vague

Expected output:

- task file committed or ready to commit
- short summary of scope, assumptions, and risks

### 2. Senior Developer Agent

Read:

- `.ai/agents/senior-developer.md`
- `.ai/guidelines/*`
- the task file created by the Team Lead Agent
- related existing code

Actions:

1. Create a branch named `feature/issue-<number>-<slug>` when an issue number exists.
2. Implement only the agreed task scope.
3. Add migrations, models, controllers, requests, policies, Vue components, routes, and tests as needed.
4. Keep business rules on the Laravel side.
5. Keep Vue components typed.
6. Run:

```bash
composer test
npm run typecheck
npm run build
```

7. Commit changes.
8. Open or prepare a PR.
9. Check off each acceptance-criteria box on the GitHub issue that the tests just run actually prove — see "Tracking Progress On The Issue" above. Leave unproven criteria unchecked rather than guessing.

Expected PR body:

```md
## Summary

-

## Issue

Closes #

## Agent Work

- Team Lead task file:
- Developer implementation:
- Tests:

## Checks

- [ ] composer test
- [ ] npm run typecheck
- [ ] npm run build

## Risks And Assumptions

-
```

### 3. QA Agent

Read:

- `.ai/agents/qa-agent.md`
- `.ai/guidelines/testing.md`
- `.ai/guidelines/erp-domain.md`
- the issue
- the task file
- the PR diff

Actions:

1. Verify acceptance criteria — for each one, check its box on the GitHub issue if this pass independently confirms it (including any the Senior Developer Agent left unchecked), or uncheck it if a regression is found. See "Tracking Progress On The Issue" above.
2. Check permissions, validation, edge cases, empty states, error states, and regression risk.
3. Add focused tests if the gap is clear and low-risk.
4. Otherwise leave concrete QA findings.
5. Run relevant checks.

Output:

```md
## QA Result

### Behaviour Verified

-

### Tests Added

-

### Blocking Issues

-

### Non-Blocking Improvements

-

### Residual Risk

-
```

### 4. GitHub Reviewer Agent

Read:

- `.ai/agents/github-reviewer.md`
- `.ai/guidelines/*`
- the PR diff
- test results

Actions:

1. Review only changed behaviour.
2. Prioritise bugs, security, permission leaks, data integrity, missing tests, performance, and maintainability.
3. Leave comments using P0/P1/P2/P3 severity.
4. Avoid subjective style comments.

Output:

- PR comments, or a clear "no blocking findings" review summary

### 5. Bug Fix Agent (conditional)

Run this step automatically, without being asked, if QA reported a Blocking Issue or the GitHub Reviewer Agent reported any finding — any severity, P0 through P3 — that names a concrete code or test change. Do not stop at leaving comments when the finding is actually fixable.

Read:

- `.ai/agents/bug-fix-agent.md`
- the QA Result and GitHub Reviewer comments on the PR

Actions:

1. Fix each actionable finding with the smallest safe change.
2. Add or extend a regression test per finding fixed.
3. Leave findings that need a human judgment call (business decision, breaking change, out-of-scope) as-is, with a one-line reason.
4. Run `composer test`, `npm run typecheck`, `npm run build`.
5. Commit and push to the same PR branch.
6. Comment on the PR: what was fixed, what was left and why.

Skip this step entirely if QA and the Reviewer found nothing actionable — do not manufacture work.

### 6. Human Review Gates

Humans approve:

- business workflow
- technical quality
- QA/support/rollout risk

Agents must not merge.

## One-Shot Prompt For Claude

Use this when you want Claude to run the whole orchestrated sequence as far as it safely can:

```text
Use CLAUDE.md.

Build: <describe the feature>

If there is a GitHub issue, use issue #<number>.

Run the feature-delivery playbook:
1. Team Lead Agent creates or updates the task file.
2. Senior Developer Agent implements on a feature branch.
3. Run composer test, npm run typecheck, and npm run build.
4. Open or prepare a PR.
5. Run QA Agent review.
6. Run GitHub Reviewer Agent review.
7. If QA or the Reviewer found anything actionable (any bug, or any finding at P0-P3 with a concrete fix), run the Bug Fix Agent to fix it and push the fix to the same PR before stopping.

Stop for human clarification if business rules, permissions, data migration, accounting, stock, money, or audit behaviour are unclear.

Do not merge.
```

## Short Prompt Form

Once Claude has this repo loaded, this should be enough:

```text
Build <feature> using the feature-delivery playbook. Use issue #<number>. Do not merge.
```
