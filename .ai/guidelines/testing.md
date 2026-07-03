# Testing Guidelines

Backend:

- Should use Pest as the test suite.
- Use Laravel feature tests for workflows.
- Test permissions, validation, state transitions, and business calculations.
- Use factories for setup.

Frontend:

- TypeScript must pass.
- Build must pass.
- Add component or browser tests when frontend behaviour becomes complex enough to justify them.

Every PR should say what was tested and what was not tested.
