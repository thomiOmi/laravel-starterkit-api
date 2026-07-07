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

## Existing Skills

| Skill | Location | Description |
|---|---|---|
| `laravel-attributes` | `.ai/skills/laravel-attributes/` | PHP 8 attributes for Laravel models, jobs, commands, form requests |
| `modular-architecture` | `.ai/skills/modular-architecture/` | Module DDD structure: Actions, Controllers, Filters, Payloads, Resources |

Use the `skill` tool to load a skill when the task matches its description. List all available skills with the `available_skills` list in the system prompt.
