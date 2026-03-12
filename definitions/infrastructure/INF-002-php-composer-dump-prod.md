# INF-002: Composer Environment Dump for Production Builds

**Documentation:** https://github.com/team-mate-pro/tmp-standards/blob/main/definitions/infrastructure/INF-002-php-composer-dump-prod.md

## Check Method

| Method | Command |
|--------|---------|
| **AI** | `claude -p "$(cat vendor/team-mate-pro/tmp-standards/definitions/infrastructure/INF-002-php-composer-dump-prod.prompt.txt)" --cwd .` |

## Definition

Production and staging Docker builds for PHP/Symfony applications **must** include `composer dump-env <env>` to generate an optimized `.env.local.php` file. This eliminates runtime parsing of `.env` files and improves application bootstrap performance.

## Required Configuration

For production builds:

```dockerfile
RUN composer dump-env prod
```

For staging builds:

```dockerfile
RUN composer dump-env stage
```

## Correct Usage

### Production Dockerfile Stage

```dockerfile
FROM base AS prod

ENV APP_ENV=prod
ENV COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /app

COPY .env.prod .env.prod

# Install dependencies with full optimization
RUN composer install --no-scripts --no-ansi --no-interaction --no-progress --optimize-autoloader --no-dev \
    && composer dump-autoload --no-dev --classmap-authoritative

# Generate optimized .env.local.php for faster environment loading
RUN composer dump-env prod

# Warm up Symfony cache
RUN php bin/console cache:warmup --env=prod --no-debug
```

### Staging Dockerfile Stage

```dockerfile
FROM base AS stage

ENV APP_ENV=stage
ENV COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /app

COPY .env.stage .env.stage

# Install dependencies with optimization
RUN composer install --no-scripts --no-ansi --no-interaction --no-progress --optimize-autoloader \
    && composer dump-autoload --classmap-authoritative

# Generate optimized .env.local.php for stage environment
RUN composer dump-env stage

# Warm up Symfony cache for stage
RUN php bin/console cache:warmup --env=stage || true
```

## Generated File

The `composer dump-env <env>` command generates a `.env.local.php` file:

```php
<?php
// .env.local.php (auto-generated, do not edit)
return [
    'APP_ENV' => 'prod',
    'APP_SECRET' => '...',
    'DATABASE_URL' => '...',
    // ... all environment variables compiled as PHP array
];
```

This file is loaded directly by Symfony's `Dotenv` component, bypassing the slower `.env` file parsing.

## Violation

```dockerfile
# WRONG: Missing composer dump-env in production stage
FROM base AS prod

ENV APP_ENV=prod

RUN composer install --no-dev --optimize-autoloader

# Missing: composer dump-env prod
# Application will parse .env files on EVERY request

RUN php bin/console cache:warmup --env=prod
```

```dockerfile
# WRONG: Wrong environment specified
FROM base AS prod

ENV APP_ENV=prod

RUN composer dump-env dev  # Should be: composer dump-env prod
```

```dockerfile
# WRONG: Using dump-env in development stage
FROM base AS dev

ENV APP_ENV=dev

# Unnecessary: dev environment should use .env files for flexibility
RUN composer dump-env dev
```

## When NOT to Use

- **Development environments**: Keep `.env` file parsing for flexibility during development
- **Test environments**: May need dynamic environment switching
- **Local Docker builds**: Use only for production-like deployments

## Prerequisites

The `composer dump-env` command requires the Symfony DotEnv component:

```bash
composer require symfony/dotenv
```

## Rationale

1. **Performance**: Eliminates `.env` file parsing on every request. PHP array loading is significantly faster than parsing dotenv syntax.

2. **Immutability**: Environment variables are compiled at build time, ensuring consistency across container restarts.

3. **Reduced I/O**: No filesystem reads for environment configuration during runtime.

4. **Security**: Environment file is compiled into PHP, reducing exposure of raw `.env` files in production.

5. **Symfony Best Practice**: Officially recommended by Symfony for production deployments. See [Symfony Environment Variables documentation](https://symfony.com/doc/current/configuration.html#configuring-environment-variables-in-production).

6. **Docker Layer Caching**: The generated `.env.local.php` becomes part of the Docker image layer, enabling efficient caching and deployment.

## Performance Impact

| Method | Approximate Time per Request |
|--------|------------------------------|
| `.env` file parsing | ~1-2ms |
| `.env.local.php` loading | ~0.1ms |

For high-traffic applications, this optimization compounds significantly across thousands of requests.
