# Code Review Guidelines

- Ensure final classes are used for Controllers, Actions, and Payloads.
- Ensure strict types are declared in every PHP file.
- Verify that authorization is handled in Request classes or via Policies.
- Check that business logic is kept within Action classes.
- Ensure standard API response format: {status, message, data}.
- Verify that performance optimizations like defer() and whereRelation() are used where appropriate.
