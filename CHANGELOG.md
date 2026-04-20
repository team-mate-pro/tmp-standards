# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.3.0] - 2026-04-20

### Added
- Standard CC-003: logowanie wyjątków przez PSR-3 z kluczem `exception` w kontekście (Sentry/Monolog wyciąga stack trace automatycznie); doprecyzowuje relację do CC-002 Fail Fast — catch-log-swallow dozwolony wyłącznie na jawnych granicach (handlery kolejek, crony, listenery kernela, best-effort side effects)
- Reguła PHPStan `logger.exceptionContextKey` (`PsrLoggerExceptionContextKeyRule`) egzekwująca CC-003.1 — `\Throwable` w kontekście loggera PSR-3 musi być pod kluczem `'exception'`; inne klucze (`error`, `throwable`, `e`, `ex`, numeryczne) są flagowane
- Zależność `psr/log` (`^3.0`) w `require-dev` — potrzebna do fixturów i testów reguły

## [1.2.0] - 2026-04-17

### Added
- Standard INF-005: wymóg prowadzenia changelogu zgodnego z Keep a Changelog
- Standard CC-002: wzorzec Fail Fast z guard clauses i walidacją na granicy
- Identyfikatory sub-reguł (PREFIX-NNN.N) we wszystkich definicjach z wieloma regułami
- Instrukcje tworzenia definicji i prowadzenia changelogu w readme.md i CLAUDE.md

## [1.1.0] - 2025-03-01

### Added
- Standard INF-003: pipeline CI/CD z Docker na GitLab
- Standard INF-004: polityka merge requestów w GitLab
- Standard TEST-003: DTO w testach use case
- Makefile, konfiguracja Docker i narzędzia deweloperskie

### Fixed
- Poprawiono konfigurację Docker i PHPStan dla testów

## [1.0.3] - 2025-01-20

### Added
- Standard UCB-006: brak logiki bezpieczeństwa w kontrolerach w scope use case

## [1.0.2] - 2025-01-15

### Added
- Standard TEST-001: struktura katalogów testowych
- Standard TEST-002: testy behawioralne z wzorcem Given-When-Then
- Standard UCB-005: metody akcji kontrolera muszą mieć sufiks "Action"
- Standard FE-001, FE-002, FE-003: standardy komponentów frontendowych
- Standardy SOLID-001 do SOLID-005: zasady SOLID
- Standard ARCH-001: nazewnictwo tras REST API
- Standard ARCH-002: standardy architektury REST API

### Changed
- Dodano linki do dokumentacji w błędach reguł PHPStan

## [1.0.1] - 2025-01-10

### Added
- Standard CC-001: brak persystencji w wzorcach kreacyjnych
- Standard UCB-003: brak autoryzacji w warstwie use case
- Standard UCB-004: kontroler musi używać metody $this->response()

## [1.0.0] - 2025-01-01

### Added
- Pierwsza wersja pakietu z regułami PHPStan
- Standard UCB-001: parametry use case muszą być interfejsami
- Standard UCB-002: use case musi mieć metodę __invoke()
- Standard INF-001: Makefile do lokalnego developmentu

[Unreleased]: https://github.com/team-mate-pro/tmp-standards/compare/v1.3.0...HEAD
[1.3.0]: https://github.com/team-mate-pro/tmp-standards/compare/v1.2.0...v1.3.0
[1.2.0]: https://github.com/team-mate-pro/tmp-standards/compare/v1.1.0...v1.2.0
[1.1.0]: https://github.com/team-mate-pro/tmp-standards/compare/v1.0.3...v1.1.0
[1.0.3]: https://github.com/team-mate-pro/tmp-standards/compare/v1.0.2...v1.0.3
[1.0.2]: https://github.com/team-mate-pro/tmp-standards/compare/v1.0.1...v1.0.2
[1.0.1]: https://github.com/team-mate-pro/tmp-standards/compare/v1.0.0...v1.0.1
[1.0.0]: https://github.com/team-mate-pro/tmp-standards/releases/tag/v1.0.0
