# Laravel Guidelines

- Use Form Requests for user input validation when endpoints accept meaningful data.
- Use policies or gates for authorization.
- Keep controllers thin.
- Put business calculations in service classes or actions.
- Use database transactions for multi-write business operations.
- Use feature tests for end-to-end workflow behaviour.
- Use model factories for test data.
- Do not rely on frontend checks for authorization.
- Migrations must be reversible unless there is a documented reason.
