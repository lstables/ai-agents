# Issue 3: Suppliers

## Feature Summary

Build full CRUD for suppliers (list, create, edit, delete) and make sure the supplier list on the Purchasing page (built in [#1](https://github.com/lstables/ai-agents/issues/1)) reflects changes made here.

Source: [GitHub issue #3](https://github.com/lstables/ai-agents/issues/3) — "[Feature]: Suppliers".

Implemented on branch `feature/issue-2-suppliers`, based on the unmerged `feature/issue-1-purchasing-module` branch (PR #2) since this feature extends the `Supplier` model and the Purchasing page's supplier dropdown that PR introduced.

## Acceptance Criteria

- [ ] A supplier listing page shows existing suppliers with pagination and search.
- [ ] A user can create a supplier (name required; email/phone optional).
- [ ] A user can edit an existing supplier.
- [ ] A user can delete a supplier, unless it has existing purchases (data-integrity: deleting would orphan purchase records).
- [ ] The Purchasing page's supplier dropdown/filter reflect supplier changes made here (already true by construction — both screens fetch suppliers live from the same endpoint on mount).
- [ ] All write actions are server-side validated and authorized.
- [ ] Backend feature tests cover listing, pagination, search, create, update, delete, and the delete-with-purchases guard.

## Agent Tasks

- [x] Team Lead Agent: confirm scope and flag the "shadcn" ambiguity below before implementation.
- [ ] Senior Developer Agent: extend `SupplierController` with paginated/searchable `index`, plus `store`, `update`, `destroy`.
- [ ] Senior Developer Agent: add `StoreSupplierRequest` / `UpdateSupplierRequest`.
- [ ] Senior Developer Agent: update `SupplierPolicy` create/update/delete to allow any authenticated user (matching `PurchasePolicy`'s no-roles-yet stance).
- [ ] Senior Developer Agent: guard supplier deletion when purchases reference it.
- [ ] Senior Developer Agent: build `SuppliersPage`/`SuppliersTable`/`SupplierForm` Vue components (reusing the Purchasing page's Tailwind patterns) and wire the "Suppliers" nav item.
- [ ] Senior Developer Agent: add backend feature tests.
- [ ] QA Agent: verify delete-with-purchases guard, validation edge cases, and that the Purchasing page keeps working against the now-paginated `/api/suppliers` endpoint.
- [ ] GitHub Reviewer Agent: check authorization, data-integrity on delete, and test coverage.

## Assumptions

- No roles/permissions are defined (per the issue), matching the existing `PurchasePolicy` stance: any authenticated user may create/update/delete suppliers.
- `/api/suppliers` becomes paginated (mirroring `/api/purchases`) with a generous default `per_page` so the existing Purchasing dropdown/filter (which requests it unpaginated today) keeps working without changes beyond requesting a larger page size.
- Deleting a supplier with existing purchases is rejected (422) rather than cascading, since `purchases.supplier_id` is a `restrictOnDelete` foreign key — this preserves purchase history.
- "Update the supplier list of purchasing page" is satisfied structurally: the Purchasing page already fetches suppliers fresh each time it mounts, so navigating away to Suppliers and back reflects any changes without extra wiring.

## Risks

- Introducing pagination to `/api/suppliers` could silently truncate the dropdown/filter list if supplier count exceeds the page size — mitigated by requesting a larger `per_page` from those call sites, but this doesn't scale indefinitely (same limitation already accepted for Purchases in issue #1).
- This branch is based on the unmerged `feature/issue-1-purchasing-module`; its PR will show both features' diffs until PR #2 merges.

## Blocked Questions

- The issue's business rules say "must enforce shadcn, Laravel boost and guidelines." `shadcn/ui` is a React component library; this app is Vue 3 + Tailwind with no React or shadcn-vue installed, and introducing a new UI framework mid-project is a large, unscoped change. Proceeding by matching the existing Tailwind patterns already used on the Purchasing page instead — confirm with a human whether shadcn (or a Vue port of it) is actually required.
- Same "Laravel boost" ambiguity flagged in issue #1's task file remains unresolved.

## Links

- Issue: https://github.com/lstables/ai-agents/issues/3
- Branch: feature/issue-2-suppliers
- PR:
- CI:
