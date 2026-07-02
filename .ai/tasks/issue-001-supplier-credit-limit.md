# Issue 001: Supplier Credit Limit Checks

## Feature Summary

Add supplier credit limit warnings to purchase orders.

## Acceptance Criteria

- [ ] A supplier can have an optional credit limit.
- [ ] Creating or editing a purchase order warns when expected exposure exceeds the supplier credit limit.
- [ ] Cancelled purchase orders do not count toward exposure.
- [ ] Managers can override the warning.
- [ ] Non-managers must acknowledge the warning before saving.
- [ ] Backend tests cover normal user and manager flows.

## Agent Tasks

- [ ] Team Lead Agent: confirm data model, workflow, and risk assumptions.
- [ ] Senior Developer Agent: add supplier credit limit field.
- [ ] Senior Developer Agent: add purchase order exposure calculation service.
- [ ] Senior Developer Agent: add validation and authorization.
- [ ] Senior Developer Agent: add Vue warning state to purchase order form.
- [ ] Senior Developer Agent: add backend feature tests.
- [ ] QA Agent: verify edge cases and missing tests.
- [ ] GitHub Reviewer Agent: review permission, data integrity, and test coverage risks.

## Assumptions

- Pending and approved purchase orders count toward exposure.
- Cancelled purchase orders are excluded.
- Existing suppliers without a credit limit should not warn.

## Risks

- The calculation may later need to include unpaid invoices.
- Manager override behaviour must be auditable before production use.

## Links

- Issue:
- Branch:
- PR:
- CI:
