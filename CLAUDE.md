# Claude Operating Instructions

This repository uses an AI-agent delivery workflow. When the user asks to build, fix, review, test, or plan work, do not treat the request as a single undifferentiated coding task.

Start by reading:

1. `.ai/agent-router.md`
2. the relevant file in `.ai/playbooks/`
3. the agent role files in `.ai/agents/`
4. the engineering guidelines in `.ai/guidelines/`
5. `.ai/definition-of-done.md`

## Default Behaviour

If the user says something like:

- "build me X"
- "add X"
- "create X"
- "implement X"
- "fix X"

then use `.ai/agent-router.md` to choose the right playbook.

For new product functionality, use `.ai/playbooks/feature-delivery.md`.

## Important Rules

- Humans approve merges. Agents do not merge to `main`.
- Keep each branch focused on one issue or task.
- Keep `.ai/tasks/issue-<number>-<slug>.md` as the shared source of truth.
- Run `composer test`, `npm run typecheck`, and `npm run build` before opening or updating a PR.
- Use `.ai/definition-of-done.md` to decide whether feature work is complete.
- If a request is ambiguous enough to risk building the wrong ERP workflow, stop after the Team Lead Agent pass and ask for clarification.
- If GitHub issue or PR numbers are available, use them. If not, create a local task file first and ask whether to create a GitHub issue.

## Canonical Agent Order For Features

1. Team Lead Agent
2. Senior Developer Agent
3. QA Agent
4. GitHub Reviewer Agent
5. Human review gates

Do not skip the Team Lead Agent for non-trivial ERP features.
