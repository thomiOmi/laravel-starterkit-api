# Creating Custom AI Skills & Rules

This project uses the [Agent Skills](https://agentskills.io) format for domain-specific AI knowledge. Skills are loaded on-demand; guidelines are loaded upfront.

## Guidelines vs Skills

| | Guidelines | Skills |
|---|---|---|
| Location | `.ai/guidelines/*.md` | `.ai/skills/{name}/SKILL.md` |
| Loaded | Upfront, always present | On-demand, when task matches description |
| Scope | Broad conventions (coding standards, architecture) | Focused domain knowledge (testing, permissions, social auth) |

## Creating a Skill

### 1. Directory structure

```
.ai/skills/{skill-name}/
  SKILL.md       -- Required: YAML frontmatter + instructions
  references/    -- Optional: detailed docs loaded on-demand
  scripts/       -- Optional: executable code
  assets/        -- Optional: templates, resources
```

### 2. SKILL.md format

```yaml
---
name: skill-name
description: Clear description of what this skill does and when to use it. Include keywords for matching.
metadata:
  version: "1.0"
---
```

Name rules:
- Lowercase letters, numbers, and hyphens only
- Max 64 characters
- Must match the parent directory name

Description rules:
- Max 1024 characters
- Describe both what and when

### 3. Progressive disclosure

Keep SKILL.md under 500 lines. Move detailed reference material to `references/`.

```markdown
See [the reference guide](references/detail.md) for full documentation.
```

## Creating a Guideline

Add `.md` files to `.ai/guidelines/`. Guidelines are loaded upfront, so keep them concise (under 100 lines).

```
.ai/guidelines/
  general.md           -- Project conventions (tech stack, API, testing, code quality)
  creating-skills.md   -- This file
```

## Managing with Boost

Run `php artisan boost:install -n` to install Boost-provided guidelines and skills. All custom files in `.ai/` are preserved.

## Existing Skills Reference

| Skill | Description |
|---|---|
| `laravel-specialist` | Laravel 13+, modules, controllers, actions, Eloquent, API endpoints |
| `laravel-patterns` | Modular DDD, Single-Action Controllers, Action pattern, Payloads |
| `laravel-security` | Security best practices, Sanctum, Spatie permission, RFC 9457 |
| `laravel-verification` | QA verification loop — Pint, PHPStan, Pest, Arch tests |
| `php-pro` | PHP 8.4+ strict typing, immutability, Property Hooks |
