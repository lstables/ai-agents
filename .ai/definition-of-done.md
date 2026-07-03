# Definition Of Done

A feature is not done until these checks are satisfied.

## Product

- Acceptance criteria are met.
- ERP workflow is understandable to the target user.
- Edge cases and empty states are handled.

## Backend

- Validation is server-side.
- Authorization is server-side.
- Business rules are tested.
- Database changes are reversible or clearly documented.

## Frontend

- Vue components are typed.
- Loading, empty, error, and forbidden states are handled where relevant.
- UI is consistent with the existing ERP shell.

## Quality

- `composer test` passes.
- `npm run typecheck` passes.
- `npm run build` passes.
- QA Agent review is complete.
- GitHub Reviewer Agent review is complete.

## Human Gates

- Human 1 approved business workflow.
- Human 2 approved technical quality.
- Human 3 approved QA, support, and rollout risk.

Agents must not merge to `main`.
