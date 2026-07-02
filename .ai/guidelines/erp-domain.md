# ERP Domain Guidelines

This is a slim ERP system. Keep modules small and explicit.

Initial module boundaries:

- Purchasing
- Inventory
- Sales Orders
- Suppliers
- Customers
- Finance
- Workflow
- Reports

Business logic must be testable outside Vue components. If a rule affects money, stock, permissions, workflow state, or auditability, it belongs in Laravel and needs tests.

Avoid hidden state transitions. Workflow changes should be visible in the UI and backed by clear database state.
