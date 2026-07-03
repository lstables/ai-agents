# Bug Fix Playbook

Use this playbook when the user asks to fix broken behaviour or a regression.

## Agent Sequence

1. Team Lead Agent: reproduce or describe the bug, identify suspected scope, and create/update a task file.
2. Senior Developer Agent: implement the smallest safe fix and add a regression test.
3. QA Agent: verify the original bug is covered and adjacent workflows still pass.
4. GitHub Reviewer Agent: review the PR for risk.

## Rules

- Prefer a failing test before the fix when practical.
- Keep the branch focused on the bug.
- Do not refactor unrelated code.
- Run `composer test`, `npm run typecheck`, and `npm run build` before PR.

## Short Prompt Form

```text
Fix <bug> using the bug-fix playbook. Use issue #<number> if available. Do not merge.
```
