# INF-005: Changelog Following Keep a Changelog

**Documentation:** https://github.com/team-mate-pro/tmp-standards/blob/main/definitions/infrastructure/INF-005-changelog-keepachangelog.md

## Check Method

| Method | Command |
|--------|---------|
| **AI** | `claude -p "$(cat vendor/team-mate-pro/tmp-standards/definitions/infrastructure/INF-005-changelog-keepachangelog.prompt.txt)" --cwd .` |

## Definition

Every TMP project must have a `CHANGELOG.md` file in the repository root, maintained according to the [Keep a Changelog 1.1.0](https://keepachangelog.com/en/1.1.0/) convention.

The changelog is intended for **humans, not machines** — change descriptions should be short, concise, and understandable by a non-technical person (where possible). Description language: **Polish** (preferred) or English.

## Required Structure

### File Header

```markdown
# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).
```

### Version Section

Each version must include a date in `YYYY-MM-DD` format and an optional diff link:

```markdown
## [1.2.0] - 2025-03-15
```

Newest version always on top. An `[Unreleased]` section at the very top for changes not yet released.

### Change Categories — Required Order

Categories **must** be in English and in the following order (skip empty categories):

| Order | Category | When to Use |
|-------|----------|-------------|
| 1 | `### Added` | New features |
| 2 | `### Changed` | Changes to existing functionality |
| 3 | `### Deprecated` | Features marked for removal |
| 4 | `### Removed` | Removed features |
| 5 | `### Fixed` | Bug fixes |
| 6 | `### Security` | Security fixes |

### Change Descriptions

Each entry is a single bullet point (`-`) with a short, understandable description:

- Language: **Polish** (preferred) or English
- Style: concise, non-technical, human-readable
- If a change is linked to a Jira task — add a link at the end of the line
- If a change is critical or complex — add a link to a `docs/*.md` file with details

```markdown
### Added
- Eksport zamówień do pliku CSV [TMP-123](https://jira.team-mate.pl/browse/TMP-123)
- Automatyczne powiadomienia SMS o statusie dostawy — szczegóły w [docs/sms-notifications.md](docs/sms-notifications.md)
```

### Diff Links (at the bottom of the file)

```markdown
[Unreleased]: https://github.com/org/repo/compare/v1.2.0...HEAD
[1.2.0]: https://github.com/org/repo/compare/v1.1.0...v1.2.0
[1.1.0]: https://github.com/org/repo/releases/tag/v1.1.0
```

## When to Link to `docs/*`

Not every change requires additional documentation. Add a link to `docs/*.md` when:

- The change involves **critical business logic** (e.g. a new pricing algorithm)
- The change is **complex** and requires context, migration steps, or configuration explanation
- The change **affects other systems** or requires coordination with other teams

The `docs/` file should contain: problem description, chosen solution, migration steps (if applicable), impact on other modules.

## Correct Usage

```markdown
# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Filtrowanie zamówień po statusie płatności

## [1.2.0] - 2025-03-15

### Added
- Eksport zamówień do CSV [TMP-456](https://jira.team-mate.pl/browse/TMP-456)
- Nowy algorytm naliczania rabatów — szczegóły w [docs/discount-algorithm.md](docs/discount-algorithm.md)

### Changed
- Zmieniono format odpowiedzi API dla listy produktów

### Fixed
- Poprawiono błąd przy tworzeniu zamówienia bez adresu dostawy [TMP-789](https://jira.team-mate.pl/browse/TMP-789)

## [1.1.0] - 2025-02-01

### Added
- Powiadomienia e-mail o zmianie statusu zamówienia

### Removed
- Usunięto stary panel administracyjny (zastąpiony nowym UI)

### Security
- Zaktualizowano zależności z podatnościami CVE-2025-XXXX

[Unreleased]: https://github.com/org/repo/compare/v1.2.0...HEAD
[1.2.0]: https://github.com/org/repo/compare/v1.1.0...v1.2.0
[1.1.0]: https://github.com/org/repo/releases/tag/v1.1.0
```

## Violation

### Missing CHANGELOG.md file

```
project/
├── src/
├── composer.json
└── readme.md
# No CHANGELOG.md ❌
```

### Incorrect category order

```markdown
## [1.0.0] - 2025-01-01

### Fixed          ❌ Fixed before Added
- Poprawiono błąd logowania

### Added
- Nowa funkcja eksportu

### Security       ❌ Security before Removed
- Aktualizacja zależności

### Removed
- Usunięto stary moduł
```

**Correct order:** Added → Changed → Deprecated → Removed → Fixed → Security

### Overly technical descriptions

```markdown
### Fixed
- Fix NullPointerException in OrderRepository::findByStatus() when status enum is null ❌
- Dodano null-check w middleware przed deserializacją payload ❌
```

**Correct:**
```markdown
### Fixed
- Poprawiono błąd przy wyszukiwaniu zamówień bez statusu
- Naprawiono przetwarzanie żądań z pustą treścią
```

### Missing Unreleased section

```markdown
# Changelog

## [1.0.0] - 2025-01-01  ❌ No [Unreleased] section at the top

### Added
- Pierwsza wersja
```

### Categories in Polish

```markdown
### Dodane        ❌ Should be: Added
- Nowa funkcja

### Naprawione    ❌ Should be: Fixed
- Poprawka błędu
```

### Missing date in version

```markdown
## [1.0.0]        ❌ Missing date in YYYY-MM-DD format

### Added
- Nowa funkcja
```

### Missing Jira/docs link for complex change

```markdown
### Added
- Kompletna przebudowa silnika naliczania cen z uwzględnieniem rabatów grupowych,
  promocji czasowych i kuponów  ❌ Complex change without docs/* link
```

## Rules Summary

- **INF-005.1:** `CHANGELOG.md` must exist in the project root
- **INF-005.2:** File header must reference Keep a Changelog and Semantic Versioning conventions
- **INF-005.3:** `[Unreleased]` section must be at the top
- **INF-005.4:** Change categories in English, in order: Added → Changed → Deprecated → Removed → Fixed → Security
- **INF-005.5:** Change descriptions in Polish (preferred) or English — short and human-readable
- **INF-005.6:** Each version with date in `YYYY-MM-DD` format
- **INF-005.7:** Newest version on top
- **INF-005.8:** Complex/critical changes with link to `docs/*.md`
- **INF-005.9:** Task-related changes with Jira link (where applicable)
- **INF-005.10:** Diff links at the bottom of the file

## Rationale

1. **Transparency**: A changelog allows anyone (developer, PM, client) to quickly check what changed in a given version.

2. **Standardization**: One convention across all projects — no need to learn a new format when switching projects.

3. **Non-technical language**: Descriptions understandable by non-programmers facilitate communication with business stakeholders.

4. **Change tracking**: Jira links connect the changelog with the business context of the task.

5. **Complexity documentation**: `docs/*` files for critical changes provide detailed context without cluttering the changelog.

6. **Semantic Versioning**: Pairing with SemVer makes it easy to understand the scale of changes between versions.

## Related Standards

- [INF-004: GitLab Merge Request Policy](./INF-004-gitlab-merge-request-policy.md) - merge request policy
- [INF-001: Local Development Makefile](./INF-001-infrastructure-local-makefile.md) - local project standards
