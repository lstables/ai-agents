# Issue 5: Inventory Module

## Feature Summary

Build the Inventory module: a tabular list of inventory items with pagination, filtering, and search, plus full CRUD (create, edit, delete) via a form. This is the first inventory feature — no stock-movement/transaction ledger exists yet, only inventory item master data.

Source: [GitHub issue #5](https://github.com/lstables/ai-agents/issues/5) — "[Feature]: Inventory Module".

The raw issue gives minimal detail (business problem: "the inventory to work... tabular list, ability to CRUD"; business rules: "Laravel and Vue guidelines should be enforced, utilising Laravel Boost where possible"; acceptance criteria: "a full test suite run to be green"; explicitly no permissions/roles required). This task file fills the gaps with explicit assumptions below — see **Blocked Questions** for anything that should still be confirmed with a human.

**Laravel Boost note:** unlike the ambiguous "Laravel boost" wording in issues #1 and #3, this session has confirmed what it refers to — the `laravel-boost` Claude Code plugin (visible as a loaded plugin directory and an `artisan boost:mcp` process for this project). Its MCP tools were not connected/available when this plan was written (a `ToolSearch` for its tools returned nothing). The Senior Developer Agent should re-check for Boost's MCP tools (e.g. via `ToolSearch`) before implementing and use them where available (e.g. querying the app's actual routes/schema/logs instead of guessing) — but the plan below does not depend on Boost being available, since it may not be connected when implementation happens either.

## Acceptance Criteria

- [ ] An inventory items table lists existing items with server-side pagination.
- [ ] The table supports filtering (at minimum: below-reorder-level items).
- [ ] The table supports search (at minimum: by name and SKU).
- [ ] A user can create a new inventory item via a form (SKU, name, description, quantity on hand, reorder level, unit).
- [ ] A user can edit an existing inventory item.
- [ ] A user can delete an inventory item, with a data-integrity guard against deleting items already referenced elsewhere (see Blocked Questions — no such reference exists yet in this codebase, so this is likely a no-op guard today, added for forward-compatibility).
- [ ] Quantity on hand and reorder level cannot be negative.
- [ ] SKU is unique.
- [ ] All write actions are server-side validated; no roles/permissions are required (per the issue), matching the existing no-roles-yet stance used by Purchasing/Suppliers.
- [ ] Backend feature tests cover creation, validation failures, listing, pagination, filtering, search, update, and delete.
- [ ] `composer test`, `npm run typecheck`, and `npm run build` all pass — the issue's acceptance criterion is explicitly "a full test suite run to be green," so this includes the existing Purchasing/Suppliers suite continuing to pass, not just new Inventory tests.
- [ ] The PR states what was tested and what was not tested.

## Agent Tasks

### Laravel models/migrations

- [ ] Senior Developer Agent: add `inventory_items` migration — `sku` (string, unique), `name` (string), `description` (nullable text), `quantity_on_hand` (integer, default 0), `reorder_level` (nullable integer), `unit` (nullable string, e.g. "each"/"kg" — free text is fine for v1), timestamps. Reversible migration.
- [ ] Senior Developer Agent: add `InventoryItem` Eloquent model with explicit `#[Fillable]` (matching the `Supplier`/`Purchase` attribute-based convention already in this codebase), and a computed/query helper for "below reorder level" (e.g. a local scope) so it isn't reimplemented ad hoc in the controller.
- [ ] Senior Developer Agent: add `InventoryItemFactory` for test data.
- [ ] Senior Developer Agent: put any non-trivial calculation (e.g. "is below reorder level") in the model or a small query scope, not duplicated in the controller and the Vue table — per `.ai/guidelines/erp-domain.md`, stock-affecting rules belong in Laravel with tests.

### Routes/controllers/requests

- [ ] Senior Developer Agent: add `InventoryItemController` with `index` (paginate/filter/search), `store`, `update`, `destroy` — mirror `SupplierController`'s shape exactly (same pagination clamp, same search pattern) rather than inventing a new convention.
- [ ] Senior Developer Agent: add `StoreInventoryItemRequest` / `UpdateInventoryItemRequest` validating `sku` (required, unique — excluding self on update), `name` (required), `quantity_on_hand` (required, integer, min:0), `reorder_level` (nullable, integer, min:0), `unit`/`description` (nullable, string).
- [ ] Senior Developer Agent: add an `InventoryItemPolicy` — per the issue, no roles/permissions are required, so mirror `SupplierPolicy`'s current stance (all gates return `true`) rather than skipping authorization structure entirely, so a real policy can be dropped in later without restructuring.
- [ ] Senior Developer Agent: add `InventoryItemResource` for a stable, typed API response shape (include a computed `is_below_reorder_level` boolean so the Vue table doesn't reimplement that comparison).
- [ ] Senior Developer Agent: register routes under the existing `Route::prefix('api')->middleware(ResolveDemoUser::class)` group in `routes/web.php` — this app has no login flow and no `auth` middleware (see `.ai/tasks/issue-001-purchasing-module.md`'s `ResolveDemoUser` note); do not reintroduce an auth barrier for this module.
- [ ] Senior Developer Agent: implement pagination/filtering/search directly in the controller/query builder, not client-side.

### Vue/TypeScript screens/components

- [ ] Senior Developer Agent: add `resources/js/types/inventory.ts` and `resources/js/api/inventory.ts`, matching the existing `suppliers.ts` shape (typed request/response, `PaginatedResponse<T>` reuse from `types/purchases.ts` rather than redefining it).
- [ ] Senior Developer Agent: add `InventoryPage.vue` / `InventoryTable.vue` / `InventoryItemForm.vue`, reusing the Suppliers module's structure (list + toggleable create/edit form + delete with confirm) since it's the closest existing precedent for full CRUD in this codebase.
- [ ] Senior Developer Agent: wire the existing "Inventory" nav item in `ErpDashboard.vue` (currently a placeholder showing "has not been built yet") to `InventoryPage.vue`.
- [ ] Senior Developer Agent: handle loading, empty, error, and forbidden states per `.ai/guidelines/vue-typescript.md` (forbidden state can be a thin stub for now, consistent with the no-roles stance, but the state should exist so it isn't a gap when roles are added later).

### Create form

- [ ] Senior Developer Agent: build a create/edit inventory item form (SKU, name, description, quantity on hand, reorder level, unit) reusing the Suppliers `SupplierForm.vue` pattern (one component for both create and edit, driven by an optional `item` prop).
- [ ] Senior Developer Agent: client-side validation is UX-only; server response validation errors are the source of truth and must be surfaced per-field.
- [ ] Senior Developer Agent: disable submit while in flight and show a clear success/failure state after submit.

### Table with pagination, filtering, and search

- [ ] Senior Developer Agent: build the inventory table bound to the paginated API response with page controls.
- [ ] Senior Developer Agent: add a "below reorder level" filter toggle that re-queries the server.
- [ ] Senior Developer Agent: add a debounced search input (by name/SKU) that re-queries the server, matching `SuppliersTable.vue`'s existing debounce pattern.
- [ ] Senior Developer Agent: add edit/delete actions per row, matching `SuppliersTable.vue`'s pattern (including the delete confirmation dialog).

### Tests

- [ ] Senior Developer Agent: backend feature tests — create (happy path), validation failures (missing name, duplicate SKU, negative quantity/reorder level), listing with pagination, filtering by below-reorder-level, searching by name/SKU, update, delete, and guest/no-session behaviour consistent with `ResolveDemoUser` (i.e. requests without a session still succeed as the demo user — do not write a "guests are rejected" test, since that is not how this app works; see `.ai/tasks/issue-001-purchasing-module.md`).
- [ ] Senior Developer Agent: run `composer test`, `npm run typecheck`, and `npm run build` before opening the PR, and confirm the *entire* suite is green (Purchasing + Suppliers + Inventory), since the issue's acceptance criterion is explicitly about the full suite, not just new tests.

### QA checks

- [ ] QA Agent: verify acceptance criteria end-to-end (create, edit, delete, list, filter, search, pagination), not just via unit tests.
- [ ] QA Agent: check validation edge cases — duplicate SKU, negative quantity/reorder level, zero quantity (should be allowed — an item can be out of stock), very long name/description.
- [ ] QA Agent: check the below-reorder-level filter combined with search and pagination together, the same way prior QA passes checked combined filters for Purchasing.
- [ ] QA Agent: check delete behaviour once any future feature references `InventoryItem` (e.g. if Purchasing is later linked to inventory) — flag if the delete guard is untested because nothing references inventory items yet.
- [ ] QA Agent: check failed/empty/loading UI states on both the form and the table.
- [ ] QA Agent: flag any missing automated test coverage found during manual/live verification and add focused tests where appropriate.
- [ ] QA Agent: report residual risk, especially anything deferred from Blocked Questions below.

### Reviewer checks

- [ ] GitHub Reviewer Agent: confirm validation logic doesn't live only in the frontend.
- [ ] GitHub Reviewer Agent: confirm the migration is reversible and the model uses an explicit `#[Fillable]`.
- [ ] GitHub Reviewer Agent: confirm controllers are thin and mirror the existing `SupplierController` pagination/search convention rather than diverging.
- [ ] GitHub Reviewer Agent: confirm pagination/filtering/search happen server-side.
- [ ] GitHub Reviewer Agent: confirm test coverage matches the acceptance criteria, and that the full suite (not just new tests) is verified green.
- [ ] GitHub Reviewer Agent: confirm the PR states what was tested and what was not.

## Assumptions

- Inventory items are standalone master data for this issue — no stock-movement/transaction ledger, no linkage to Purchasing (e.g. purchase receipt does not adjust `quantity_on_hand`). That would be a separate, larger feature; this issue is scoped to CRUD + listing only, matching how Purchasing (#1) and Suppliers (#3) were each scoped narrowly.
- No roles/permissions are required (explicitly stated in the issue) — matches the existing `SupplierPolicy`/`PurchasePolicy` stance of returning `true` for all gates.
- No authentication exists in this app (removed entirely per `.ai/tasks/issue-001-purchasing-module.md`'s `ResolveDemoUser` decision) — Inventory routes should use the same `ResolveDemoUser` middleware group, not reintroduce `auth`.
- `unit` (each/kg/box/etc.) is free-text for v1, not a controlled list — no requirement was given for unit-of-measure standardization.
- "Ability to CRUD" means create, edit, and delete are all in scope for this issue (unlike Purchasing, which was create+list only) — full CRUD, matching how Suppliers (#3) was scoped.

## Risks

- If a future feature links Purchasing to Inventory (e.g. receiving a purchase increments stock), the `quantity_on_hand` field and any delete guard added now may need to change — flagged so that work isn't mistaken for a bug in this issue's scope.
- "Laravel Boost" tooling was unavailable when this plan was written; if it remains unavailable during implementation, the plan defaults to matching existing repo conventions (Supplier/Purchase code) instead, which should be equally valid but won't benefit from whatever Boost-specific guidance it might otherwise provide.
- Reusing the Suppliers CRUD pattern closely (recommended above for consistency) means any latent issues already known in that pattern — e.g. the delete-guard TOCTOU race and the stale-object-on-edit gap noted in the PR #4 review — would likely be reproduced here too unless deliberately addressed during implementation.

## Blocked Questions

- Should deleting an inventory item ever be blocked (e.g. once something references it), or is delete always safe for now given nothing currently links to `InventoryItem`? Assumed: allow delete unconditionally for v1, since no other table references inventory items yet.
- Is quantity tracked as a whole number (integer) or can inventory be fractional (e.g. weight-based stock in kg)? Assumed: integer for v1; revisit if a real business case for fractional stock arises.
- Should low-stock ("below reorder level") surface anywhere outside the Inventory table itself (e.g. the dashboard's existing "Stock exceptions" metric, which is currently hardcoded placeholder data in `DashboardHome.vue`)? Out of scope for this issue unless confirmed.

## Links

- Issue: https://github.com/lstables/ai-agents/issues/5
- Branch:
- PR:
- CI:
