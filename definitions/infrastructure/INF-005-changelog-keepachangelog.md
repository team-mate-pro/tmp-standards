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

Newest version always on top. An `[Unreleased]` section at the very top for changes not yet released. See **Release Definition** below for what "released" means — stage/test deployments do **not** promote entries out of `[Unreleased]`.

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
- If a change is critical or complex — add a link to an extended description under `docs/changelog/{YYYY-MM-DD}-{version}-{slug}.md`

```markdown
### Added
- Eksport zamówień do pliku CSV [TMP-123](https://jira.team-mate.pl/browse/TMP-123)
- Automatyczne powiadomienia SMS o statusie dostawy — szczegóły w [docs/changelog/2025-03-15-1.2.0-sms-notifications.md](docs/changelog/2025-03-15-1.2.0-sms-notifications.md)
```

### Diff Links (at the bottom of the file)

```markdown
[Unreleased]: https://github.com/org/repo/compare/v1.2.0...HEAD
[1.2.0]: https://github.com/org/repo/compare/v1.1.0...v1.2.0
[1.1.0]: https://github.com/org/repo/releases/tag/v1.1.0
```

## Release Definition — What Counts as "Released"

A version is considered **released** only when the code is running on **production**. Deployments to stage, test, dev, QA, preview, or any other non-production environment do **not** promote entries out of `[Unreleased]`.

| State | Changelog location |
|-------|--------------------|
| Merged to `main`, not deployed anywhere | `[Unreleased]` |
| Deployed to stage / test / preview / QA | `[Unreleased]` |
| Deployed to production | `## [X.Y.Z] - YYYY-MM-DD` |

The date in the version heading is the **production deployment date** — not the merge date, tag date, or stage-deployment date. The filename date segment in `docs/changelog/{YYYY-MM-DD}-{version}-{slug}.md` follows the same rule (production release date; use literal `unreleased` while the change is still in `[Unreleased]`).

### Release Workflow

Promoting `[Unreleased]` to a versioned section is part of the production-deployment flow, not the merge-to-`main` flow:

1. Decide the version bump following SemVer.
2. In `CHANGELOG.md`: rename `## [Unreleased]` to `## [X.Y.Z] - YYYY-MM-DD` using the production deploy date, and add a fresh empty `## [Unreleased]` on top.
3. Update the diff links at the bottom (`[Unreleased]` now compares against `vX.Y.Z`, add a new `[X.Y.Z]` link).
4. Rename any `docs/changelog/{date}-unreleased-{slug}.md` files promoted in this release to `{production-date}-{X.Y.Z}-{slug}.md` and update the links in `CHANGELOG.md`.
5. Commit the changelog bump (typical message: `X.Y.Z: <release summary>`).
6. Tag that commit `vX.Y.Z` in git and push the tag (`git tag vX.Y.Z && git push origin vX.Y.Z`).
7. Deploy that exact tagged commit to production.

A `vX.Y.Z` git tag may exist **only** for versions that are actually on production. Do not tag stage/test releases. If a release is cut but rolled back before reaching production, delete the tag and move the entries back under `[Unreleased]` in a follow-up commit.

## When to Link to an Extended Description

Not every change requires additional documentation. The main `CHANGELOG.md` stays short — extended descriptions live in `docs/changelog/` and are linked from the changelog entry only when the change qualifies.

Add a link to `docs/changelog/{YYYY-MM-DD}-{version}-{slug}.md` when:

- The change involves **critical business logic** (e.g. a new pricing algorithm)
- The change is **complex** and requires context, migration steps, or configuration explanation
- The change **affects other systems** or requires coordination with other teams

### File Location and Naming

Extended descriptions **must** be stored under `docs/changelog/` with the filename format `{YYYY-MM-DD}-{version}-{slug}.md`:

- `{YYYY-MM-DD}` — **production release date** matching the version where the change lands (for `[Unreleased]` entries use the date of the change; rename the file to the real production-deploy date at release time)
- `{version}` — target version where the change lands (e.g. `1.2.0`, `2.0.0-rc1`); for changes still in `[Unreleased]` use the literal string `unreleased` (rename to the real version at release time)
- `{slug}` — short kebab-case identifier describing the change (e.g. `pricing-algorithm`, `sms-notifications`, `auth-migration`)

Examples:

```
docs/changelog/2025-03-15-1.2.0-sms-notifications.md
docs/changelog/2025-03-15-1.2.0-discount-algorithm.md
docs/changelog/2026-04-17-2.0.0-auth-migration.md
docs/changelog/2026-04-22-unreleased-experimental-feature-toggle-semantics.md
```

The main changelog links to the extended description **only when the change is related** — short or obvious entries stay inline without a link.

### Extended Description Content

Keep the extended description **short and business-focused** — it complements the inline changelog entry, not replaces code review or technical documentation. Target 1–2 scrolls of screen content, not pages.

Required sections (in this order, skip when not applicable):

- **Problem** — what went wrong or what was missing (2–4 sentences, observable behavior)
- **Rozwiązanie** / **Solution** — what changed from the user's perspective (bulleted list, no implementation details)
- **Wdrożenie** / **Deployment notes** — migrations, cache purges, coordination with other teams

**Avoid** in the extended description:

- File paths, class names, method signatures — reader opens the diff for that
- Test counts, green/red status — belongs in the MR description
- Step-by-step implementation walkthroughs — belongs in the code or ADR
- Rationale for alternative approaches not taken — belongs in the MR discussion

If the change genuinely needs deep technical context (migration with reversibility risks, protocol change, RFC-like decision), write an ADR under `docs/architecture/` and link to it from the extended description — don't inline it.

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
- Nowy algorytm naliczania rabatów — szczegóły w [docs/changelog/2025-03-15-1.2.0-discount-algorithm.md](docs/changelog/2025-03-15-1.2.0-discount-algorithm.md)

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
  promocji czasowych i kuponów  ❌ Complex change without docs/changelog/* link
```

### Extended description in wrong location or with wrong filename

```markdown
### Added
- Nowy algorytm naliczania rabatów — szczegóły w [docs/discount-algorithm.md](docs/discount-algorithm.md)  ❌ Not under docs/changelog/
- Nowy algorytm naliczania rabatów — szczegóły w [docs/changelog/discount-algorithm.md](docs/changelog/discount-algorithm.md)  ❌ Missing date and version prefix
- Nowy algorytm naliczania rabatów — szczegóły w [docs/changelog/2025-03-15-discount-algorithm.md](docs/changelog/2025-03-15-discount-algorithm.md)  ❌ Missing version segment
```

**Correct:**
```markdown
### Added
- Nowy algorytm naliczania rabatów — szczegóły w [docs/changelog/2025-03-15-1.2.0-discount-algorithm.md](docs/changelog/2025-03-15-1.2.0-discount-algorithm.md)
```

### Promoting a version before production deployment

```markdown
## [1.3.0] - 2026-04-20         ❌ Version section created after stage-only deploy
### Added
- Nowy moduł rozliczeń
```

**Problem:** The code is running only on stage. Per INF-005.12, until it hits production the entries must stay in `[Unreleased]`.

**Correct:** Keep the entries in `[Unreleased]`; create `## [1.3.0] - <prod-date>` only on the production-deploy commit, and tag `v1.3.0` on that same commit.

### Versioned section without a matching git tag

```markdown
## [1.3.0] - 2026-04-20
### Added
- Nowa funkcja
```
```bash
$ git tag | grep v1.3.0
# (empty)                       ❌ No v1.3.0 tag on any commit
```

**Problem:** A `## [X.Y.Z]` heading without a `vX.Y.Z` git tag means either the release is not really on production, or the tag was forgotten. Both violate INF-005.13.

### Git tag without a matching versioned section

```bash
$ git tag
v1.3.0                          ❌ Tagged but still under [Unreleased]
```
```markdown
## [Unreleased]
### Added
- Nowa funkcja (już na produkcji jako v1.3.0)
```

**Problem:** A `vX.Y.Z` tag must correspond to a `## [X.Y.Z] - YYYY-MM-DD` section. Either promote the entries out of `[Unreleased]` or remove the tag if the release never reached production.

### Extended description with too much technical noise

```markdown
# Discount algorithm

## Zmiany w kodzie
- **DiscountCalculator::calculate()** — dodany parametr `tier`, refactor z strategy pattern  ❌ file path + implementation detail
- 12 testów jednostkowych, 3 integracyjne, 100% coverage na DiscountCalculator  ❌ test counts belong in MR description
- Przeanalizowaliśmy warianty: eager vs lazy evaluation, wybraliśmy eager bo ...  ❌ MR discussion, not changelog
```

**Correct — business and deployment focus:**
```markdown
# Nowy algorytm naliczania rabatów

## Problem
Stary algorytm nie obsługiwał rabatów grupowych i promocji czasowych łącznie — klient z kuponem tracił rabat grupowy.

## Rozwiązanie
- Rabat grupowy i kupon kumulują się dla zamówień powyżej 500 zł
- Promocje czasowe mają priorytet nad rabatami grupowymi w Black Friday

## Wdrożenie
- Migracja `2025_03_15_discount_tiers` musi być uruchomiona **przed** wdrożeniem kodu
- Wyczyść cache `orders:discount:*` w Redis po deployu
```

## Rules Summary

- **INF-005.1:** `CHANGELOG.md` must exist in the project root
- **INF-005.2:** File header must reference Keep a Changelog and Semantic Versioning conventions
- **INF-005.3:** `[Unreleased]` section must be at the top
- **INF-005.4:** Change categories in English, in order: Added → Changed → Deprecated → Removed → Fixed → Security
- **INF-005.5:** Change descriptions in Polish (preferred) or English — short and human-readable
- **INF-005.6:** Each version with date in `YYYY-MM-DD` format
- **INF-005.7:** Newest version on top
- **INF-005.8:** Complex/critical changes with link to an extended description under `docs/changelog/{YYYY-MM-DD}-{version}-{slug}.md` (inline entries stay in `CHANGELOG.md`; extended descriptions always live in `docs/changelog/` and are linked only when related; use `unreleased` as the version segment for changes still in the `[Unreleased]` section)
- **INF-005.11:** Extended descriptions are business- and deployment-focused (Problem → Rozwiązanie → Wdrożenie), without file paths, class names, test counts, or implementation walkthroughs — those belong in the MR description, the diff, or an ADR under `docs/architecture/`
- **INF-005.9:** Task-related changes with Jira link (where applicable)
- **INF-005.10:** Diff links at the bottom of the file
- **INF-005.12:** "Released" means running on production. Stage, test, dev, QA, preview deployments do not promote entries out of `[Unreleased]`. The date in `## [X.Y.Z] - YYYY-MM-DD` is the production deploy date
- **INF-005.13:** Every `## [X.Y.Z]` section must have a matching `vX.Y.Z` git tag on the commit that introduces the version bump; no `vX.Y.Z` tag may exist while the entries are still under `[Unreleased]`

## Rationale

1. **Transparency**: A changelog allows anyone (developer, PM, client) to quickly check what changed in a given version.

2. **Standardization**: One convention across all projects — no need to learn a new format when switching projects.

3. **Non-technical language**: Descriptions understandable by non-programmers facilitate communication with business stakeholders.

4. **Change tracking**: Jira links connect the changelog with the business context of the task.

5. **Complexity documentation**: Extended descriptions under `docs/changelog/{date}-{slug}.md` keep `CHANGELOG.md` short and scannable while preserving full context for critical changes. The date-prefixed filename makes chronological ordering and release-based grouping obvious at a glance.

6. **Semantic Versioning**: Pairing with SemVer makes it easy to understand the scale of changes between versions.

7. **Production-only releases**: Tying "released" to production deployment (not merge, not stage) makes the changelog a truthful record of what customers can actually use. Git tags mirror the same rule so a checkout of `vX.Y.Z` always matches what ran on prod — no ambiguity between "tagged", "merged", "on stage", and "on prod".

## Related Standards

- [INF-004: GitLab Merge Request Policy](./INF-004-gitlab-merge-request-policy.md) - merge request policy
- [INF-001: Local Development Makefile](./INF-001-infrastructure-local-makefile.md) - local project standards
