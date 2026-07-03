# Issue 7: Customers Module

## Feature Summary

Build the Customers module: a listing of customers with server-side pagination and search, plus full CRUD (create, edit, delete) via a form. This mirrors the existing Suppliers module closely, since both are simple contact/master-data records with the same shape (name, email, phone).

Source: [GitHub issue #7](https://github.com/lstables/ai-agents/issues/7) — "[Feature]: Customers". This issue was authored as part of running the `.ai/playbooks/feature-delivery.md` playbook end-to-end (Team Lead → Senior Developer → QA → GitHub Reviewer), per explicit instruction, rather than being a pre-existing human-filed issue like #1/#3/#5. Its acceptance criteria and scope were written to be unambiguous, so this task file does not need to fill in as many gaps as the earlier ones did.

## Acceptance Criteria

- [ ] A customers table lists existing customers with server-side pagination.
- [ ] The table supports search (at minimum: by name and email).
- [ ] A user can create a new customer via a form (name required; email/phone optional).
- [ ] A user can edit an existing customer.
- [ ] A user can delete a customer.
- [ ] Name is required; email, if provided, must be a valid email format.
- [ ] All write actions are server-side validated; no roles/permissions are required (per the issue), matching the existing no-roles-yet stance used by Purchasing/Suppliers/Inventory.
- [ ] Backend feature tests cover creation, validation failures, listing, pagination, search, update, and delete.
- [ ] `composer test`, `npm run typecheck`, and `npm run build` all pass.
- [ ] The PR states what was tested and what was not tested.

## Agent Tasks

### Laravel models/migrations

- [ ] Senior Developer Agent: add `customers` migration — `name` (string), `email` (nullable string), `phone` (nullable string), timestamps. Reversible migration. Deliberately the same shape as `suppliers` — no need to invent new fields the issue didn't ask for.
- [ ] Senior Developer Agent: add `Customer` Eloquent model with explicit `#[Fillable]` (matching the `Supplier`/`Purchase`/`InventoryItem` attribute-based convention already in this codebase).
- [ ] Senior Developer Agent: add `CustomerFactory` for test data.

### Routes/controllers/requests

- [ ] Senior Developer Agent: add `CustomerController` with `index` (paginate/search), `store`, `update`, `destroy` — mirror `SupplierController`'s shape exactly (same pagination clamp, same search-by-name-or-email pattern, same explicit `$this->authorize('delete', ...)` in `destroy`).
- [ ] Senior Developer Agent: add `StoreCustomerRequest` / `UpdateCustomerRequest` validating `name` (required), `email` (nullable, valid email format), `phone` (nullable, string) — matching `StoreSupplierRequest`/`UpdateSupplierRequest` exactly.
- [ ] Senior Developer Agent: add a `CustomerPolicy` mirroring `SupplierPolicy`'s current stance (all gates return `true` — no roles exist), so a real policy can be dropped in later without restructuring.
- [ ] Senior Developer Agent: add `CustomerResource` for a stable, typed API response shape.
- [ ] Senior Developer Agent: register routes under the existing `Route::prefix('api')->middleware(ResolveDemoUser::class)` group in `routes/web.php` — this app has no login flow and no `auth` middleware (see `.ai/tasks/issue-001-purchasing-module.md`'s `ResolveDemoUser` note); do not reintroduce an auth barrier for this module.
- [ ] Senior Developer Agent: implement pagination/search directly in the controller/query builder, not client-side.

### Vue/TypeScript screens/components

- [ ] Senior Developer Agent: add `resources/js/types/customers.ts` and `resources/js/api/customers.ts`, matching the existing `suppliers.ts` shape (typed request/response, reuse `PaginatedResponse<T>` from `types/purchases.ts` rather than redefining it).
- [ ] Senior Developer Agent: add `CustomersPage.vue` / `CustomersTable.vue` / `CustomerForm.vue`, reusing the Suppliers module's structure directly (list + toggleable create/edit form + delete with confirm) since it's the closest and most direct precedent for this exact shape of module.
- [ ] Senior Developer Agent: wire the existing "Customers" nav item in `ErpDashboard.vue` (currently a placeholder showing "has not been built yet") to `CustomersPage.vue`.
- [ ] Senior Developer Agent: handle loading, empty, error states per `.ai/guidelines/vue-typescript.md`. A distinct "forbidden" state is not required for this module either — the existing Suppliers/Inventory modules don't have one, and no roles exist yet to make it meaningful (tracked as a cross-module follow-up, not specific to Customers).

### Create form

- [ ] Senior Developer Agent: build a create/edit customer form (name, email, phone) reusing the `SupplierForm.vue` pattern (one component for both create and edit, driven by an optional `customer` prop).
- [ ] Senior Developer Agent: client-side validation is UX-only; server response validation errors are the source of truth and must be surfaced per-field.
- [ ] Senior Developer Agent: disable submit while in flight and show a clear success/failure state after submit.

### Table with pagination, filtering, and search

- [ ] Senior Developer Agent: build the customers table bound to the paginated API response with page controls.
- [ ] Senior Developer Agent: add a debounced search input (by name/email) that re-queries the server, matching `SuppliersTable.vue`'s existing debounce pattern.
- [ ] Senior Developer Agent: add edit/delete actions per row, matching `SuppliersTable.vue`'s pattern (including the delete confirmation dialog).
- [ ] No filtering beyond search is required by the issue (unlike Inventory's below-reorder-level filter) — customers have no comparable status/threshold field yet.

### Tests

- [ ] Senior Developer Agent: backend feature tests — create (happy path), validation failures (missing name, invalid email), listing with pagination, searching by name/email, update, delete, and no-session-succeeds-as-demo-user behaviour consistent with `ResolveDemoUser` (do not write a "guests are rejected" test — that is not how this app works; see `.ai/tasks/issue-001-purchasing-module.md`).
- [ ] Senior Developer Agent: run `composer test`, `npm run typecheck`, and `npm run build` before opening the PR and confirm the entire suite is green, not just the new Customer tests.

### QA checks

- [ ] QA Agent: verify acceptance criteria end-to-end (create, edit, delete, list, search, pagination), not just via unit tests.
- [ ] QA Agent: check validation edge cases — missing name, invalid email format, very long name.
- [ ] QA Agent: check search combined with pagination.
- [ ] QA Agent: check failed/empty/loading UI states on both the form and the table.
- [ ] QA Agent: flag any missing automated test coverage found during manual/live verification and add focused tests where appropriate.
- [ ] QA Agent: report residual risk.

### Reviewer checks

- [ ] GitHub Reviewer Agent: confirm validation logic doesn't live only in the frontend.
- [ ] GitHub Reviewer Agent: confirm the migration is reversible and the model uses an explicit `#[Fillable]`.
- [ ] GitHub Reviewer Agent: confirm the controller is thin and mirrors the existing `SupplierController` pagination/search convention rather than diverging.
- [ ] GitHub Reviewer Agent: confirm pagination/search happen server-side.
- [ ] GitHub Reviewer Agent: confirm test coverage matches the acceptance criteria, and that the full suite is verified green.
- [ ] GitHub Reviewer Agent: confirm the PR states what was tested and what was not.

## Assumptions

- Customers are standalone contact/master data for this issue — no linkage to a future Sales Orders module yet (the issue mentions Sales Orders only as future motivation, not current scope).
- No roles/permissions are required (explicitly stated in the issue) — matches the existing `SupplierPolicy`/`InventoryItemPolicy` stance of returning `true` for all gates.
- No authentication exists in this app (removed entirely per `.ai/tasks/issue-001-purchasing-module.md`'s `ResolveDemoUser` decision) — Customer routes use the same `ResolveDemoUser` middleware group, not a reintroduced `auth` gate.
- Deleting a customer is unconditional (no data-integrity guard), since nothing else in this codebase references `Customer` yet — same reasoning already used for `InventoryItem`.

## Risks

- If a future Sales Orders module links to Customers (e.g. an order references a customer), the delete behavior here may need a guard later — flagged so that future work isn't mistaken for a regression in this issue's scope.
- Because this issue was authored to closely mirror Suppliers, any latent issues already known in that pattern (the delete-guard TOCTOU race and the stale-object-on-edit gap noted in the PR #4 review) do not apply here directly since Customers has no delete guard — but the stale-object-on-edit-form gap in `SupplierForm.vue`'s pattern would reproduce identically in `CustomerForm.vue` if copied verbatim.

## Blocked Questions

None — this issue was written with clear, unambiguous acceptance criteria and no vague business rules, so no human clarification is needed before proceeding (per `.ai/playbooks/feature-delivery.md`'s stop conditions: none of them apply here).

## Links

- Issue: https://github.com/lstables/ai-agents/issues/7
- Branch:
- PR:
- CI:
