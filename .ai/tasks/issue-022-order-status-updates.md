# Issue #22: Purchase and Sales Order Status Updates

GitHub issue: https://github.com/lstables/ai-agents/issues/22
Branch: `feature/issue-22-order-status-updates`

## Summary

Add the ability to move a Purchase or Sales Order through its status workflow (and cancel it), with the legal-transition rule enforced authoritatively server-side.

## Scope

### Backend

- `Purchase::allowedNextStatuses(): array` and `SalesOrder::allowedNextStatuses(): array` — static per-status transition maps:
  - Purchase: `draft → [pending, cancelled]`, `pending → [approved, cancelled]`, `approved → [received, cancelled]`, `received → []`, `cancelled → []`.
  - SalesOrder: `draft → [pending, cancelled]`, `pending → [confirmed, cancelled]`, `confirmed → [fulfilled, cancelled]`, `fulfilled → []`, `cancelled → []`.
- `App\Actions\Purchases\UpdatePurchaseStatus` / `App\Actions\SalesOrders\UpdateSalesOrderStatus`: inside `DB::transaction()` with `lockForUpdate()` on the order row (same pattern as the Payment overpayment guard, issue #14):
  1. Re-check the transition is still legal against the locked row's current status.
  2. If the target status is `cancelled`, re-check the order has zero payments; throw `ValidationException` if it has any.
  3. Persist the new status.
- `UpdatePurchaseStatusRequest` / `UpdateSalesOrderStatusRequest`: `status` required, must be a valid enum value, and a closure rule doing the same two checks as the Action for a fast, clear 422 in the common (non-race) case. The Action's in-transaction check remains the authoritative guard, matching the FormRequest-is-fast-path / Action-is-authoritative split used in Finance.
- `PurchaseController::updateStatus` / `SalesOrderController::updateStatus`: `$this->authorize('update', $purchase)` (the `update` policy method already exists and returns `true` — no policy changes needed), call the Action, return the resource.
- New routes: `PATCH /api/purchases/{purchase}/status`, `PATCH /api/sales-orders/{sales_order}/status`.
- `PurchaseResource`/`SalesOrderResource`: add `'allowed_next_statuses' => $this->allowedNextStatuses()`.

### Frontend

- `resources/js/types/purchases.ts` / `resources/js/types/sales-orders.ts`: add `allowed_next_statuses: PurchaseStatus[]` / `SalesOrderStatus[]` to the resource types.
- `resources/js/api/purchases.ts` / `resources/js/api/sales-orders.ts`: `updatePurchaseStatus(id, status)` / `updateSalesOrderStatus(id, status)` calling the new `PATCH` endpoints.
- `PurchasesTable.vue` / `SalesOrdersTable.vue`: an "Update status" `Button` per row opening a shadcn `Dialog` with a `Select` of `allowed_next_statuses`; disabled when the array is empty (terminal state). Follows the exact Dialog/Select pattern established in issue #20 — reuse `Button`, `Dialog`, `Select` from `resources/js/components/ui/**`.
- Validation errors from an illegal/raced transition render the same way every other form in this app does (via `ApiValidationError`).

### Tests

- Feature tests (new `PurchaseStatusTest.php` / `SalesOrderStatusTest.php`, or added to the existing `PurchaseTest.php`/`SalesOrderTest.php` — decide when writing, whichever keeps files a reasonable size): every legal transition, at least one skipped/illegal transition, transitioning from each terminal status, cancelling with zero payments (allowed) and with at least one payment (blocked).
- No new Vue component tests, consistent with the rest of the app.

## Assumptions (locked in, no need to re-ask)

- The two workflows above are the intended shape (linear progression + cancel-from-anywhere-non-terminal). If a different shape is wanted later, `allowedNextStatuses()` is the single place to change it.
- Cancelling with existing payments is blocked outright (no partial-refund or force-cancel path) — this app has no refund/credit-note mechanism to make that data consistent.
- No inventory stock-adjustment side effects on `received`/`fulfilled` — no such linkage exists today; out of scope.
- No audit/reason-for-change field — no audit-log table exists in this app.
- Dedicated `PATCH .../status` endpoints, not a generic full-record update — nothing else about an order is editable after creation.

## Risks

- This is the first place a money-adjacent guard (payment-blocks-cancellation) is enforced on the *order* side rather than the *payment* side — mitigated by reusing the exact `lockForUpdate` transaction pattern already reviewed and fixed in issue #14, rather than inventing a new concurrency approach.

## Blocked Questions

None.
