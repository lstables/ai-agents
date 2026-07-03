# AI Delivery Operating Model

This repo is designed to be built through controlled AI agent handoffs.

The agents do not replace human approval. They create focused branches, add tests, review pull requests, and report risk so the three human owners can make better merge decisions.

## Roles

- Team Lead Agent: decomposes a feature brief into implementation, QA, and review tasks.
- Senior Developer Agent: implements Laravel, Vue, TypeScript, and Tailwind changes on a feature branch.
- QA Agent: verifies behaviour, adds or requests tests, and checks edge cases.
- GitHub Reviewer Agent: reviews the PR as a strict code reviewer.
- Bug Fix Agent: fixes whatever QA or the Reviewer found, instead of leaving it as a comment for a human to act on. Runs automatically when there's something actionable; see `.ai/agents/bug-fix-agent.md`.

## Human Gates

- Human 1: business workflow approval.
- Human 2: technical/code quality approval.
- Human 3: QA, support, and rollout approval.

## Standard Flow

1. Human creates a GitHub issue with acceptance criteria.
2. Team Lead Agent creates `.ai/tasks/issue-<number>.md`.
3. Senior Developer Agent implements a feature branch and opens a PR.
4. QA Agent validates the PR and adds missing tests where appropriate.
5. GitHub Reviewer Agent leaves concrete review comments.
6. Bug Fix Agent fixes anything actionable from steps 4-5 and pushes to the same PR — humans review fixed/confirmed work, not a pile of open findings.
7. CI must pass.
8. Humans approve and merge.
