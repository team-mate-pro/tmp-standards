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
