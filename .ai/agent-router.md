# Agent Router

Use this file to decide which playbook and agents to run.

## New Feature

User wording:

- "build"
- "add"
- "create"
- "implement"
- "new module"
- "new workflow"
- "new feature"

Use:

- `.ai/playbooks/feature-delivery.md`

Agent order:

1. Team Lead Agent
2. Senior Developer Agent
3. QA Agent
4. GitHub Reviewer Agent

## Bug Fix

User wording:

- "fix"
- "broken"
- "not working"
- "regression"
- "error"

Use:

- `.ai/playbooks/bug-fix.md` when it exists
- otherwise use `.ai/playbooks/feature-delivery.md` but keep scope to the bug

Agent order:

1. Team Lead Agent for impact analysis
2. Senior Developer Agent for fix
3. QA Agent for regression tests
4. GitHub Reviewer Agent for PR review

## Review Only

User wording:

- "review this PR"
- "check this branch"
- "act as reviewer"

Use:

- `.ai/agents/github-reviewer.md`
- `.ai/guidelines/*`

Do not implement changes unless explicitly asked.

## QA Only

User wording:

- "test this"
- "QA this"
- "check coverage"
- "find edge cases"

Use:

- `.ai/agents/qa-agent.md`
- `.ai/guidelines/testing.md`
- `.ai/guidelines/erp-domain.md`

## Refactor

User wording:

- "refactor"
- "clean up"
- "restructure"

Use:

- `.ai/playbooks/refactor.md` when it exists
- otherwise run Team Lead Agent first and require a narrow scope before coding

## Release

User wording:

- "release"
- "deploy"
- "ship"
- "changelog"

Use:

- `.ai/playbooks/release-check.md` when it exists
- otherwise do not deploy automatically; prepare a release checklist only
