# Issue 9: Sales Orders + Cross-Module Linking

## Feature Summary

Build the Sales Orders module (create + paginated/filterable/searchable list, mirroring the Purchasing module's shape — not full CRUD, since editing a submitted order is a separate workflow concern out of scope here) and connect it to existing modules with real foreign keys:

1. `SalesOrder` belongs to a `Customer`.
2. `SalesOrderItem` may optionally reference an `InventoryItem` (a line can be a real stock item or free-text, matching `PurchaseItem`'s existing flexibility).
3. `InventoryItem` may have an optional preferred/primary `Supplier`.
4. Deleting a `Customer` with existing sales orders is blocked, mirroring the existing `Supplier`/`Purchase` guard.

Source: [GitHub issue #9](https://github.com/lstables/ai-agents/issues/9) — "[Feature]: Sales Orders + cross-module linking". Authored as part of running `.ai/playbooks/feature-delivery.md`.

No automatic stock quantity changes are in scope (confirming/fulfilling a sales order does not decrement `inventory_items.quantity_on_hand`) — that's a separate stock-movement feature, explicitly out of scope per the issue's business rules.

## Acceptance Criteria

- [ ] A sales order can be created for a customer, with one or more line items, each optionally linked to an inventory item.
- [ ] A sales orders table lists orders with server-side pagination, filtering (status, customer), and search (reference, customer name).
- [ ] The order total is computed server-side from line items (reusing the existing `PurchaseTotalCalculator`, which is already generic — quantity × price, rounded — not Purchase-specific in its logic despite its namespace).
- [ ] Deleting a customer with existing sales orders returns 422, mirroring `SupplierController::destroy`'s guard exactly.
- [ ] An inventory item can have an optional preferred supplier, settable from the inventory form and visible in the inventory table.
- [ ] All write actions are server-side validated; no roles/permissions required.
- [ ] Backend feature tests cover: sales order creation/validation/listing/pagination/filtering/search, the customer delete guard, and the supplier-on-inventory-item link (set on create/update, visible in resource response).
- [ ] `composer test`, `npm run typecheck`, `npm run build` all pass — full suite, not just new tests.

## Agent Tasks

### Laravel models/migrations

- [ ] `sales_orders` migration: `customer_id` (FK, `restrictOnDelete` — mirrors `purchases.supplier_id`), `created_by` (FK to users, `restrictOnDelete`), `reference` (unique), `status` (string, default `draft`, indexed), `order_date`, `expected_date` (nullable), `notes` (nullable), `total_amount` (decimal 12,2 default 0), timestamps.
- [ ] `sales_order_items` migration: `sales_order_id` (FK, `cascadeOnDelete` — mirrors `purchase_items.purchase_id`), `inventory_item_id` (nullable FK, `nullOnDelete` — deleting an inventory item must not delete sales history, just clear the reference), `description`, `quantity`, `unit_price`, `line_total`, timestamps.
- [ ] Migration to add `supplier_id` (nullable FK, `nullOnDelete` — unlike Purchase's required+restrict supplier, this is an optional "preferred supplier," so deleting a supplier should not be blocked by it) to `inventory_items`.
- [ ] `SalesOrder` model: `#[Fillable]`, status constants (`draft`, `pending`, `confirmed`, `fulfilled`, `cancelled` — sales-appropriate terms, not a copy-paste of Purchase's `approved`/`received`), casts, `belongsTo Customer`, `belongsTo User` (creator), `hasMany SalesOrderItem`.
- [ ] `SalesOrderItem` model: `#[Fillable]`, casts, `belongsTo SalesOrder`, `belongsTo InventoryItem` (nullable).
- [ ] `Customer` model: add `salesOrders(): HasMany` (needed for the delete guard).
- [ ] `InventoryItem` model: add `supplier(): BelongsTo` (nullable), extend `#[Fillable]` to include `supplier_id`.
- [ ] `Supplier` model: add `inventoryItems(): HasMany` (reverse relation, for completeness — not strictly required by the guard logic but keeps the relationship bidirectional like `Supplier::purchases()`).
- [ ] `SalesOrderFactory`, `SalesOrderItemFactory`.
- [ ] `CreateSalesOrder` action (mirrors `CreatePurchase` exactly): transaction, per-line total via `App\Actions\Purchases\PurchaseTotalCalculator` (reused, not duplicated — it's a pure `quantity × price` utility with no Purchase-specific concepts), reference generation with the same collision-retry pattern.

### Routes/controllers/requests

- [ ] `SalesOrderController` with `index` (paginate/filter status+customer_id/search reference+customer name) and `store` — mirror `PurchaseController` exactly, including the `$this->authorize('viewAny', ...)` pattern and FormRequest-based `authorize()` for `store`.
- [ ] `StoreSalesOrderRequest`: `customer_id` required/exists, `order_date` required date, `expected_date` nullable/after_or_equal, `notes` nullable, `items` required array min:1, `items.*.inventory_item_id` nullable/exists:inventory_items,id, `items.*.description` required, `items.*.quantity` required numeric min:0.01, `items.*.unit_price` required numeric min:0 — mirrors `StorePurchaseRequest` plus the new optional inventory link field.
- [ ] `SalesOrderPolicy`: mirror `PurchasePolicy` exactly (viewAny/view/create/update return `true`, delete/restore/forceDelete return `false` — no delete route exists, matching Purchase's own unused-but-present policy shape).
- [ ] `SalesOrderResource` / `SalesOrderItemResource` (the latter includes a lightweight nested `inventory_item` — `{id, sku, name}` — via `whenLoaded`, not a full `InventoryItemResource`, to avoid payload bloat and circular coupling).
- [ ] Update `CustomerController::destroy` to guard on `$customer->salesOrders()->exists()`, mirroring `SupplierController::destroy` line-for-line (throw `ValidationException::withMessages(['customer' => ...])`).
- [ ] Update `StoreInventoryItemRequest`/`UpdateInventoryItemRequest` to accept `supplier_id` (nullable, integer, exists:suppliers,id).
- [ ] Update `InventoryItemController::index` to eager-load `->with('supplier')`; `store`/`update` need no change beyond the request validation already covering `supplier_id` (it flows through `$request->validated()` into `InventoryItem::create()`/`update()` since it's in `#[Fillable]`).
- [ ] Update `InventoryItemResource` to include `supplier` (nested `SupplierResource`, nullable via `whenLoaded`).
- [ ] Register `sales-orders` routes under the existing `Route::prefix('api')->middleware(ResolveDemoUser::class)` group — no auth barrier.

### Vue/TypeScript screens/components

- [ ] `resources/js/types/sales-orders.ts` and `resources/js/api/sales-orders.ts`, mirroring `types/purchases.ts`/`api/purchases.ts` shape.
- [ ] `SalesOrdersPage.vue` / `SalesOrdersTable.vue` / `SalesOrderCreateForm.vue` / `SalesOrderStatusBadge.vue`, mirroring the Purchasing module's four components directly (same structure: toggleable create form + table with status/customer filters, search, pagination).
- [ ] `SalesOrderCreateForm.vue`'s line items get an additional optional "Inventory item" select per row (populated via a dropdown-style inventory fetch, same pattern as the existing supplier dropdown in `PurchaseCreateForm.vue`) — selecting one can prefill the description field, but the description remains editable (matches the historical-record reasoning already used for `PurchaseItem`).
- [ ] `InventoryItemForm.vue`: add an optional "Preferred supplier" select (reuses `fetchAllSuppliers()` from `api/suppliers.ts`, already exists).
- [ ] `InventoryTable.vue`: add a "Supplier" column (`item.supplier?.name ?? '—'`).
- [ ] Wire the existing "Sales Orders" nav item in `ErpDashboard.vue` to `SalesOrdersPage.vue`.
- [ ] Handle loading, empty, error states per `.ai/guidelines/vue-typescript.md` (no forbidden-state handling required — consistent with every other module built so far, no roles exist).

### Create form

- [ ] `SalesOrderCreateForm.vue`: customer select, order date, expected date, notes, dynamic line items (description, optional inventory item, quantity, unit price), computed running total, disable-while-submitting, server errors are the source of truth.

### Table with pagination, filtering, and search

- [ ] `SalesOrdersTable.vue`: status filter, customer filter, debounced search (reference/customer name), pagination — mirrors `PurchasesTable.vue`.

### Tests

- [ ] Sales order creation (happy path with and without inventory-linked lines), validation failures (missing customer, empty items, negative quantity/price, invalid inventory_item_id), listing/pagination/filter/search (including combined), total calculation correctness.
- [ ] Customer delete guard: deleting a customer with sales orders → 422; without → 204 (already covered, re-verify still passes).
- [ ] Inventory-supplier link: creating/updating an inventory item with a `supplier_id` persists and is returned in the resource; omitting it leaves `supplier` null; an invalid `supplier_id` is rejected.
- [ ] Run `composer test`, `npm run typecheck`, `npm run build` — confirm the full suite (not just new tests) is green.

### QA checks

- [ ] Verify acceptance criteria end-to-end (not just unit-tested) via a live smoke test against a running server.
- [ ] Check the customer delete guard doesn't have the same TOCTOU race already flagged (as informational, not blocking) for Supplier's guard in the PR #4 review — if reusing the exact same pattern, the same caveat applies; don't treat it as a new defect.
- [ ] Check that an inventory item's `supplier` field correctly reflects `null` after the supplier is deleted (given `nullOnDelete`).
- [ ] Check filter/search/pagination combinations for Sales Orders, same rigor as prior QA passes for Purchasing.
- [ ] Flag any missing coverage and add focused tests where appropriate.

### Reviewer checks

- [ ] Confirm the new foreign keys use appropriate delete behavior (`restrictOnDelete` for required relationships, `nullOnDelete` for optional ones) — this is the crux of the "rollout risk" called out in the issue.
- [ ] Confirm `CreateSalesOrder`'s reuse of `PurchaseTotalCalculator` is a reasonable DRY choice, not a maintainability smell worth flagging (a naming nitpick is fine to note as P3, but the reuse itself is intentional).
- [ ] Confirm the Customer delete-guard change doesn't silently change behavior for existing rows in a way that isn't covered by a test (it changes behavior only for customers that now have sales orders, which cannot exist for rows created before this migration).
- [ ] Confirm test coverage matches the acceptance criteria across all three linked concerns (Sales Orders, Customer guard, Inventory-Supplier link), not just the new module in isolation.

## Assumptions

- Sales Orders get create+list only (no edit/delete), matching Purchasing's original scope — not full CRUD like Suppliers/Inventory/Customers, since a submitted order's line items are a workflow/audit concern beyond this issue.
- No automatic stock quantity mutation — confirming/fulfilling a sales order does not touch `inventory_items.quantity_on_hand`. This is the same "no stock-movement ledger" boundary already drawn for the Inventory module itself.
- `SalesOrderItem.inventory_item_id` is optional, not required — a sales order can sell something not (yet) catalogued in Inventory, matching the flexibility `PurchaseItem` already has (free-text only, no catalog link at all).
- `InventoryItem.supplier_id` is a single "preferred/primary" supplier, not a many-to-many "this item can come from any of these suppliers" relationship — the issue describes a simple link, not a full sourcing model.
- No roles/permissions are required (explicit in the issue), matching every other module.

## Risks

- This is the first migration linking two independently-shipped modules (Inventory, Suppliers) and the first behavior change to an already-shipped module (Customer's delete now can fail where it previously couldn't) — flagged explicitly in the issue's rollout risk section, not silently introduced.
- The Customer delete guard mirrors Supplier's exact pattern, including its known (low-severity, already-reviewed) check-then-delete TOCTOU race — accepted as consistent with existing precedent, not re-litigated here.
- Reusing `PurchaseTotalCalculator` from the `App\Actions\Purchases` namespace for Sales Orders is a deliberate DRY trade-off; a stricter reviewer might want it renamed/relocated to a neutral namespace (e.g. `App\Actions\Shared`). Noted as an acceptable, low-cost naming compromise rather than done silently.

## Blocked Questions

None — the issue was authored with clear, unambiguous acceptance criteria and explicit rollout-risk disclosure; none of `.ai/playbooks/feature-delivery.md`'s stop conditions apply.

## Links

- Issue: https://github.com/lstables/ai-agents/issues/9
- Branch:
- PR:
- CI:
