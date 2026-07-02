# ERP Agent Orchestrator

A slim Laravel 13, Vue 3, TypeScript, and TailwindCSS ERP starter designed to be built through an AI agent delivery workflow.

The project includes:

- Laravel backend scaffold
- Vue/TypeScript/Tailwind operational dashboard shell
- AI agent role prompts in `.ai/agents`
- engineering guidelines in `.ai/guidelines`
- shared agent task templates in `.ai/tasks`
- GitHub issue and PR templates
- GitHub Actions CI
- GitHub bootstrap and agent command helpers

## Local Setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
npm run build
```

Run the app:

```bash
composer run dev
```

Run checks:

```bash
composer test
npm run typecheck
npm run build
```

## AI Delivery Workflow

1. Create a GitHub issue using the ERP Feature template.
2. Ask the Team Lead Agent to create `.ai/tasks/issue-<number>.md`.
3. Ask the Senior Developer Agent to implement the feature branch.
4. Ask the QA Agent to validate the PR and add missing tests.
5. Ask the GitHub Reviewer Agent to review the PR.
6. Require CI and all three human review gates before merge.

Copyable GitHub command comments are in `scripts/ai-orchestration/agent-comments.md`.

## GitHub Bootstrap

After authenticating `gh`, create the remote repo:

```bash
scripts/ai-orchestration/bootstrap-github.sh your-org/erp-agent-orchestrator
```

Then configure branch protection for `main`:

- require pull requests
- require CI
- require human reviews
- block direct pushes
- dismiss stale approvals after new commits

## Human Review Gates

- Human 1: business workflow approval
- Human 2: technical/code quality approval
- Human 3: QA/support/rollout approval
