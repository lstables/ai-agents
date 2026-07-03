# Issue 11: Reports Module

## Feature Summary

Build a read-only Reports module that aggregates data already captured by the other modules (Purchasing, Sales Orders, Inventory, Suppliers, Customers) into a single screen: purchasing/sales summaries, a low-stock inventory list, top-supplier/top-customer rankings, and an optional order-date range filter.

Source: [GitHub issue #11](https://github.com/lstables/ai-agents/issues/11) — "[Feature]: Reports". Authored as part of running `.ai/playbooks/feature-delivery.md`.

Unlike every prior module, this one has **no new database tables and no create/edit/delete** — it is a pure read/aggregation layer. The shape of the work (models/migrations, forms, CRUD tables) that fit Purchasing/Suppliers/Inventory/Customers/Sales Orders doesn't apply the same way here, so this task file adapts accordingly rather than forcing a mismatched template.

## Acceptance Criteria

- [ ] Purchasing summary: total purchase count, total spend (excluding cancelled), counts by status.
- [ ] Sales summary: total sales order count, total revenue (excluding cancelled), counts by status.
- [ ] Inventory summary: count of items at or below reorder level, plus the list of those items.
- [ ] Top 5 suppliers by total purchase spend, top 5 customers by total sales revenue.
- [ ] Optional `from`/`to` order-date range filter applied to purchasing and sales figures (inventory is a point-in-time snapshot, not date-filtered); defaults to all-time.
- [ ] All aggregation happens server-side; no roles/permissions required.
- [ ] Backend feature tests cover the aggregation math, cancelled-order exclusion, date filtering, and empty-data (no rows at all) edge cases.
- [ ] `composer test`, `npm run typecheck`, `npm run build` all pass — full suite, not just new tests.

## Agent Tasks

### Backend (no migrations — read-only aggregation)

- [ ] `App\Actions\Reports\BuildReportSummary`: a single action taking optional `from`/`to` dates and returning a plain array with `purchasing`, `sales`, `inventory`, `top_suppliers`, `top_customers` keys. Keeps all aggregation logic out of the controller, per `.ai/guidelines/laravel.md`.
  - Purchasing/sales totals and by-status counts: `whereNotIn('status', [STATUS_CANCELLED])` for the spend/revenue sums (status breakdown itself includes cancelled, so the count is visible even though it doesn't contribute to spend).
  - Top suppliers/customers: `groupBy` + `sum('total_amount')` + `orderByDesc` + `limit(5)`, joined back to supplier/customer name — exclude cancelled orders from the sum here too, for consistency with the summary totals.
  - Inventory: reuse `InventoryItem::belowReorderLevel()` (already exists from the Inventory module) rather than reimplementing the comparison.
- [ ] `ReportController@summary`: thin controller calling the action, returning a plain JSON array (no need for a Resource class — this isn't an Eloquent model, just a computed structure; keep it simple rather than forcing an unnecessary abstraction).
- [ ] No `ReportPolicy`/authorization check — there is no Eloquent model to authorize against, and every other read endpoint's policy already returns `true` unconditionally (no roles exist). Gating this behind a no-op policy would be a hollow abstraction; `ResolveDemoUser` already ensures every request has a resolved user, which is the only "authorization" this app currently has anywhere. Documented here explicitly so it isn't mistaken for an oversight.
- [ ] Register `GET /api/reports/summary` under the existing `Route::prefix('api')->middleware(ResolveDemoUser::class)` group.

### Vue/TypeScript

- [ ] `resources/js/types/reports.ts` and `resources/js/api/reports.ts` (typed response shape, `fetchReportSummary(from?, to?)`).
- [ ] `ReportsPage.vue`: a from/to date filter (re-fetches on change) plus five sections — purchasing summary cards, sales summary cards, a small inventory low-stock table, a top-suppliers table, a top-customers table. Reuse `PurchaseStatusBadge`/`SalesOrderStatusBadge`-style presentation only where it clearly fits (status breakdowns) — no new create form is needed since there is nothing to create.
- [ ] Wire the existing "Reports" nav item in `ErpDashboard.vue`.
- [ ] Handle loading, empty (no data at all — e.g. a fresh install with zero purchases/sales), and error states per `.ai/guidelines/vue-typescript.md`.

### Tests

- [ ] Purchasing/sales totals correctly exclude cancelled orders from spend/revenue but still count them in the by-status breakdown.
- [ ] Inventory low-stock count/list matches `belowReorderLevel()` exactly (including the "no reorder level set = never low" rule already established).
- [ ] Top suppliers/customers ranking is correctly ordered and capped at 5, and excludes cancelled-order amounts from the ranking sum.
- [ ] Date range filtering: orders outside `[from, to]` are excluded from purchasing/sales figures.
- [ ] Empty-data case: no purchases/sales/inventory at all returns zeros and empty arrays, not an error.
- [ ] Run `composer test`, `npm run typecheck`, `npm run build` — confirm the full suite is green.

### QA checks

- [ ] Verify the aggregation math by hand against seeded data via a live smoke test, not just unit tests.
- [ ] Check the date filter's boundary behavior (an order exactly on `from` or `to` should be included, not excluded).
- [ ] Check that a supplier/customer with zero orders doesn't appear in the top-N rankings (no divide-by-zero or spurious zero-amount rows).
- [ ] Check failed/empty/loading UI states on the reports screen.
- [ ] Flag any missing coverage and add focused tests where appropriate.

### Reviewer checks

- [ ] Confirm aggregation logic lives in the action, not the controller or Vue layer.
- [ ] Confirm cancelled orders are consistently excluded from every money total (spend, revenue, top-N rankings) but still visible in status breakdowns.
- [ ] Confirm the "no policy/no authorization" decision is a reasonable, documented choice rather than a security gap — there's genuinely nothing to authorize here beyond what `ResolveDemoUser` already provides.
- [ ] Confirm test coverage matches the acceptance criteria, especially the edge cases (empty data, date boundaries, cancelled exclusion).

## Assumptions

- No new database tables or models — this is a pure read/aggregation layer over existing tables.
- "Top 5" is a fixed, hardcoded limit for both rankings — no user-configurable page size, since this is a summary view, not a paginated list.
- The inventory section is a current-state snapshot and is not affected by the date range filter (stock levels don't have a meaningful "as of a past date" view in this app, since there's no stock-movement ledger).
- No policy class is created for this feature (see Agent Tasks above) — this is a deliberate choice, not an oversight, given there's no model to attach one to and no roles exist anywhere in this app yet.

## Risks

- As data volume grows, the aggregation queries (especially the top-N groupBy/sum queries) could become slow — explicitly out of scope to optimize now, per the issue's stated rollout risk, but worth revisiting if this app ever handles real production volumes.
- If a future issue adds roles/permissions, this endpoint will need a real authorization check retrofitted (same as every other endpoint in this app) — not just this one.

## Blocked Questions

None — the issue was authored with clear, unambiguous acceptance criteria; none of `.ai/playbooks/feature-delivery.md`'s stop conditions apply.

## Links

- Issue: https://github.com/lstables/ai-agents/issues/11
- Branch:
- PR:
- CI:
