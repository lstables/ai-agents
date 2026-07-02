# GitHub Agent Command Comments

Use these comments on GitHub issues and pull requests to drive the workflow.

## Plan

```text
/ai plan
Use .ai/agents/team-lead.md and .ai/guidelines/*.
Create .ai/tasks/issue-<number>.md from this issue.
Identify backend, frontend, QA, review, migration, permission, and rollout tasks.
```

## Implement

```text
/ai implement
Use .ai/agents/senior-developer.md, .ai/tasks/issue-<number>.md, and .ai/guidelines/*.
Create a feature branch, implement the task, add tests, run checks, and open a PR.
```

## QA

```text
/ai qa
Use .ai/agents/qa-agent.md, the linked issue, and this PR.
Validate acceptance criteria, add missing tests where appropriate, and report residual risk.
```

## Review

```text
/ai review
Use .ai/agents/github-reviewer.md and review this PR.
Leave concrete comments only for bugs, security, permissions, data integrity, missing tests, or maintainability risks.
```
