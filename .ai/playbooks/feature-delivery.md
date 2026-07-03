# Feature Delivery Playbook

Use this playbook when the user asks to build a new ERP feature or module.

The goal is to run a predictable sequence of agent roles, not to let one agent freestyle the whole job.

## Inputs

At least one of these should exist:

- a GitHub issue URL or number
- a clear feature brief from the user
- an existing `.ai/tasks/issue-<number>-<slug>.md` task file

If there is no GitHub issue, create a local task file first and ask whether a GitHub issue should be created.

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

1. Verify acceptance criteria.
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

### 5. Human Review Gates

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

Stop for human clarification if business rules, permissions, data migration, accounting, stock, money, or audit behaviour are unclear.

Do not merge.
```

## Short Prompt Form

Once Claude has this repo loaded, this should be enough:

```text
Build <feature> using the feature-delivery playbook. Use issue #<number>. Do not merge.
```
