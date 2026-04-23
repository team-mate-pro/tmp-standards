# INF-007: Client Feedback & Change Request Document

**Documentation:** https://github.com/team-mate-pro/tmp-standards/blob/main/definitions/infrastructure/INF-007-client-feedback-document.md

## Check Method

| Method | Command |
|--------|---------|
| **AI** | `claude -p "$(cat vendor/team-mate-pro/tmp-standards/definitions/infrastructure/INF-007-client-feedback-document.prompt.txt)" --cwd .` |
| **MANUAL** | Review before sending to the client |

## Definition

Every TMP project that communicates status, change requests and post-session findings with a client must use a **single, consistent document format** for that purpose. This document is the traceable, versioned artifact that aggregates:

- **Questions** from the client (post-demo, post-workshop, post-meeting),
- **Developer answers** (business-language — never code walk-throughs),
- **Status per issue** — separating fixes, changes and new features,
- **Links to Jira tickets** and to extended change descriptions (`docs/changelog/...` per INF-005),
- A **summary table** usable as a single-view status dashboard for the client.

The document complements, but does **not** replace:

- **`CHANGELOG.md` (INF-005)** — the canonical record of what shipped. The client document **links** to changelog entries; it does not duplicate them.
- **Jira tickets (INF-006)** — the backlog of *work*. The client document **points** to tickets; it does not restate Acceptance Criteria.
- **Specification / ADRs** — the canonical product decisions. The client document **references** the relevant spec sections; it does not redefine them.

The audience is **business**. The client must be able to read the document alone — without a developer at their side — and understand what is fixed, what is planned, what is asked, and what is still open. Technical vocabulary (class names, SQL, HTTP status codes, framework names) is forbidden. Names of integrated systems the client already knows (e.g. the client's own CRM, ERP, auth provider) are allowed because they carry business meaning.

Document language: **Polish** (preferred) or English, chosen per project and used consistently across the whole document.

## File Location & Naming

Client feedback documents live under `docs/` in the project repository, grouped by the source event:

```
docs/{YYYY-MM-DD-source-event}/{YYYY-MM-DD-document-slug}.md
```

- `{YYYY-MM-DD-source-event}` — date of the event that generated the feedback (demo / workshop / meeting) + a short slug (e.g. `2026-04-16-post-demo`). Multiple documents can live in the same folder (response v1, response v2, summary, follow-up).
- `{YYYY-MM-DD-document-slug}` — date the **document** itself was written / last revised + a short slug (e.g. `2026-04-20-odp-na-feedback-z-dnia-16-04`).
- Any supporting assets (screenshots, attachments) go into a sibling `assets/` folder inside the event directory.

Example layout:

```
docs/
└── 2026-04-16-post-demo/
    ├── 2026-04-16-feedback.odt                         # raw client feedback
    ├── 2026-04-20-odp-na-feedback-z-dnia-16-04.md      # our response (this standard)
    ├── 2026-04-20-odp-na-feedback-z-dnia-16-04.pdf     # rendered PDF sent to client
    └── assets/
        ├── feedback-2026-04-16-01-podglad-skierowania.png
        └── download-xls.png
```

## Required Structure

A compliant document is composed of **seven sections in this order**:

1. **YAML frontmatter (cover metadata)**
2. **Document title (`# ...`)**
3. **Draft banner** (only while the document is not yet finalized)
4. **Introduction** — 1–3 paragraphs: source event, scope, audience
5. **Conventions / Legend** — numbering scheme, icons, status glossary
6. **Thematic sections** — one `## [N.0] TOPIC` per functional area; questions under them
7. **Summary table** — every question listed with ID, topic, Jira link, status

### 1. YAML Frontmatter

Required keys:

```yaml
---
title: PMP — odpowiedzi na feedback z dnia 16.04.2026
subtitle: Post-demo (wersja robocza)
author: Sebastian Twaróg
version: v1.0 — wersja robocza
date: 2026-04-20
lang: pl
cover: true
toc: true
template: c1
---
```

| Key | Required | Notes |
|-----|:--------:|-------|
| `title` | ✓ | Short, client-facing title. Must name the product and the source event. |
| `subtitle` | — | Adds context (e.g. `Post-demo`, `Follow-up #2`). |
| `author` | ✓ | The person who owns the document on the delivery side. |
| `version` | ✓ | `v<major>.<minor>` (e.g. `v1.0`, `v1.2`). Bump minor when adding/changing content; bump major when the document is reorganized or re-scoped. Append `— wersja robocza` / `— draft` while not finalized. |
| `date` | ✓ | Last revision date in `YYYY-MM-DD`. |
| `lang` | ✓ | `pl` or `en`. Used consistently across the document. |
| `cover` | ✓ | `true` — the document is always rendered with a cover page. |
| `toc` | ✓ | `true` — the document always includes a table of contents. |
| `template` | — | PDF generator template name (project-specific). |

### 2. Title

The H1 under the frontmatter repeats the `title` field verbatim, so the document reads well when rendered without a cover page.

### 3. Draft Banner

While `version` ends with `— wersja robocza` / `— draft`, the document **must** carry a prominent banner at the top, e.g.:

```markdown
> [!WARNING]
> **Wersja robocza** — dokument w trakcie uzupełniania. Treści oraz oznaczenia ✅ „Zaadresowane" mogą ulec zmianie przed finalnym przekazaniem do klienta.
```

Remove the banner on the commit that promotes the document to the final version — at the same time drop the draft suffix from `version`.

### 4. Introduction

1–3 paragraphs, in business language, covering:

- **What this document is** — e.g. "answers to feedback from the 2026-04-16 post-demo meeting".
- **Perspectives / roles covered** — e.g. POLMED admin, contractor admin, contractor user.
- **How to read open items** — e.g. "items marked as TODO still need business clarification — they are not delivery promises".

### 5. Conventions / Legend

A callout that explains:

- The `[N.0]` (section) and `[N.K]` (question) numbering scheme.
- That the numbering is **stable** — once published, IDs never change, so third parties can refer to `[14.5]` unambiguously in email / chat / meetings.
- Icons used in the document (`✅` = already addressed; any other icons must be declared here).

### 6. Thematic Sections and Questions

Each functional area of the client's feedback is its own section:

```markdown
## [N.0] SECTION NAME IN UPPERCASE
```

Numbering starts at `1.0` and increases by whole numbers per section. Sub-numbers under a section (`[N.1]`, `[N.2]`, …) follow the order the client raised the issue in.

Each question within a section uses **exactly** this shape:

```markdown
<a id="q-N-K"></a>

*Pytanie:* [N.K] Original question from the client, verbatim or lightly cleaned for grammar. ([JIRA-123](https://…/JIRA-123))

> **Odpowiedź developera:**
>
> Answer in business language. Can span multiple paragraphs, bullet lists, and tables. Use callouts (`[!NOTE]`, `[!WARNING]`, `[!IMPORTANT]`) for emphasis.
>
> Reference the specification section when citing it (e.g. "spec 1.1.2 pkt 1.f").
> Link to related questions in the same document with section IDs ("see `[2.5]`") — never with prose-only references.
>
> ✅ **Zaadresowane** — short description of what changed and where it is visible (stage/prod).
>   Link to the extended description under `docs/changelog/{YYYY-MM-DD}-{version}-{slug}.md` (INF-005.11).
```

Components, all required where applicable:

- **Anchor** — `<a id="q-N-K"></a>` above every question so the summary table can deep-link. The ID pattern is `q-{section}-{question}` (e.g. `q-14-5`).
- **Question marker** — `*Pytanie:*` in italics + `[N.K]` + the question. Preserve the client's wording; minor grammar / punctuation edits are allowed. Never paraphrase into developer vocabulary.
- **Jira inline link** — when a ticket exists, append `([JIRA-123](…))` at the end of the question line (first mention).
- **Answer block** — a blockquote starting with `> **Odpowiedź developera:**`. Business language. No file paths, class names, method signatures, SQL, or framework names.
- **Cross-references** — "see `[N.K]`" with the linked ID; identical questions from different sections are answered **once** and linked from the others ("same answer as `[2.2]` — opiekun umowy").
- **Addressed marker** — `✅ **Zaadresowane**` at the bottom of the answer **only** when:
  1. the fix is running on stage or production, and
  2. a matching `CHANGELOG.md` entry exists under `[Unreleased]` or a released version (INF-005), and
  3. any extended description lives under `docs/changelog/` with the INF-005.8 filename format.
- **Open questions back to the business** — when we need the client to decide something, list the concrete questions at the end of the answer (e.g. "Pytania do biznesu:" with bullets). These are never implicit — they must be enumerated so the client can answer each one.

Complex answers may include screenshots placed inline with the question they relate to. Reference them with alt text that describes the content.

### 7. Summary Table

The document ends with a single table listing every question. The legend defining the status taxonomy (see below) sits above the table.

```markdown
## Podsumowanie — status zagadnień

> [!NOTE]
> **Legenda statusów** (skrótowo):
>
> **V1 — zakres pierwszego wydania (w granicach istniejącej specyfikacji):**
> - **V1 – naprawa** — naprawa błędu lub luki implementacyjnej mieszczącej się w spec.
> - **V1 – drobna zmiana wizualna** — zmiana etykiety / koloru / drobnego elementu UI.
> - **V1 – zmiana funkcjonalności** — modyfikacja istniejącego zachowania w zakresie spec.
>
> **Poza specyfikacją — wymaga osobnej wyceny i akceptacji biznesu:**
> - **Drobna zmiana nie ujęta w specyfikacji** — niewielkie uzupełnienie spoza spec.
> - **Nowa funkcjonalność** — znacząca nowa funkcja; wymaga rozszerzenia spec.
> - **Nie ujęte w specyfikacji** — zagadnienie wykraczające poza zakres spec.
>
> **Pozostałe:**
> - **Doprecyzowanie** — wymaga ustaleń biznesowych przed implementacją.
> - **Informacja** — pytanie wyjaśnione w odpowiedzi, bez odrębnego zadania.
>
> Ikona **✅** przed statusem = zagadnienie już zaadresowane (poprawka wdrożona).
> Kliknięcie ID (np. `[1.1]`) przenosi do odpowiedniego pytania w treści dokumentu.

| ID | Zagadnienie | Jira | Status |
|---|---|---|---|
| [[1.1](#q-1-1)] | Zmiana hasła użytkownika | — | Informacja |
| [[1.4](#q-1-4)] | Logo POLMED / kontrahenta na PDF | [INTPMP-121](https://…) | Doprecyzowanie |
| [[2.5](#q-2-5)] | Tylko firmy z dostępem do PMP na liście | [INTPMP-125](https://…) | ✅ V1 – naprawa |
| [[4.2](#q-4-2)] | Numer kontaktowy w profilu użytkownika | [INTPMP-146](https://…) | Nowa funkcjonalność |
| [[14.15](#q-14-15)] | Miejsce wystawienia: podgląd vs PDF | [INTPMP-135](https://…) | ✅ V1 – naprawa |
```

Rules for the summary:

- Every `[N.K]` question from the body **must** appear in the table — no silent drops.
- The first cell uses `[[N.K](#q-N-K)]` so the ID is clickable.
- The topic column is a short, business-language label — not a copy of the question.
- The Jira column lists every Jira link mentioned in the answer (comma-separated for multi-ticket items) or `—` when none exists.
- The status column uses **exactly** one status from the taxonomy (see below). The `✅` prefix is added on the same line when the item is already addressed.

## Status Taxonomy

Exactly **one** status applies per question. This taxonomy is part of the standard — projects must not invent ad-hoc labels. If a project finds a gap, extend this standard; do not branch.

### V1 scope — within existing specification

| Status | When to use |
|--------|-------------|
| `V1 – naprawa` | Bug or implementation gap covered by the existing spec. Going from "broken / missing" to "working as specified". |
| `V1 – drobna zmiana wizualna` | Label / colour / copy / small UI tweak. No logic change. |
| `V1 – zmiana funkcjonalności` | Modification of existing behaviour that still fits the spec (correction or refinement of logic). |

### Outside specification — needs separate estimation & business acceptance

| Status | When to use |
|--------|-------------|
| `Drobna zmiana nie ujęta w specyfikacji` | Small addition the spec does not mention; green-lit after a short alignment. |
| `Nowa funkcjonalność` | Meaningful new feature. Requires spec extension, estimate, formal acceptance. |
| `Nie ujęte w specyfikacji` | Topic outside current scope — either an oversight or a new area. Spec extension needed before work can start. |

### Other

| Status | When to use |
|--------|-------------|
| `Doprecyzowanie` | Requires business decisions / clarification before any implementation can be scoped. |
| `Informacja` | Question answered inline; no ticket, no work to track. |

Features, changes and fixes are **distinguished by status**, not by mixing them under one catch-all label. This lets the client read the summary table and immediately see "what is delivery", "what is new", and "what needs their input".

## Change Log of the Document Itself

The document is versioned in its frontmatter, not in git commits alone. Any time you change the document after the client has seen it:

1. Bump `version` (minor for content additions; major for structural rework).
2. Update `date` to the revision date.
3. If adding new `[N.K]` questions, append at the end of the section with the **next unused sub-number** — never renumber existing ones.
4. Add a short `## Historia zmian dokumentu` / `## Document changelog` at the bottom (after the summary table) listing `v1.1 — YYYY-MM-DD — added [14.16] …` one line per revision. Keep it to a single scroll.

## Correct Usage

```markdown
---
title: PMP — odpowiedzi na feedback z dnia 16.04.2026
subtitle: Post-demo (wersja robocza)
author: Sebastian Twaróg
version: v1.0 — wersja robocza
date: 2026-04-20
lang: pl
cover: true
toc: true
template: c1
---

# PMP — odpowiedzi na feedback z dnia 16.04.2026

> [!WARNING]
> **Wersja robocza** — dokument w trakcie uzupełniania. Treści oraz oznaczenia ✅ „Zaadresowane" mogą ulec zmianie przed finalnym przekazaniem do klienta.

Dokument zawiera odpowiedzi developera na uwagi i pytania zebrane po demo PMP, które odbyło się **16.04.2026**. Pierwsza część dotyczy perspektywy Administratora POLMED, następnie Administratora kontrahenta oraz Użytkownika kontrahenta.

> [!NOTE]
> **Oznaczenia w nawiasach kwadratowych (`[N.0]`, `[N.K]`):**
> - `[N.0]` — numer sekcji / zagadnienia (np. `[1.0] ADMINISTRATOR POLMED`).
> - `[N.K]` — numer konkretnego pytania w sekcji (np. `[1.4]`).
>
> Numeracja jest stabilna w obrębie tego dokumentu. Ikona **✅** przed statusem = zagadnienie zaadresowane.

## [2.0] STRONA STARTOWA

<a id="q-2-5"></a>

*Pytanie:* [2.5] Czy na stronie startowej będą wyświetlać się tylko firmy, które mają PMP, czy wszystkie? ([INTPMP-125](https://jira.example/INTPMP-125))

> **Odpowiedź developera:**
>
> Obecnie wyświetlane są wszystkie firmy pobierane z Atinea — bez rozróżnienia, czy mają aktywny dostęp do PMP. Specyfikacja (sekcja 5 pkt 3) zakłada, że w PMP widoczne są wyłącznie firmy z aktywnym „Dostępem do PMP" i aktywną umową.
>
> Naprawa obejmuje wprowadzenie filtrowania po oznaczeniu dostępu do PMP, które Atinea już dostarcza przy każdej ofercie umownej.
>
> ✅ **Zaadresowane** — filtr wdrożony po stronie integracji z Atinea; do listy trafiają wyłącznie oferty z aktywnym dostępem do PMP. Szczegóły: [docs/changelog/2026-04-20-unreleased-pmp-access-filter.md](../changelog/2026-04-20-unreleased-pmp-access-filter.md).

## Podsumowanie — status zagadnień

> [!NOTE]
> Legenda statusów — patrz sekcja „Konwencje". Kliknięcie ID przenosi do pytania.

| ID | Zagadnienie | Jira | Status |
|---|---|---|---|
| [[2.5](#q-2-5)] | Tylko firmy z dostępem do PMP | [INTPMP-125](https://jira.example/INTPMP-125) | ✅ V1 – naprawa |
```

## Violation Examples

### Missing YAML frontmatter / cover / toc

```markdown
# PMP — odpowiedzi na feedback            ❌ No frontmatter
Dokument zawiera odpowiedzi…
```

**Problem:** without `cover: true` and `toc: true` the rendered PDF lacks the cover page and the table of contents — two hard requirements for a client-facing artifact.

### Version-less or date-less document

```markdown
---
title: PMP — odpowiedzi na feedback
author: Sebastian Twaróg
---                                       ❌ Missing version and date
```

**Problem:** the client has no way to tell two revisions apart.

### Answer in technical language

```markdown
> **Odpowiedź developera:**
>
> Dodaliśmy pole `pmpAccess` do encji `Contractor` i filtr w `HttpContractorRepository::findAll()`  ❌
> — migracja `2026_04_20_add_pmp_access.sql` już wdrożona na stage.  ❌
```

**Problem:** class names, repository names, SQL migration names — the client cannot read this. The answer must describe observable behaviour instead.

**Correct:**

```markdown
> **Odpowiedź developera:**
>
> Na liście firm domyślnie widoczne są wyłącznie firmy z aktywnym dostępem do PMP. Pozostałe pozycje nie są pobierane. ✅ Wdrożone na stage.
```

### Numbering not stable — questions renumbered after publication

```markdown
<!-- v1.0 miał [14.5] = gwiazdka WST -->
## [14.0] WYSTAWIENIE SKIEROWANIA

<a id="q-14-5"></a>
*Pytanie:* [14.5] Zapisywanie skierowania bez adresu        ❌ v1.1 changed what [14.5] points to
```

**Problem:** external references (emails, Slack) now point to the wrong question. Always append with the next unused number — never shift existing ones.

### `✅ Zaadresowane` without a CHANGELOG.md entry

```markdown
> ✅ **Zaadresowane** — poprawka wdrożona na stage.
```

```markdown
# CHANGELOG.md
## [Unreleased]
### Fixed
(empty)                                   ❌ No matching entry
```

**Problem:** the document claims the item is done, but no changelog record exists. INF-005 is the canonical source of truth — without a matching entry the claim is not traceable.

### Summary table missing questions

```markdown
| ID | Zagadnienie | Jira | Status |
|---|---|---|---|
| [[2.5](#q-2-5)] | … | … | ✅ V1 – naprawa |
| [[14.15](#q-14-15)] | … | … | ✅ V1 – naprawa |
<!-- [14.1] .. [14.14] missing -->         ❌
```

**Problem:** the summary is the client's single-view dashboard. Dropping items silently breaks the contract.

### Status taxonomy not respected

```markdown
| [[9.1](#q-9-1)] | Rozszerzone statusy | [INTPMP-149](https://…) | TODO        ❌
| [[9.2](#q-9-2)] | Filtr na rodzaj badań | [INTPMP-137](https://…) | in progress ❌
```

**Problem:** `TODO` / `in progress` are not in the taxonomy. The client does not know if these are fixes, features, or blocked on their input. Use a defined status (e.g. `Nowa funkcjonalność`, `V1 – naprawa`).

### Features, changes and fixes mixed under one label

```markdown
| [[4.2](#q-4-2)] | Numer kontaktowy w profilu użytkownika | [INTPMP-146](https://…) | V1 – naprawa ❌
```

**Problem:** adding a new phone-number field is a **new feature** — it was not in the spec. Labelling it `V1 – naprawa` hides that it needs scope / estimate approval. Use `Nowa funkcjonalność`.

### Draft document sent to client without the draft banner

```markdown
---
version: v1.0 — wersja robocza
---

# PMP — odpowiedzi na feedback z dnia 16.04.2026

Dokument zawiera odpowiedzi…            ❌ No draft banner
```

**Problem:** the client receives a draft and assumes the answers are final. Always carry the `> [!WARNING] Wersja robocza` banner while the version string contains the draft suffix.

### Identical question answered twice with divergent answers

```markdown
## [2.0] STRONA STARTOWA
*Pytanie:* [2.2] Opiekun umowy w podglądzie …
> Obecnie nie jest zwracany przez Atinea. Wymaga rozbudowy integracji.

## [5.0] FIRMY
*Pytanie:* [5.2] Opiekun umowy w podglądzie firmy …
> Analogiczne do [2.2], ale teraz sugerujemy włączenie do V1.     ❌
```

**Problem:** two different answers to the same business question → the client cannot tell which is binding. Answer once; link the duplicate back to the canonical answer.

## Rules Summary

- **INF-007.1:** Document has YAML frontmatter with `title`, `author`, `version`, `date`, `lang`, `cover: true`, `toc: true` (and optional `subtitle`, `template`).
- **INF-007.2:** `version` follows `v<major>.<minor>` (draft suffix `— wersja robocza` / `— draft` allowed until finalized); `date` is the document revision date in `YYYY-MM-DD`.
- **INF-007.3:** File lives under `docs/{YYYY-MM-DD-event-slug}/{YYYY-MM-DD-document-slug}.md`; assets under a sibling `assets/` folder in the event directory.
- **INF-007.4:** Draft documents carry a visible `> [!WARNING] Wersja robocza` banner at the top until the draft suffix is dropped from `version`.
- **INF-007.5:** The document opens with a 1–3 paragraph introduction naming the source event, covered perspectives, and how open items are marked.
- **INF-007.6:** A conventions / legend section explains `[N.0]` / `[N.K]` numbering and any icons (at minimum `✅`).
- **INF-007.7:** Thematic sections use `## [N.0] TOPIC IN UPPERCASE` with stable numbering.
- **INF-007.8:** Every question has an anchor `<a id="q-N-K"></a>`, a verbatim `*Pytanie:* [N.K] …` line, and a `> **Odpowiedź developera:**` blockquote.
- **INF-007.9:** Numbering is stable — once a `[N.K]` is published it is never reassigned; new entries append with the next unused sub-number.
- **INF-007.10:** The answer is in business language — no class / file / table / endpoint / library / framework / SQL / migration names. Names of integrated systems the client already knows (e.g. the client's own CRM, auth provider) are allowed.
- **INF-007.11:** Features, changes and fixes are distinguished by using a **different status** from the taxonomy — never grouped under a single catch-all label.
- **INF-007.12:** Status per question uses exactly one value from the INF-007 status taxonomy (V1 – naprawa / V1 – drobna zmiana wizualna / V1 – zmiana funkcjonalności / Drobna zmiana nie ujęta w specyfikacji / Nowa funkcjonalność / Nie ujęte w specyfikacji / Doprecyzowanie / Informacja).
- **INF-007.13:** Every Jira ticket referenced in an answer is linked at first mention and in the summary table.
- **INF-007.14:** `✅ Zaadresowane` is set only when (a) the change is on stage/production, (b) a matching `CHANGELOG.md` entry exists per INF-005, and (c) any extended description lives under `docs/changelog/{date}-{version}-{slug}.md` per INF-005.8/INF-005.11.
- **INF-007.15:** A summary table at the end lists every `[N.K]` with ID (clickable via `#q-N-K`), topic, Jira link(s), and status. No question may be missing from the table.
- **INF-007.16:** Duplicate or near-duplicate questions are answered once; the remaining occurrences link back to the canonical answer (`patrz [N.K]`).
- **INF-007.17:** A `Historia zmian dokumentu` / `Document changelog` section at the end logs each post-publication revision (`vX.Y — YYYY-MM-DD — summary`).

## Rationale

1. **One artifact, one source of truth.** Clients routinely receive emails, PDFs, and Jira exports that contradict each other. A single versioned document with a summary table closes that gap — everyone references the same IDs.

2. **Business language is non-negotiable.** The reader is a product owner, a contractor admin, a medical operations lead. Class names and SQL snippets do not help them approve scope — they obscure it.

3. **Stable IDs survive email threads.** `[14.5]` said once in a meeting still means the same thing six weeks later. Renumbering would silently break every external reference.

4. **A strict status taxonomy separates fixes from features.** Without it "V1 bug fix" and "new paid feature" bleed into each other and the client ends up surprised at estimate time. The taxonomy forces the conversation up-front.

5. **Traceability with INF-005 prevents false claims.** `✅ Zaadresowane` without a changelog entry is a promise with no evidence. Wiring the two together means the document cannot drift away from what actually shipped.

6. **Short-circuit repeat questions.** Clients naturally ask the same question under several sections ("opiekun umowy" in `[2.2]`, `[5.2]`, `[6.2]`, `[13.1]`). Answering once and linking keeps the document consistent and short.

7. **The draft banner is a safety net.** Drafts leak. A persistent warning at the top of the document prevents the client from treating a working version as final.

## Related Standards

- [INF-005: Changelog Following Keep a Changelog](./INF-005-changelog-keepachangelog.md) — `✅ Zaadresowane` items must have a matching `CHANGELOG.md` entry; extended descriptions live under `docs/changelog/`.
- [INF-006: Jira Task Standard](./INF-006-jira-task-standard.md) — questions linked from the client document point at tickets that follow INF-006.
- [INF-004: GitLab Merge Request Policy](./INF-004-gitlab-merge-request-policy.md) — the merge / release path that makes "addressed" real.
