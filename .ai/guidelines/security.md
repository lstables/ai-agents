# Security Guidelines

- All user-facing write actions require server-side authorization.
- Validate all request input.
- Avoid mass assignment unless model fillable/guarded rules are explicit.
- Do not expose sensitive operational data through unauthenticated routes.
- Log or audit sensitive business events once audit infrastructure exists.
- Never commit secrets or real customer data.
- AI agents must not create deployment credentials, production tokens, or bypass branch protection.
