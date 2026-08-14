# Creating Custom AI Skills & Rules

This project uses the [Agent Skills](https://agentskills.io) format for domain-specific AI knowledge. Skills are loaded on-demand; guidelines are loaded upfront.

## Guidelines vs Skills

| | Guidelines | Skills |
|---|---|---|
| Location | `.ai/guidelines/*.md` | `.ai/skills/{name}/SKILL.md` |
| Loaded | Upfront, always present | On-demand, when task matches description |
| Scope | Broad conventions (e.g, coding standards, architecture) | Focused domain knowledge (e.g, testing, permissions, social auth) |

## Creating a Skill

### 1. Directory structure

```text
.ai/skills/{skill-name}/
├── SKILL.md          # Required: YAML frontmatter + metadata + instructions
├── scripts/          # Optional: executable code
├── references/       # Optional: documentation
├── assets/           # Optional: templates, resources
└── ...               # Any additional files or directories
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

More details. See [specification](https://agentskills.io/specification), [best practices](https://agentskills.io/skill-creation/best-practices), [optimizing descriptions](https://agentskills.io/skill-creation/optimizing-descriptions), [using scripts](https://agentskills.io/skill-creation/using-scripts)

## Creating a Guideline

Add `.md` files to `.ai/guidelines/`. Guidelines are loaded upfront, so keep them concise (under 100 lines).

```text
.ai/guidelines/
├── general.md           # Project conventions (tech stack, API, testing, code quality)
├── creating-skills.md   # This file
└── ...                  # Any additional guidelines
```

## Managing with Boost

Run `php artisan boost:update --discover` to install Boost-provided guidelines and skills. All custom files in `.ai/` are preserved.

## Existing Skills

| Skill | Location | Description |
|---|---|---|
| `laravel-attributes` | `.ai/skills/laravel-attributes/` | PHP 8 attributes for Laravel models, jobs, commands, form requests |
| `modular-architecture` | `.ai/skills/modular-architecture/` | Module DDD structure: Actions, Builders, Controllers, Payloads, Requests, Resources |
| `create-prd` | `.ai/skills/create-prd/` | PRD creation from `docs/prd/template.md` with clarifying questions |
| `create-adr` | `.ai/skills/create-adr/` | Architecture Decision Records in Nygard format from `docs/adr/template.md` |
| `update-tasks` | `.ai/skills/update-tasks/` | TASKS.md operational tracker maintenance |

Use the `skill` tool to load a skill when the task matches its description. List all available skills with the `available_skills` list in the system prompt.
