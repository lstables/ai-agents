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


## Examples

Simply use: `Build <feature> using the feature-delivery playbook. Create/use a GitHub issue, open a PR, run QA and reviewer passes, but do not merge.`

Shorter: `Build <feature> using the feature-delivery playbook. Do not merge.`

--

Or use each agent individually:

Run the Team Lead Agent to write out a plan

Use .ai/agents/team-lead.md and .ai/guidelines/*.

Read GitHub issue #:
https://github.com/lstables/ai-agents/issues/

Create or update .ai/tasks/issue-#.md.

Break the work into:
- Laravel models/migrations
- routes/controllers/requests
- Vue/TypeScript screens/components
- create form
- table with pagination, filtering, and search
- tests
- QA checks
- reviewer checks

Do not implement the feature yet. Only create the task plan.

---

Run the Developer Agent to write what was planned

Use .ai/agents/senior-developer.md, .ai/guidelines/*, and .ai/tasks/issue-#.md.

Implement GitHub issue #.

Create a new branch:
feature/issue-#

Build the Purchasing module:
- migration/model for purchases
- create form
- index table
- pagination
- filtering
- search
- Laravel validation
- tests for the feature

Run:
composer test
npm run typecheck
npm run build

Commit the work and open a pull request against main.

---

Run the QA Agent to check work carried out

Use .ai/agents/qa-agent.md and .ai/guidelines/*.

Review PR #<number> against GitHub issue # and .ai/tasks/issue-#.md.

Check:
- creation works
- validation is covered
- table search/filter/pagination works
- tests back up the feature
- no obvious regression risk

Add missing tests if needed.
Report blocking issues and residual risk.

---

Run the Reviewer Agent

Use .ai/agents/github-reviewer.md and .ai/guidelines/*.

Review PR #<number> as the GitHub reviewer agent.

Only comment on:
- bugs
- missing tests
- Laravel/Vue convention problems
- data integrity risks
- security issues
- maintainability problems

Use P0/P1/P2/P3 severity.
