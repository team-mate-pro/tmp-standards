# CLAUDE.md

## Language

All definition files (`definitions/**/*.md` and `definitions/**/*.prompt.txt`) **must be written in English**. Changelog entries and user-facing descriptions follow the INF-005 standard (Polish preferred, English acceptable).

## Definition Structure

Every definition must follow the format `{PREFIX}-{NUMBER}: {Title}` (e.g., `UCB-001: UseCase Parameters Must Be Interfaces`).

### Sub-rule Identifiers

When a definition contains multiple enforceable rules, each sub-rule **must** have an identifier in the format `{PREFIX}-{NUMBER}.{SUB}`:

```markdown
### ARCH-001.1: Use Plural Nouns Only
### ARCH-001.2: No Verbs in Paths
### ARCH-001.3: State Changes via PATCH or Sub-resources
```

In prompt files, use the same identifiers:

```
1. **ARCH-001.1: Use Plural Nouns Only**: ...
2. **ARCH-001.2: No Verbs in Paths**: ...
```

The output format section should reference identifiers, not plain numbers:

```
- Which rule is violated (ARCH-001.1 through ARCH-001.4)
```

**Do NOT add sub-rule identifiers to:**
- Rationale sections (numbered "Why" explanations)
- Configuration tables (settings, variables)
- Step-by-step instructions (migration steps, setup guides)

Only actual **enforceable rules** get identifiers.

### Available Prefixes

| Prefix  | Category |
|---------|----------|
| `ARCH`  | Architecture - REST API design standards |
| `CC`    | Clean Code - general coding best practices |
| `FE`    | Frontend - Vue/Nuxt component standards |
| `INF`   | Infrastructure - local development, CI/CD, tooling |
| `SOLID` | Design Patterns - SOLID principles |
| `TEST`  | Tests - test structure and patterns |
| `UCB`   | UseCase Bundle - rules for UseCase pattern |

## PHPStan Rules

Every PHPStan rule that enforces a definition must include a `->tip()` with a link to the corresponding definition on GitHub:

```php
RuleErrorBuilder::message('...')
    ->identifier('...')
    ->tip('See: https://github.com/team-mate-pro/tmp-standards/blob/main/definitions/{category}/{DEFINITION-FILE}.md')
    ->build();
```

This ensures developers can immediately understand and read the full rule when PHPStan reports an error.

## Prompt-Based Verification

Definitions that require AI-based verification must include a `.prompt.txt` file alongside the definition. The Check Method section must use the full `claude` CLI command:

```markdown
## Check Method

| Method | Command |
|--------|---------|
| **AI** | `claude -p "$(cat vendor/team-mate-pro/tmp-standards/definitions/{category}/{DEFINITION-ID}.prompt.txt)" --cwd .` |
```

The prompt file should contain:
- Standard description
- Rules to check (numbered list with `{PREFIX}-{NUMBER}.{SUB}` identifiers)
- How to review (step-by-step instructions)
- Output format specification (referencing rule identifiers)

## Changelog

This project follows [INF-005](definitions/infrastructure/INF-005-changelog-keepachangelog.md). Every change to definitions, rules, or scripts must be recorded in `CHANGELOG.md` under `[Unreleased]`.

## Checklist for New Definitions

1. Create `definitions/{category}/{PREFIX}-{NUMBER}-{slug}.md` (in English)
2. Add sub-rule identifiers (`{PREFIX}-{NUMBER}.{SUB}`) for multiple enforceable rules
3. Create the check method file:
   - PHPStan rule -> `src/PHPStan/Rules/` + test in `tests/PHPStan/Rules/` + `->tip()` link
   - AI prompt -> `{PREFIX}-{NUMBER}-{slug}.prompt.txt` (with matching identifiers)
   - Script -> `{PREFIX}-{NUMBER}-{slug}.sh`
4. Add the standard to the "Available Standards" table in `readme.md`
5. Add a changelog entry in `CHANGELOG.md` under `[Unreleased]`
