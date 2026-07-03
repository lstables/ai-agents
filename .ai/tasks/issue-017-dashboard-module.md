# Issue #17: Dashboard Module

GitHub issue: https://github.com/lstables/ai-agents/issues/17
Branch: `feature/issue-17-dashboard-module`

## Summary

Replace the hardcoded/fake content in `DashboardHome.vue` (the app's default landing view) with a real, data-driven overview: current status across Purchasing/Sales/Inventory (reusing the Reports module's aggregation) plus a 30-day activity trend graph. Add hand-rolled SVG charts — no new dependency.

## Scope

### Backend

- `App\Actions\Reports\BuildActivityTrend`: given a number of days (default 30), return per-day purchase and sales-order counts/totals for the last N days, zero-filled for days with no orders, excluding cancelled orders from both counts and totals (same convention as `BuildReportSummary`).
- `App\Http\Controllers\DashboardController@overview`: composes `BuildReportSummary` (unfiltered — no date range) and `BuildActivityTrend` (30 days) into one JSON payload: `{ summary: {...same shape as /api/reports/summary...}, trend: {...} }`.
- New route: `GET /api/dashboard/overview`, grouped with the other `ResolveDemoUser`-gated API routes. No policy/authorization needed — same reasoning as Reports (no model to authorize against).

### Frontend

- `resources/js/api/dashboard.ts`: `fetchDashboardOverview()` using the existing `apiRequest` client.
- `resources/js/types/dashboard.ts`: `DashboardOverview` type (reusing `ReportSummary` type for the `summary` key, adding a `Trend` type for the daily series).
- Two small, reusable hand-rolled SVG chart components (no new npm dependency):
  - `resources/js/components/charts/BarChart.vue` — vertical/horizontal bars from a `Record<string, number>` or labelled list, used for order-status breakdowns.
  - `resources/js/components/charts/TrendLineChart.vue` — SVG polyline for the 30-day daily counts (purchases vs sales orders as two series).
- Rewrite `DashboardHome.vue`: fetch on mount, `loading`/`ready`/`error` states matching `ReportsPage.vue`'s pattern, render:
  - Stat cards: open purchase orders, total spend, open sales orders, total revenue, low-stock count (from `summary`).
  - Bar chart(s) for purchase/sales status breakdown.
  - Trend line chart for the last 30 days of order activity.
  - Keep it a read-only overview — no new interactive workflow.
- No nav change. Dashboard stays the default view (`activeModule === null`) in `ErpDashboard.vue`.

### Tests

- Feature tests for `BuildActivityTrend`: zero-fill for empty days, correct daily aggregation, cancelled orders excluded from count and total, correct day-count for a given `$days` argument.
- Feature test for `DashboardController@overview`: response shape includes both `summary` and `trend`, matches `BuildReportSummary`/`BuildActivityTrend` output for a seeded scenario.
- No Vue component tests, consistent with the rest of the app (typecheck + build are the frontend gate).

## Assumptions (locked in, no need to re-ask)

- Dashboard is the existing default/home view, not a new sidebar module.
- Dashboard is a fixed "at a glance" view (no user-configurable date range) — that's what Reports is for.
- Trend window is a fixed 30 days.
- Cancelled orders excluded from trend counts/totals, matching the existing Reports convention (visible in status terms elsewhere, but not part of "activity" here since the point of the trend is order volume that actually stuck).
- No new charting library — hand-rolled SVG components, consistent with this app's existing zero-extra-dependency posture.

## Risks

- None affecting money/stock/audit behaviour — this module is read-only and purely presentational/aggregation, same risk profile as Reports.

## Blocked Questions

None.
