# INF-006: Jira Task Standard

**Documentation:** https://github.com/team-mate-pro/tmp-standards/blob/main/definitions/infrastructure/INF-006-jira-task-standard.md

## Check Method

| Method | Command |
|--------|---------|
| **AI** | `claude -p "$(cat vendor/team-mate-pro/tmp-standards/definitions/infrastructure/INF-006-jira-task-standard.prompt.txt)" --cwd .` |
| **MANUAL** | Review tickets during grooming / refinement |

## Definition

Every Jira ticket in TMP projects must follow a consistent structure, regardless of type (User Story / Task / Bug / Spike). A well-written ticket is understandable without external context and provides everything needed to start work (DoR) and to finish it (DoD).

Tickets are written for **humans** — both business and technical. The opening line must let any reader (PO, developer, tester) understand *who wants what and why* within seconds. Ticket description language: **Polish** (preferred) or English.

**A ticket describes WHAT has to be observable after the work is done, not HOW to build it.** The implementation is left to the developer picking up the ticket. See [INF-006.9](#rules-summary) and the `Uwagi techniczne` rules below.

## Issue Types

| Type | When to Use | Language / Tone |
|------|-------------|-----------------|
| **User Story** | A piece of observable value for a business / end user | Business only. No technical jargon (no class names, endpoints, tables, frameworks). Always in the `As a <role> I want <goal> so that <value>` form. |
| **Task** | Technical work that does not directly map to a user value (migration, refactor with observable effect, infra change). Technical vocabulary is allowed, but the ticket describes the **observable outcome**, not the implementation. |
| **Bug** | A defect in existing behaviour | Must contain: reproduction steps, expected result, actual result, environment. |
| **Spike** | Timeboxed research / investigation with a concrete deliverable (decision, document, PoC) | Neutral. Must state the question, the timebox and the expected deliverable. |

## Required Sections (all types)

Every ticket **must** contain — in this order:

1. **Title** — `[area][sub-area] - Short description` (max ~80 chars)
2. **Header** — Type, Project, Priority, Labels (Jira)
3. **Context** — 2–5 sentences: why the ticket exists, what the current state is, who raised it
4. **Opening line** — depends on type (see templates below)
5. **Acceptance Criteria** — checklist of observable outcomes
6. **Definition of Ready (DoR)** — checklist, see defaults below
7. **Definition of Done (DoD)** — checklist, see defaults below

Optional sections:

- **Uwagi techniczne / Technical notes** — see [Technical Notes — what belongs there](#technical-notes--what-belongs-there). **Never a solution recipe.**
- **Źródła / Sources** — links to transcripts, chats, docs, related tickets, mockups

## Opening Line Per Type

### User Story

Three-line format, **no technical jargon**, **no implementation hints**:

```
Jako <rola>
Chcę <cel wyrażony biznesowo>
Aby <wartość biznesowa / problem, który rozwiązuje>
```

The role must be a real user of the product (operator, patient, admin, external institution…), **never** "developer", "tester", "system".

### Task

A short paragraph under a **Cel / Goal** heading that describes the **observable** state of the system after the task is done — what a user, an admin, an API client, or monitoring will see differently. It must not prescribe the implementation.

```
## Cel
<1–3 sentences: what will be observably different after this task is done>
```

### Bug

Four dedicated sections are mandatory: `Kroki reprodukcji`, `Oczekiwane zachowanie`, `Aktualne zachowanie`, `Środowisko`. See the Bug template.

### Spike

```
## Pytanie
<the concrete research question this spike must answer>

## Timebox
<e.g. 1 day, 4 hours>

## Oczekiwany rezultat
<decision / document / PoC — exactly what will be delivered>
```

## Acceptance Criteria — Rules

- Each AC is an **observable**, **testable** outcome.
- Checkbox format (`- [ ]`).
- 3–7 bullets is the sweet spot. More than ~8 usually means the ticket should be split.
- AC must **not** prescribe the implementation (no class / file / table / endpoint names, no "add field X", "create migration Y", "refactor service Z"). Those decisions belong to the developer.
- For User Story — AC must stay in business language (what the user sees / can do), not technical language.

## Technical Notes — what belongs there

The `Uwagi techniczne` section is **optional** and exists **only** to record information the developer cannot derive from the rest of the ticket. It is **not** a solution sketch.

**Allowed in technical notes:**

- **Constraints** the implementation must respect (e.g. "existing public API contract is frozen", "must remain backwards-compatible with v1 clients", "runs under PHP 8.2").
- **Known gotchas / prior incidents** (e.g. "previous attempt failed because of a race condition in the webhook handler — link").
- **Pointers to related code or standards** (e.g. "see CC-003 for logging", "related to SRR-214").
- **Non-functional requirements** not expressible as AC (e.g. performance budget, rate limits).

**Forbidden in technical notes:**

- Concrete solution proposals ("add `ReporterType` enum", "create `InstitutionRepository`", "use Symfony Messenger").
- File / class / table / migration names.
- Step-by-step implementation plans.

If the author believes a specific implementation is required (e.g. for compliance), they must express it as an **AC** ("must log via PSR-3 with `exception` key") — not as a technical hint.

## Default DoR / DoD

Teams may extend these lists, but may not drop default items.

### Definition of Ready (default)

- [ ] Context and goal are clear to someone outside the team
- [ ] Acceptance Criteria are written and accepted by the PO
- [ ] Dependencies (other tickets, systems, decisions) are identified
- [ ] Design / mockup is attached (if the change touches UI)
- [ ] Estimation is done (if the team estimates)

### Definition of Done (default)

- [ ] Tests cover the new behaviour / a regression test for a bug
- [ ] Entry added in `CHANGELOG.md` under `[Unreleased]` (INF-005)
- [ ] Verified on the test / stage environment
- [ ] Accepted by the PO or the reporter

> The merge-to-`main` / green-CI steps are intentionally omitted — they are universal for every repository under INF-004 and do not need to be repeated in every ticket.

## Templates

> The templates below are in Polish on purpose — they are meant to be pasted into Jira tickets, which for TMP are written in Polish. The **section structure** and the **rules about no-implementation** apply identically to English-language tickets.

### User Story Template

```markdown
# [obszar][pod-obszar] - Krótki opis

**Typ:** User Story
**Projekt:** <nazwa projektu>
**Priorytet:** High | Medium | Low
**Etykiety (Jira):** <lista etykiet>

## Kontekst
<2–5 zdań: co jest dziś, kto zgłosił, jaki problem rozwiązujemy>

## User Story
> Jako <rola>
> Chcę <cel biznesowy>
> Aby <wartość / problem, który rozwiązuje>

## Acceptance Criteria
- [ ] <obserwowalny rezultat 1>
- [ ] <obserwowalny rezultat 2>
- [ ] <obserwowalny rezultat 3>

## Definition of Ready
- [ ] Kontekst i cel zrozumiałe dla osoby spoza zespołu
- [ ] AC zaakceptowane przez PO
- [ ] Zależności zidentyfikowane
- [ ] Mockup / design (jeśli UI)
- [ ] Estymacja

## Definition of Done
- [ ] Testy pokrywają funkcjonalność
- [ ] Wpis w `CHANGELOG.md` (INF-005)
- [ ] Zweryfikowane na stage
- [ ] Akceptacja PO

## Uwagi techniczne
<opcjonalne — wyłącznie ograniczenia, pointery, znane pułapki. Bez rozwiązań.>

## Źródła
<opcjonalne — linki do rozmów, dokumentów, zależnych zadań>
```

### Task Template

```markdown
# [obszar][pod-obszar] - Krótki opis

**Typ:** Task
**Projekt:** <nazwa projektu>
**Priorytet:** High | Medium | Low
**Etykiety (Jira):** <lista etykiet>

## Kontekst
<dlaczego to zadanie istnieje — problem, potrzeba>

## Cel
<1–3 zdania: co zmieni się w obserwowalnym zachowaniu systemu po wykonaniu zadania>

## Acceptance Criteria
- [ ] <obserwowalny rezultat 1>
- [ ] <obserwowalny rezultat 2>

## Definition of Ready
- [ ] Kontekst i cel zrozumiałe
- [ ] AC spisane
- [ ] Zależności zidentyfikowane
- [ ] Estymacja

## Definition of Done
- [ ] Testy
- [ ] Wpis w `CHANGELOG.md` (INF-005)
- [ ] Zweryfikowane na stage

## Uwagi techniczne
<opcjonalne — wyłącznie ograniczenia, pointery, znane pułapki. Bez rozwiązań.>

## Źródła
<opcjonalne>
```

### Bug Template

```markdown
# [obszar][pod-obszar] - Krótki opis błędu

**Typ:** Bug
**Projekt:** <nazwa projektu>
**Priorytet:** High | Medium | Low
**Etykiety (Jira):** <lista etykiet>

## Kontekst
<krótko: co się dzieje i dlaczego to problem>

## Kroki reprodukcji
1. ...
2. ...
3. ...

## Oczekiwane zachowanie
<co powinno się stać>

## Aktualne zachowanie
<co się faktycznie dzieje>

## Środowisko
- Wersja aplikacji: <np. commit SHA / tag>
- Przeglądarka / OS: <jeśli dotyczy>
- Rola / użytkownik: <jeśli dotyczy>

## Załączniki
<zrzut ekranu, log, nagranie — jeśli są>

## Definition of Ready
- [ ] Błąd reprodukowalny wg kroków powyżej
- [ ] Zrzut ekranu / log dołączony (jeśli dotyczy)
- [ ] Priorytet ustalony
- [ ] Zidentyfikowany zakres regresji (od kiedy występuje, jeśli wiadomo)

## Definition of Done
- [ ] Test regresyjny pokrywa przypadek
- [ ] Wpis w `CHANGELOG.md` → `Fixed` (INF-005)
- [ ] Zweryfikowane na stage

## Uwagi techniczne
<opcjonalne — hipoteza kierunku („wygląda na problem z deserializacją webhooka") lub znane pułapki. Bez konkretnego rozwiązania.>
```

### Spike Template

```markdown
# [spike][obszar] - Krótki opis pytania

**Typ:** Spike
**Projekt:** <nazwa projektu>
**Priorytet:** High | Medium | Low
**Etykiety (Jira):** <lista etykiet>

## Kontekst
<dlaczego pytanie się pojawia>

## Pytanie
<konkretne pytanie badawcze>

## Timebox
<np. 1 dzień, 4 godziny>

## Oczekiwany rezultat
<decyzja / dokument / PoC — co dokładnie zostanie dostarczone>

## Definition of Done
- [ ] Rezultat spisany w dokumencie (lub komentarzu do ticketa)
- [ ] Decyzja przedstawiona zespołowi / PO
- [ ] Jeśli potrzebne — założone follow-up zadania
```

## Correct Usage

### User Story — good example

```markdown
## User Story
> Jako pracownik działu reklamacji
> Chcę móc wybrać przy dodawaniu zgłoszenia, czy zgłaszającym jest pacjent czy instytucja
> Aby poprawnie zarejestrować pisma od NFZ, prokuratury i innych organów

## Acceptance Criteria
- [ ] Przy dodawaniu zgłoszenia widać wybór typu zgłaszającego
- [ ] Dla instytucji formularz prosi o inne dane niż dla pacjenta
- [ ] Na liście zgłoszeń widać, czy zgłoszenie pochodzi od pacjenta czy instytucji
- [ ] Listę można filtrować po typie zgłaszającego
```

No class names, no fields, no decisions about data model — the developer decides how to model it.

### Task — good example

```markdown
## Cel
Lista reklamacji pozwala filtrować po zakresie dat, a eksport CSV respektuje aktualnie ustawione filtry.

## Acceptance Criteria
- [ ] Na liście dostępny jest filtr zakresu dat (od / do)
- [ ] Eksport CSV zawiera tylko rekordy pasujące do aktywnych filtrów
- [ ] Szybkie skróty: „Ten miesiąc", „Poprzedni miesiąc"
- [ ] Eksport > 10 000 pozycji nie blokuje UI — użytkownik otrzymuje link po wygenerowaniu
```

The non-blocking export is expressed as observable behaviour, not as "use Messenger" or "use a queue".

## Violation Examples

### User Story with technical jargon

```markdown
## User Story
> Jako developer
> Chcę dodać pole `reporterType` do encji `Complaint`
> Aby w repozytorium dało się filtrować po typie     ❌
```

**Problem:** describes the implementation, not business value. The role "developer" is almost never correct for a User Story — the real role is the end user.

### Task prescribing implementation

```markdown
## Cel
Zrefaktorować klasę `OrderExporter`, wydzielić `CsvWriter`, dodać DI.     ❌
```

**Problem:** says *how*, not *what will change*. The goal should describe the observable effect, e.g. *"Eksport zamówień działa także dla list > 10 000 pozycji bez blokowania UI."*

### Acceptance Criteria as implementation steps

```markdown
## Acceptance Criteria
- [ ] Utworzyć migrację `AddReporterTypeToComplaint`   ❌
- [ ] Dodać form type `ReporterTypeType`               ❌
- [ ] Zarejestrować serwis w `services.yaml`           ❌
```

**Problem:** AC must describe observable outcomes. These are implementation steps — the developer's call, not the ticket's.

### Technical notes as a solution recipe

```markdown
## Uwagi techniczne
- Dodać kolumnę `reporter_type VARCHAR(32)` do tabeli `complaints`   ❌
- Użyć `Symfony\Form\ChoiceType` z opcjami `patient` / `institution` ❌
- Zarejestrować subscribera `ComplaintReporterTypeListener`          ❌
```

**Problem:** this is a solution, not a constraint. Leave the solution to the developer. If the author really needs something to be enforced, it must be an AC, e.g. *"Typ zgłaszającego jest trwale zapisany i można po nim filtrować listę oraz eksport"*.

### Bug without reproduction steps

```markdown
## Opis
Czasem nie wysyłają się SMS-y.     ❌
```

**Problem:** missing `Kroki reprodukcji`, `Oczekiwane zachowanie`, `Aktualne zachowanie`, `Środowisko`. The ticket is not ready for work.

### Missing DoD

```markdown
## Acceptance Criteria
- [ ] Działa filtr dat

(no Definition of Done)     ❌
```

**Problem:** no shared definition of "done" — no changelog entry, no stage verification, no tests. The ticket will bounce back in review.

## Rules Summary

- **INF-006.1:** Title follows the format `[area][sub-area] - Short description`
- **INF-006.2:** Header contains: Type, Project, Priority, Labels (Jira)
- **INF-006.3:** Every ticket has a `Kontekst / Context` section
- **INF-006.4:** User Story uses the `As a <role> / I want <goal> / So that <value>` form in business language; the role is a real user, never "developer" / "tester" / "system"
- **INF-006.5:** Task has a `Cel / Goal` section describing the **observable** state after the work is done, not the implementation
- **INF-006.6:** Bug contains `Kroki reprodukcji`, `Oczekiwane zachowanie`, `Aktualne zachowanie`, `Środowisko`
- **INF-006.7:** Spike contains `Pytanie`, `Timebox`, `Oczekiwany rezultat`
- **INF-006.8:** Every ticket has an `Acceptance Criteria` section as a checkbox list of observable outcomes
- **INF-006.9:** Neither the description, the goal, nor the AC prescribe the implementation — no class, file, table, endpoint, migration or library names. The "how" is the developer's decision.
- **INF-006.10:** Every ticket has a `Definition of Ready` section containing at least the default items
- **INF-006.11:** Every ticket has a `Definition of Done` section containing at least the default items (tests, `CHANGELOG.md` entry — INF-005, stage verification, acceptance). Obvious universal items (merge to `main`, green CI — covered by INF-004) must **not** be repeated in the DoD.
- **INF-006.12:** `Uwagi techniczne / Technical notes` is optional and contains only constraints, known gotchas, and pointers — **never** a solution recipe
- **INF-006.13:** Description language is Polish (preferred) or English, used consistently across the whole ticket

## Rationale

1. **Readability for non-technical stakeholders.** A jargon-free User Story lets the PO, client, and tester understand the scope without asking a developer.

2. **Scope control.** Observable AC double as test cases. If an AC cannot be verified without reading the code, it is written wrong.

3. **Separation of "what" from "how".** Keeping the implementation out of the ticket protects the ticket from rotting as the implementation evolves, and it respects the developer's judgement — they pick the right solution for the current codebase, not the one the author imagined at grooming time.

4. **DoR as the entry gate.** A ticket without a clear context, dependencies, and accepted AC should not enter the sprint. DoR prevents the team from starting unfinished ideas.

5. **DoD as the exit gate.** A changelog entry, a stage verification, and tests are part of being "done". Without them "done" quietly means "committed", which comes back as debt.

6. **Uniform bug format.** Reproduction steps, expected vs actual, and environment break the "cannot reproduce" loop that stalls defects.

## Related Standards

- [INF-004: GitLab Merge Request Policy](./INF-004-gitlab-merge-request-policy.md) — the merge-request policy referenced in DoD
- [INF-005: Changelog Following Keep a Changelog](./INF-005-changelog-keepachangelog.md) — the changelog entry is part of DoD
