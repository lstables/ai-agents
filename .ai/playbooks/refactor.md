# Refactor Playbook

Use this playbook only when the user explicitly asks for a refactor.

## Agent Sequence

1. Team Lead Agent: define the refactor boundary, expected benefit, and regression risk.
2. Senior Developer Agent: make the smallest structural change that achieves the goal.
3. QA Agent: run tests and identify behavioural risk.
4. GitHub Reviewer Agent: review maintainability and regression risk.

## Rules

- Do not mix refactors with feature work.
- Preserve behaviour.
- Add tests first if behaviour is not already covered.
- Avoid large rewrites unless the human explicitly approves the scope.
