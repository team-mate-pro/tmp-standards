# CC-001: No Persistence in Creational Patterns

## Check Method

| Method | Command |
|--------|---------|
| **AI** | `claude -p "$(cat vendor/team-mate-pro/tmp-standards/definitions/clean-code/CC-001-no-persist-in-creational-patterns.prompt.txt)" --cwd .` |

## Definition

Creational patterns (Factory, Builder) must **NOT** call `persist()`, `flush()`, or any other persistence methods. They should only build and return the entity/object. Persistence is the responsibility of a higher layer (UseCase, Controller, Service).

## Applies To

- Factory classes (`*Factory`)
- Builder classes (`*Builder`)
- Any class implementing creational design patterns

## Correct Usage

```php
// Factory only creates the entity
final readonly class PlayerFactory
{
    public function create(string $name, Team $team): Player
    {
        $player = new Player(
            id: Uuid::v4(),
            name: $name,
            team: $team,
            createdAt: new \DateTimeImmutable(),
        );

        return $player; // Return without persisting
    }
}

// UseCase handles persistence
final readonly class CreatePlayerUseCase
{
    public function __construct(
        private PlayerFactory $factory,
        private PlayerRepositoryInterface $repository,
    ) {
    }

    public function __invoke(CreatePlayerDtoInterface $dto): Result
    {
        $player = $this->factory->create(
            $dto->getName(),
            $dto->getTeam(),
        );

        $this->repository->save($player); // Persistence in UseCase

        return Result::success($player);
    }
}
```

```php
// Builder only builds the entity
final class MatchBuilder
{
    private Team $homeTeam;
    private Team $awayTeam;
    private \DateTimeImmutable $date;

    public function withHomeTeam(Team $team): self
    {
        $this->homeTeam = $team;
        return $this;
    }

    public function withAwayTeam(Team $team): self
    {
        $this->awayTeam = $team;
        return $this;
    }

    public function withDate(\DateTimeImmutable $date): self
    {
        $this->date = $date;
        return $this;
    }

    public function build(): Match
    {
        return new Match(
            id: Uuid::v4(),
            homeTeam: $this->homeTeam,
            awayTeam: $this->awayTeam,
            date: $this->date,
        );
    }
}
```

## Violation

```php
// WRONG: Factory calls persist
final readonly class PlayerFactory
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {
    }

    public function create(string $name, Team $team): Player
    {
        $player = new Player(
            id: Uuid::v4(),
            name: $name,
            team: $team,
        );

        $this->em->persist($player); // VIOLATION!
        $this->em->flush();          // VIOLATION!

        return $player;
    }
}
```

```php
// WRONG: Builder persists on build
final class MatchBuilder
{
    public function __construct(
        private MatchRepositoryInterface $repository,
    ) {
    }

    public function build(): Match
    {
        $match = new Match(...);

        $this->repository->save($match); // VIOLATION!

        return $match;
    }
}
```

## Rationale

1. **Single Responsibility**: Factories/Builders have one job - creating objects. Adding persistence violates SRP.

2. **Testability**: When factories persist, unit tests require database mocking. Pure factories are trivial to test.

3. **Transaction Control**: The calling layer (UseCase) should control transaction boundaries. Multiple entities may need to be persisted atomically.

4. **Reusability**: A factory that persists cannot be used when you need an unpersisted entity (e.g., for preview, validation, or batch operations).

5. **Explicit Flow**: Persistence is a significant side effect. Making it explicit in the UseCase improves code readability and debugging.

## Allowed Persistence Layers

Persistence should happen in:
- UseCase classes
- Command handlers
- Event handlers
- Repository methods called by above

## Exception

Test fixtures and seeders may use factories with persistence for convenience, but production code must follow this standard.
