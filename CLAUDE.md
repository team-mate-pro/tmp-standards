# CLAUDE.md

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
- Rules to check (numbered list)
- How to review (step-by-step instructions)
- Output format specification
