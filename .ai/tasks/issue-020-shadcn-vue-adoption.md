# Issue #20: Adopt shadcn-vue Components

GitHub issue: https://github.com/lstables/ai-agents/issues/20
Branch: `feature/issue-20-shadcn-vue-adoption`

## Summary

Replace this app's hand-rolled forms, inline toggled create/edit cards, plain HTML tables, and native `confirm()` delete prompts with shadcn-vue components everywhere they appear, per the (previously unimplemented) guideline in `.ai/guidelines/vue-typescript.md`.

## Audited Current State

Every module follows the same pattern today: `PageComponent.vue` toggles an inline `<div>` card containing a `*Form.vue`; `*Table.vue` renders a plain `<table>` and, where delete exists, calls the browser's native `confirm()`.

| Module | Create | Edit | Delete | Table |
|---|---|---|---|---|
| Suppliers | inline card | inline card | `confirm()` | plain |
| Customers | inline card | inline card | `confirm()` | plain |
| Inventory | inline card | inline card | `confirm()` | plain |
| Purchasing | inline card | none (no route) | none (no route) | plain |
| Sales Orders | inline card | none (no route) | none (no route) | plain |
| Finance | inline card | none (no route) | none (no route) | plain |
| Reports | n/a (filters only) | — | — | plain |
| Dashboard | n/a | — | — | n/a |

## Scope

### Foundation

- Run `npx shadcn-vue@latest init` (Vite template, base color `zinc`, CSS variables, lucide icons) — this sets up `components.json`, a `@/*` path alias in `tsconfig.json`/`vite.config.ts`, and shadcn's CSS theme variables in `resources/css/app.css`.
- Scaffold via `npx shadcn-vue@latest add`: `button`, `input`, `label`, `textarea`, `select`, `table`, `dialog`, `alert-dialog`, `card`, `badge`. These land under `resources/js/components/ui/**` as owned source, per shadcn's model.
- New dependencies this pulls in: `reka-ui`, `class-variance-authority`, `clsx`, `tailwind-merge`, `lucide-vue-next`. No form-schema library (no vee-validate/zod) — deliberately out of scope.

### Per-module conversion (same shape each time)

For Suppliers, Customers, Inventory (full CRUD):
- `*Page.vue`: replace the inline toggled card with a shadcn `Dialog` wrapping the existing `*Form.vue` for both create and edit.
- `*Table.vue`: replace the plain `<table>` with shadcn `Table`/`TableHeader`/`TableBody`/`TableRow`/`TableHead`/`TableCell`; replace the delete button's native `confirm()` with a shadcn `AlertDialog`.
- `*Form.vue`: replace plain `<input>`/`<select>`/`<textarea>`/`<button>` with shadcn `Input`/`Select`/`Textarea`/`Label`/`Button`, keeping existing server-side validation error rendering per field.

For Purchasing, Sales Orders, Finance (create-only, no edit/delete routes):
- `*Page.vue`: create opens in a shadcn `Dialog` instead of an inline toggled card.
- `*Table.vue`: shadcn `Table`.
- `*CreateForm.vue` / `RecordPaymentForm.vue`: shadcn form fields.
- `PurchaseStatusBadge.vue` / `SalesOrderStatusBadge.vue`: thin wrapper around shadcn `Badge`, preserving the existing status→color mapping.

For Reports:
- `ReportsPage.vue`: date-range filter inputs become shadcn `Input`/`Button`; the low-stock table becomes shadcn `Table`.

Dashboard and the sidebar nav shell are out of scope beyond trivial `Button` swaps on `ErpDashboard.vue`'s two header buttons, if cheap to do — the nav itself is bespoke layout, not a form or modal.

### Tests

- This is a pure presentation refactor — no Laravel/API changes. `composer test` must pass unmodified (regression-only check, no new backend tests expected).
- No Vue component tests exist in this app today (consistent throughout); `npm run typecheck` + `npm run build` are the frontend gate, plus a manual/live smoke test of one full-CRUD module end-to-end (create, edit, delete) before opening the PR.

## Assumptions (locked in, no need to re-ask)

- shadcn-vue's official Vue port (`shadcn-vue`), not a hand-rolled imitation — this is what "ShadCN components" means for a Vue app, since the original shadcn/ui is React-only.
- Base color `zinc` to match the app's existing palette.
- `Dialog` for create/edit, `AlertDialog` reserved for delete confirmation only, matching shadcn's own convention for the two components.
- No new client-side validation library — validation stays server-side, matching every other module built so far this project.
- Not adding edit/delete to Purchasing/Sales Orders/Finance — those routes don't exist and adding them is a business-rule decision outside this issue's scope.

## Risks

- Large surface area (touches every module) — main risk is a UI regression during conversion, not a data-integrity or business-rule risk, since no backend code changes. Mitigated by: converting one full-CRUD module first as a reference pattern, keeping `composer test` green throughout (proves no backend drift), and a live smoke test of at least one full CRUD flow before opening the PR.
- New dependency footprint (`reka-ui` + shadcn's small utility deps) — acceptable, this is the explicit ask.

## Blocked Questions

None.
