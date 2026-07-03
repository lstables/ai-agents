# Issue 14: Finance (Payments) Module

## Feature Summary

Build a Payments module that records money actually changing hands against the two order types that already exist: purchases (money out, to suppliers) and sales orders (money in, from customers). Surface `amount_paid`/`balance_due` on both Purchases and Sales Orders, not just on a standalone Finance screen.

Source: [GitHub issue #14](https://github.com/lstables/ai-agents/issues/14) — "[Feature]: Finance (Payments)". Authored as part of running `.ai/playbooks/feature-delivery.md`, per the now-updated process that includes a Bug Fix Agent step after QA/Reviewer passes (see `.ai/agents/bug-fix-agent.md` — not yet merged to `main` as of this issue, but followed here regardless since it was explicitly requested).

This is the first feature in the app that enforces a money-based validation rule (rejecting overpayment) rather than just recording/aggregating data, so the issue locks in the ambiguous business rules up front rather than leaving them vague — per `.ai/playbooks/feature-delivery.md`'s explicit stop condition ("the feature affects money... and the business rule is vague").

## Acceptance Criteria

- [ ] A payment can be recorded against exactly one purchase or one sales order (amount, date, method, optional reference/notes).
- [ ] A payment is rejected (422) if it would exceed the order's remaining balance due.
- [ ] A payments list shows existing payments with server-side pagination, filtering by payable type, and search (by payment reference or the linked order's reference).
- [ ] `Purchase` and `SalesOrder` resources both expose `amount_paid` and `balance_due` (computed, not stored).
- [ ] `PurchasesTable.vue` and `SalesOrdersTable.vue` both show a balance-due column.
- [ ] All write actions are server-side validated; no roles/permissions required.
- [ ] Backend feature tests cover recording a payment against each order type, overpayment rejection, `amount_paid`/`balance_due` correctness (including with zero and multiple payments), and listing/filtering/search.
- [ ] `composer test`, `npm run typecheck`, `npm run build` all pass — full suite, not just new tests.

## Agent Tasks

### Laravel models/migrations

- [ ] `payments` migration: `payable_type` + `payable_id` (polymorphic, indexed together), `amount` (decimal 12,2), `payment_date` (date), `method` (nullable string — free text for v1, e.g. "bank_transfer"/"card"/"cash"), `reference` (nullable string), `notes` (nullable text), `created_by` (FK to users, `restrictOnDelete`), timestamps. Reversible.
- [ ] `Payment` model: `#[Fillable]`, `morphTo('payable')`, `belongsTo(User::class, 'created_by')` as `creator()`.
- [ ] `Purchase` model: add `payments(): MorphMany`, `amountPaid(): float` (sum of payments), `balanceDue(): float` (`total_amount - amountPaid()`, floored at 0 defensively even though overpayment should already be prevented).
- [ ] `SalesOrder` model: same two helper methods and the `payments()` relation.
- [ ] `PaymentFactory`.
- [ ] Do **not** map `payable_type` to a raw PHP class string over the API — accept a short alias (`purchase` / `sales_order`) from the client and resolve it to `Purchase::class`/`SalesOrder::class` server-side via an explicit allow-list. Never let client input decide which class gets instantiated/queried, per `.ai/guidelines/security.md`.

### Routes/controllers/requests

- [ ] `PaymentController` with `index` (paginate/filter by payable type/search) and `store` — mirror `PurchaseController`'s shape (pagination clamp, search pattern).
- [ ] `StorePaymentRequest`: `payable_type` required, `in:purchase,sales_order`; `payable_id` required, integer, existence checked against the *resolved* table (not a generic `exists` rule, since the table depends on `payable_type`); `amount` required, numeric, `min:0.01`; a custom rule/closure rejecting `amount` greater than the resolved payable's current `balanceDue()`; `payment_date` required date; `method`/`reference`/`notes` nullable strings.
- [ ] `PaymentPolicy`: mirrors `SalesOrderPolicy`/`PurchasePolicy` (all gates `true`, no roles exist).
- [ ] `PaymentResource`: id, amount, payment_date, method, reference, notes, a lightweight nested `payable` summary (`{type, id, reference}` — not a full nested Purchase/SalesOrder resource, to avoid payload bloat and circular coupling, matching `SalesOrderItemResource`'s existing `inventory_item` pattern).
- [ ] Extend `PurchaseResource`/`SalesOrderResource` with `amount_paid`/`balance_due`.
- [ ] Register `GET /api/payments` and `POST /api/payments` under the existing `Route::prefix('api')->middleware(ResolveDemoUser::class)` group.

### Vue/TypeScript screens/components

- [ ] `resources/js/types/finance.ts` and `resources/js/api/finance.ts`.
- [ ] `FinancePage.vue` / `PaymentsTable.vue` / `RecordPaymentForm.vue`, mirroring the Purchasing module's create+list structure (no edit/delete, matching the "payments are immutable" business rule).
- [ ] `RecordPaymentForm.vue`: select payable type, then select the specific order (search by reference), showing that order's current balance due so the user knows the ceiling before typing an amount.
- [ ] Add a "Balance due" column to `PurchasesTable.vue` and `SalesOrdersTable.vue` (cheap, direct surfacing of the new data — the whole point of tying Finance to the existing modules rather than leaving it siloed).
- [ ] Wire the existing "Finance" nav item in `ErpDashboard.vue`.
- [ ] Handle loading, empty, error states per `.ai/guidelines/vue-typescript.md`.

### Tests

- [ ] Recording a payment against a purchase and against a sales order (happy path), each reducing `balance_due` correctly.
- [ ] Overpayment is rejected (422) for both payable types, and at the exact boundary (a payment equal to the remaining balance due must succeed, not just less-than).
- [ ] `amount_paid`/`balance_due` are correct with zero payments, one payment, and multiple partial payments summing toward the total.
- [ ] An invalid `payable_type` (anything other than `purchase`/`sales_order`) is rejected — this is also the security-relevant test proving the allow-list, not raw client-supplied class resolution.
- [ ] An invalid `payable_id` for the given `payable_type` is rejected.
- [ ] Listing/pagination/filter-by-type/search-by-reference, including combined.
- [ ] Run `composer test`, `npm run typecheck`, `npm run build` — confirm the full suite is green.

### QA checks

- [ ] Verify overpayment rejection end-to-end via a live smoke test, not just a unit test.
- [ ] Check the boundary case (payment exactly equal to balance due) live.
- [ ] Check that `amount_paid`/`balance_due` on the Purchases/Sales Orders tables actually reflect payments recorded via the Finance screen (cross-module UI check).
- [ ] Check failed/empty/loading UI states on the Finance screen and the record-payment form.
- [ ] Flag any missing coverage and add focused tests where appropriate.

### Reviewer checks

- [ ] Confirm `payable_type` is resolved via an explicit allow-list, never a raw client-supplied class string.
- [ ] Confirm the overpayment check is race-safe enough for this app's existing risk tolerance (compare against the already-accepted check-then-act pattern in `SupplierController`/`CustomerController`'s delete guards — same class of TOCTOU, not a new category of risk, but worth naming explicitly since this one guards money, not just referential integrity).
- [ ] Confirm `amount_paid`/`balance_due` are computed consistently (no risk of one being stored/cached and drifting from the other).
- [ ] Confirm test coverage matches the acceptance criteria, especially the overpayment boundary and the `payable_type` allow-list.

### Bug Fix Agent (conditional, per the updated playbook)

- [ ] If QA or the Reviewer find anything actionable, fix it on this branch, add a regression test, rerun checks, and push — before this goes to a human. See `.ai/agents/bug-fix-agent.md`.

## Assumptions

- Payments are immutable — no edit/delete in this version (explicit in the issue, not left vague).
- Overpayment is rejected outright, not allowed as a customer/supplier credit balance — also explicit in the issue.
- `payable_type` is a short alias (`purchase`/`sales_order`), not a raw PHP class name, for the same reason client input should never select what gets instantiated.
- No roles/permissions required (explicit in the issue), matching every other module.

## Risks

- The overpayment check (query existing payments, sum, compare, then insert) has the same check-then-act race window already accepted for the Supplier/Customer delete guards. This one is more consequential since it guards a money invariant rather than referential integrity — named explicitly here rather than silently inheriting the same risk profile without comment.
- This is the first computed (not stored) financial figure in the app (`balance_due`) — if a future feature needs to report on historical balances at a point in time, computing from live payment sums won't support that; out of scope for now.

## Blocked Questions

None — the issue explicitly locks in the two business rules (no overpayment, no edit/delete) that would otherwise trigger `.ai/playbooks/feature-delivery.md`'s "vague money business rule" stop condition.

## Links

- Issue: https://github.com/lstables/ai-agents/issues/14
- Branch:
- PR:
- CI:
