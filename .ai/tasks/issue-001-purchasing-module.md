# Issue 1: Build the Purchasing Module

## Feature Summary

Build the first version of the Purchasing module: a form to create a new purchase, and a table listing purchases that supports pagination, filtering, and search. The issue requires the implementation to follow Laravel conventions/Boost guidance and this repo's `.ai/guidelines/*`, and to ship with passing automated tests.

Source: [GitHub issue #1](https://github.com/lstables/ai-agents/issues/1) — "[Feature]: Build the Purchasing module".

The raw issue gives almost no data model, permission, or rollout detail (business rules: "must enforce Laravel boost and guidelines"; acceptance criteria: "passing tests to back up the feature"; permissions/data/rollout: not specified). This task file fills those gaps with explicit assumptions below so implementation is not blocked — see **Blocked Questions** for items that should still be confirmed with a human before or during implementation.

## Acceptance Criteria

- [ ] A user can create a new purchase via a form (supplier, order date, one or more line items, notes).
- [ ] A purchase is persisted with a status (at minimum: draft/pending, approved, received, cancelled).
- [ ] A purchases table lists existing purchases with server-side pagination.
- [ ] The purchases table supports filtering (at minimum: by status and supplier).
- [ ] The purchases table supports search (at minimum: by purchase reference/number and supplier name).
- [ ] All write actions are server-side validated and authorized; the frontend cannot bypass validation or authorization.
- [ ] Backend feature tests cover creation, validation failures, listing, pagination, filtering, and search.
- [ ] `composer test`, `npm run typecheck`, and `npm run build` all pass in CI.
- [ ] The PR states what was tested and what was not tested.

## Agent Tasks

### Laravel models/migrations

- [ ] Senior Developer Agent: add `Supplier` model + migration if one does not already exist on the branch (`name`, nullable `email`/`phone`, timestamps). Purchasing depends on suppliers existing; keep this minimal and scoped to what Purchasing needs, per `.ai/guidelines/erp-domain.md` module boundaries.
- [ ] Senior Developer Agent: add `purchases` migration — `supplier_id` (FK), `reference` (unique, human-readable), `status` (string/enum: `draft`, `pending`, `approved`, `received`, `cancelled`), `order_date`, `expected_date` (nullable), `notes` (nullable), `total_amount` (decimal, derived from line items), `created_by` (FK to users), timestamps. Reversible migration.
- [ ] Senior Developer Agent: add `purchase_items` migration — `purchase_id` (FK, cascade delete with parent), `description`, `quantity` (decimal/int), `unit_price` (decimal), `line_total` (decimal), timestamps.
- [ ] Senior Developer Agent: add `Purchase` and `PurchaseItem` Eloquent models with explicit `$fillable`, relationships (`Purchase belongsTo Supplier`, `hasMany PurchaseItem`), and a status enum/constants list.
- [ ] Senior Developer Agent: add `PurchaseFactory`, `PurchaseItemFactory`, and `SupplierFactory` for test data.
- [ ] Senior Developer Agent: put total-amount calculation in a service/action class, not in the controller or model boot logic, so it is directly unit-testable (per `.ai/guidelines/laravel.md`).

### Routes/controllers/requests

- [ ] Senior Developer Agent: add `PurchaseController` with `index`, `store`, `show`, `update` (edit not explicitly required by the issue but needed to correct a draft), and a status-change action (e.g. cancel) — thin controller, business logic delegated to services.
- [ ] Senior Developer Agent: add `StorePurchaseRequest` and `UpdatePurchaseRequest` Form Requests validating supplier existence, non-empty line items, non-negative quantity/price, and required order date.
- [ ] Senior Developer Agent: add a `PurchasePolicy` (or gate) for create/update/view/cancel — even though the issue defines no roles yet, all write routes must require authentication and pass through a policy check per `.ai/guidelines/security.md`. Default policy can allow any authenticated user until roles are defined (see Blocked Questions).
- [ ] Senior Developer Agent: add `PurchaseResource`/`PurchaseItemResource` API resources so the Vue frontend has a stable, typed response shape.
- [ ] Senior Developer Agent: register routes (decide API vs web routes — see Blocked Questions) with `auth` middleware applied.
- [ ] Senior Developer Agent: implement listing query support for pagination (`per_page`/page params), filtering (`status`, `supplier_id`), and search (`reference`, supplier name) directly in the controller/query builder — do not filter/paginate client-side.

### Vue/TypeScript screens/components

- [ ] Senior Developer Agent: decide and document minimal client-side routing approach, since the app currently mounts a single `ErpDashboard.vue` with no router (see Blocked Questions). Add `vue-router` (or equivalent) only if needed to navigate between the dashboard and the new Purchases screens.
- [ ] Senior Developer Agent: add a typed API client/composable (e.g. `usePurchases.ts`) wrapping fetch calls to the purchase endpoints, with request/response TypeScript types matching the API resources.
- [ ] Senior Developer Agent: add a `PurchasesIndex.vue` (or similarly named) screen and a `PurchaseStatusBadge.vue` component; keep styling consistent with existing Tailwind usage in `ErpDashboard.vue` (per `.ai/guidelines/tailwind.md`).
- [ ] Senior Developer Agent: handle loading, empty, error, and forbidden states on all data-driven screens (per `.ai/guidelines/vue-typescript.md`).

### Purchase create form

- [ ] Senior Developer Agent: build a create-purchase form (supplier select, order date, notes, dynamic line-item rows with add/remove, computed running total).
- [ ] Senior Developer Agent: client-side validation is UX-only (inline field errors); server response validation errors must be surfaced and must be the source of truth.
- [ ] Senior Developer Agent: disable submit while in flight and show a clear success/failure state after submit.

### Purchase table with pagination, filtering, and search

- [ ] Senior Developer Agent: build the purchases list table bound to the paginated API response (page controls, page size if applicable).
- [ ] Senior Developer Agent: add filter controls (status, supplier) that re-query the server rather than filtering the already-fetched page.
- [ ] Senior Developer Agent: add a debounced search input that re-queries the server.
- [ ] Senior Developer Agent: reflect current filter/search/page state in a way that survives a page reload if practical (e.g. query string) — nice-to-have, not blocking.

### Tests

- [ ] Senior Developer Agent: backend feature tests — create purchase (happy path), validation failures (missing supplier, empty line items, negative price/quantity), listing with pagination, filtering by status/supplier, searching by reference/supplier name, authorization failure for unauthenticated requests.
- [ ] Senior Developer Agent: unit test the total-amount calculation service in isolation.
- [ ] Senior Developer Agent: run `composer test`, `npm run typecheck`, and `npm run build` before opening the PR and report results in the PR description (per `.ai/guidelines/testing.md` and `.ai/agents/senior-developer.md`).

### QA checks

- [ ] QA Agent: verify acceptance criteria above are actually met end-to-end, not just unit-tested.
- [ ] QA Agent: check validation edge cases — zero/negative quantities or prices, missing supplier, very large line-item counts, decimal rounding on totals.
- [ ] QA Agent: check pagination/filter/search combinations (e.g. filter + search + page 2 together) do not silently drop results or double-count.
- [ ] QA Agent: check authorization — an unauthenticated or unauthorized user cannot create/view/update purchases via direct API calls, even if the UI hides the controls.
- [ ] QA Agent: check failed/empty/loading UI states on both the form and the table.
- [ ] QA Agent: flag any missing automated test coverage found during manual checks and add focused tests where appropriate.
- [ ] QA Agent: report residual risk, especially anything deferred from Blocked Questions below.

### Reviewer checks

- [ ] GitHub Reviewer Agent: confirm no authorization or validation logic lives only in the frontend.
- [ ] GitHub Reviewer Agent: confirm migrations are reversible and models use explicit fillable/guarded.
- [ ] GitHub Reviewer Agent: confirm controllers are thin and business/calculation logic is in services/actions.
- [ ] GitHub Reviewer Agent: confirm pagination/filtering/search happen server-side, not client-side over a full result set.
- [ ] GitHub Reviewer Agent: confirm test coverage matches the acceptance criteria (creation, validation, listing, pagination, filtering, search, authorization).
- [ ] GitHub Reviewer Agent: confirm the PR states what was tested and what was not.

## Assumptions

- A `Supplier` model does not yet exist in the codebase and will be added minimally as part of this issue, scoped only to what Purchasing needs (name + contact fields). A full Suppliers module (per `.ai/guidelines/erp-domain.md`) is out of scope here.
- Purchases have a status lifecycle of at least `draft`, `pending`, `approved`, `received`, `cancelled`; the issue does not specify workflow states, so this is the minimum needed to make "list/filter by status" meaningful.
- No roles/permissions are defined yet, so any authenticated user may create/view/update purchases; a policy stub is added now so real role checks can be dropped in later without restructuring the code.
- **Update (post-implementation):** this app has no login UI, and per explicit human direction requiring a login flow "shouldn't be a thing for this demo app." Rather than removing authorization entirely, `App\Http\Middleware\ResolveDemoUser` resolves every request as a single demo user (no session/cookie required) so audit fields (`Purchase::created_by`) and policy checks still have a real user to work with. There is no unauthenticated state in this app anymore — the `auth` middleware and the earlier `/login` stub route were removed. This is a demo-only simplification, not a security recommendation; replace `ResolveDemoUser` with real authentication before this goes anywhere near production or multi-user use.
- No routing library or HTTP client is currently in the frontend (`resources/js/app.ts` mounts a single component with no router, no axios in `package.json`). This issue may need to introduce one; this is called out as a decision point, not silently assumed away.
- Reporting/export and audit logging are out of scope for this issue; `.ai/guidelines/security.md` audit-logging requirement applies once audit infrastructure exists, which it does not yet.

## Risks

- Building Supplier as a side-effect of the Purchasing issue risks scope creep or duplicate work if a Suppliers module is planned separately (see `.ai/guidelines/erp-domain.md` module list, and the existing unrelated `.ai/tasks/issue-001-supplier-credit-limit.md` task file, which also assumes a Supplier model).
- No authentication/session scaffolding has been inspected yet beyond `laravel/framework` defaults; if the app has no login flow wired up, "authenticated user" tests will need a decision on how test users authenticate (e.g. `actingAs`). Resolved: see the `ResolveDemoUser` note under Assumptions — real `actingAs()`-based tests still work unchanged since the middleware only kicks in when no user is already authenticated.
- With `ResolveDemoUser` in place, every request — including from any script, not just the browser UI — silently succeeds as the demo user. There is currently no way to reject a request at the application layer. This is fine for a local demo but must not ship anywhere with real network exposure.
- Introducing client-side routing (vue-router) is an architectural change beyond just "add a screen" — it touches `app.ts` and the single mount point used by `ErpDashboard.vue`.
- Without defined roles, shipping "any authenticated user can approve/cancel a purchase" may need to be revisited before this reaches real usage.
- Decimal handling for money/quantity fields must avoid float rounding errors; use Laravel decimal casts, not floats.

## Blocked Questions

- Should purchases live under versioned JSON API routes (`/api/purchases`) or Blade+session web routes? This affects auth middleware, CSRF handling, and the Vue HTTP client choice.
- Are roles/permissions (e.g. requester vs. approver) coming in a follow-up issue, or should a basic role check be built now even though the issue didn't specify one?
- What does "Laravel boost" in the issue's business rules refer to — the Laravel Boost tooling/conventions, or a typo for "Laravel [best practices] and guidelines"? No `laravel/boost` package is currently in `composer.json`. Confirm before implementation so the Senior Developer Agent knows whether a specific tool/convention set must be installed and followed.
- Does "purchase" mean a purchase order sent to a supplier (pre-receipt), or does this issue also cover receiving/goods-in? Scoped here to order creation + listing only; receiving workflow assumed out of scope.
- Any reporting/export requirement (CSV, etc.) for the purchases table, or is in-app pagination/filter/search sufficient for v1?

## Links

- Issue: https://github.com/lstables/ai-agents/issues/1
- Branch:
- PR:
- CI:
