# Vue And TypeScript Guidelines

- Use Vue 3 Composition API.
- Use ShadCN UI components where possible.
- Keep component props and local data typed.
- Avoid `any` unless the reason is documented in code.
- Keep API payload types close to the feature consuming them until reuse is proven.
- Do not bury server authorization rules in the frontend.
- Handle loading, empty, error, and forbidden states for data-driven screens.
- Run `npm run typecheck` before opening a PR.
