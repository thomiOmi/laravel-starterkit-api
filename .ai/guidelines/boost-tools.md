# Laravel Boost & MCP Tools

This project uses **Laravel Boost** to enhance AI-assisted development.

## MCP Tools

Available via the `laravel-boost` MCP server:

| Tool | Description |
| --- | --- |
| `application-info` | PHP/Laravel versions, database, ecosystem packages, models. |
| `browser-logs` | Read recent browser logs and errors. |
| `database-connections` | Inspect available database connections. |
| `database-query` | Execute read-only queries against the database. |
| `database-schema` | Read the database schema/table structure. |
| `get-absolute-url` | Resolve relative paths to absolute URLs. |
| `last-error` | Read the last error from application logs. |
| `read-log-entries` | Read the last N log entries. |
| `search-docs` | Query Laravel documentation with semantic search. |

## AI Guidelines & Agent Skills

- **Guidelines**: Foundational context loaded upfront. Located in `.ai/guidelines/*.md`.
- **Skills**: Task-specific patterns loaded on-demand. Located in `.ai/skills/{name}/SKILL.md`.

## Workflow

1. Use `search-docs` for any framework or package-specific questions.
2. Use `database-schema` before writing migrations or models.
3. Use `application-info` to understand the current tech stack state.
4. Run `php artisan boost:update` after adding new packages to refresh guidelines.
