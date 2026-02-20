# UCB-003: No Authorization Decisions in UseCase Layer

**Documentation:** https://github.com/team-mate-pro/tmp-standards/blob/main/definitions/use-case-bundle/UCB-003-no-auth-in-use-case.md

## Check Method

| Method | Command |
|--------|---------|
| **AI** | `claude -p "$(cat vendor/team-mate-pro/tmp-standards/definitions/use-case-bundle/UCB-003-no-auth-in-use-case.prompt.txt)" --cwd .` |

## Definition

UseCase classes must **NOT** inject or use security/authorization services. Authorization decisions must be made in the presentation layer (Controller, CLI Command) **before** invoking the UseCase. If user identity or permissions are needed for business logic, they should be passed via the DTO interface.

## Forbidden in UseCase

### Symfony Security Injections

- `Security`
- `AuthorizationCheckerInterface`
- `TokenStorageInterface`
- `AccessDecisionManagerInterface`
- `UserInterface` (direct injection, not via DTO)

### Attributes/Annotations

- `#[IsGranted]`
- `@IsGranted`
- `#[Security]`
- `@Security`

### Method Calls

- `isGranted()`
- `denyAccessUnlessGranted()`
- `getUser()`
- `getToken()`

## Correct Usage

```php
// Controller handles authorization
#[Route('/articles/{id}/publish', methods: ['POST'])]
#[IsGranted('ROLE_EDITOR')]
final class PublishArticleController
{
    public function __construct(
        private PublishArticleUseCase $useCase,
        private Security $security,
    ) {
    }

    public function __invoke(PublishArticleRequest $request): JsonResponse
    {
        // Authorization in Controller
        $user = $this->security->getUser();

        if (!$this->security->isGranted('ARTICLE_PUBLISH', $request->getArticle())) {
            throw new AccessDeniedException();
        }

        // Pass user identity via DTO if needed for business logic
        $request->setEditorId($user->getId());

        // UseCase handles business logic only
        $result = ($this->useCase)($request);

        return new JsonResponse($result);
    }
}

// DTO Interface defines what business data is needed
interface PublishArticleDtoInterface
{
    public function getArticleId(): string;
    public function getEditorId(): string; // User identity passed via DTO
}

// UseCase is free of authorization concerns
final readonly class PublishArticleUseCase
{
    public function __construct(
        private ArticleRepositoryInterface $articleRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function __invoke(PublishArticleDtoInterface $dto): Result
    {
        $article = $this->articleRepository->find($dto->getArticleId());

        $article->publish($dto->getEditorId());

        $this->articleRepository->save($article);

        $this->eventDispatcher->dispatch(new ArticlePublishedEvent($article));

        return Result::success($article);
    }
}
```

## Violation

```php
// WRONG: UseCase injects Security
final readonly class PublishArticleUseCase
{
    public function __construct(
        private ArticleRepositoryInterface $articleRepository,
        private Security $security, // VIOLATION!
    ) {
    }

    public function __invoke(PublishArticleDtoInterface $dto): Result
    {
        // VIOLATION: Authorization in UseCase
        if (!$this->security->isGranted('ARTICLE_PUBLISH')) {
            throw new AccessDeniedException();
        }

        $user = $this->security->getUser(); // VIOLATION!

        // ...
    }
}
```

```php
// WRONG: UseCase uses AuthorizationCheckerInterface
final readonly class DeleteCommentUseCase
{
    public function __construct(
        private CommentRepositoryInterface $commentRepository,
        private AuthorizationCheckerInterface $authChecker, // VIOLATION!
    ) {
    }

    public function __invoke(DeleteCommentDtoInterface $dto): Result
    {
        $comment = $this->commentRepository->find($dto->getCommentId());

        // VIOLATION: Authorization check in UseCase
        $this->authChecker->isGranted('DELETE', $comment);

        // ...
    }
}
```

```php
// WRONG: UseCase has security attribute
#[IsGranted('ROLE_ADMIN')] // VIOLATION!
final readonly class BanUserUseCase
{
    public function __invoke(BanUserDtoInterface $dto): Result
    {
        // ...
    }
}
```

## What UseCase Layer Should Do

### Should Do

- Focus on a single business intention/action
- Accept inputs via abstract DTO interface
- Orchestrate domain objects and repositories
- Apply business rules and validation
- Return result objects
- Emit domain events

### Should NOT Do

- Make authorization decisions
- Access security context or current user directly
- Handle HTTP concerns (requests, responses, sessions)
- Handle framework-specific input binding
- Catch and translate framework exceptions
- Log user activity (cross-cutting concern)

## Rationale

1. **Separation of Concerns**: UseCase represents business intent. Controller handles HTTP and authorization. Mixing these concerns creates tightly coupled, hard-to-maintain code.

2. **Testability**: UseCases can be unit tested without mocking security context. Tests focus on business logic, not framework infrastructure.

3. **Reusability**: The same UseCase can be invoked from HTTP Controller, CLI Command, Message Queue consumer, or GraphQL resolver without duplicating authorization logic.

4. **Single Entry Point**: Authorization happens at the system boundary (Controller/CLI). This ensures consistent security enforcement and makes it easier to audit.

5. **Clean Architecture**: Domain layer (UseCase) must not depend on infrastructure (Security). Dependencies should point inward, not outward.

6. **Explicit Data Flow**: When user identity is needed for business logic, passing it via DTO makes the dependency explicit and traceable.

7. **Framework Independence**: UseCases remain portable across frameworks. Symfony Security is infrastructure; business logic should not depend on it.

## Passing User Identity to UseCase

When the UseCase needs user information for business logic (e.g., audit trail, ownership), pass it via the DTO:

```php
// DTO Interface
interface CreateDocumentDtoInterface
{
    public function getTitle(): string;
    public function getAuthorId(): string; // User identity for business logic
}

// Controller extracts and passes user data
$request->setAuthorId($this->security->getUser()->getId());
($this->useCase)($request);

// UseCase uses the data for business logic
$document = new Document(
    title: $dto->getTitle(),
    authorId: $dto->getAuthorId(), // Clean business data, no security dependency
);
```

## Exception

There are no exceptions to this rule. All authorization must happen before the UseCase is invoked.
