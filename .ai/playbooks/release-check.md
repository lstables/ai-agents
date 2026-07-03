# Release Check Playbook

Use this playbook before shipping merged work.

## Agent Sequence

1. Release Manager pass: list merged PRs since the last release.
2. QA Agent: identify workflows that need smoke testing.
3. GitHub Reviewer Agent: check unresolved review comments or known risks.
4. Humans: approve release.

## Checklist

- CI is passing on `main`.
- Migrations are reviewed.
- Rollback notes exist for risky changes.
- Release notes are drafted.
- Support-impact notes are drafted.
- Human business, technical, and QA/ops owners have approved.

Agents must not deploy production automatically.
