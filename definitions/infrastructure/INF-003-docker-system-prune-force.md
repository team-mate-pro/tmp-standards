# INF-003: Docker System Prune Non-Interactive Mode

**Documentation:** https://github.com/team-mate-pro/tmp-standards/blob/main/definitions/infrastructure/INF-003-docker-system-prune-force.md

## Check Method

| Method | Command |
|--------|---------|
| **AI** | `claude -p "$(cat vendor/team-mate-pro/tmp-standards/definitions/infrastructure/INF-003-docker-system-prune-force.prompt.txt)" --cwd .` |

## Definition

All `docker system prune` commands in deployment scripts, CI/CD pipelines, and automation scripts **must** include the `-f` or `--force` flag. This prevents interactive confirmation prompts that would cause deployments to hang indefinitely.

## Required Flags

| Flag | Description |
|------|-------------|
| `-f` / `--force` | **Required** - Skip confirmation prompt |
| `-a` / `--all` | Optional - Remove all unused images, not just dangling ones |
| `--volumes` | Optional - Prune anonymous volumes |

## Correct Usage

### CI/CD Pipeline (GitHub Actions)

```yaml
- name: Clean up Docker resources
  run: docker system prune -f
```

### Deployment Script

```bash
#!/bin/bash
# Clean up unused Docker resources before deployment
docker system prune -f

# Or with additional cleanup options
docker system prune -af --volumes
```

### Makefile Target

```makefile
prune: ### Remove unused Docker resources
	docker system prune -f
```

### Ansible Playbook

```yaml
- name: Clean up unused Docker resources
  command: docker system prune -f
  become: yes
```

### Docker Compose Override

```yaml
services:
  cleanup:
    image: docker:cli
    command: ["system", "prune", "-f"]
    volumes:
      - /var/run/docker.sock:/var/run/docker.sock
```

## Violation

```bash
# WRONG: Missing -f flag - will prompt for confirmation and hang
docker system prune

# WRONG: Missing -f flag with other options
docker system prune -a
docker system prune --volumes
docker system prune -a --volumes
```

```yaml
# WRONG: GitHub Actions - missing -f flag
- name: Clean up Docker
  run: docker system prune  # Will hang waiting for user input
```

```makefile
# WRONG: Makefile target without -f
prune:
	docker system prune  # Will hang in non-interactive environments
```

## Related Commands

The same principle applies to other Docker prune commands used in automation:

| Command | Non-Interactive Form |
|---------|---------------------|
| `docker system prune` | `docker system prune -f` |
| `docker container prune` | `docker container prune -f` |
| `docker image prune` | `docker image prune -f` |
| `docker volume prune` | `docker volume prune -f` |
| `docker network prune` | `docker network prune -f` |
| `docker builder prune` | `docker builder prune -f` |

## Scope

This rule applies to:

- CI/CD pipeline configurations (GitHub Actions, GitLab CI, Jenkins, etc.)
- Deployment scripts (`deploy.sh`, `release.sh`, etc.)
- Makefiles with Docker cleanup targets
- Ansible playbooks and roles
- Kubernetes Jobs/CronJobs executing Docker commands
- Any automated script that runs without human interaction

## Exceptions

Interactive `-f` flag is **not required** when:

- Running commands manually in a terminal
- Documentation examples showing the interactive prompt behavior
- Test scripts that explicitly test confirmation behavior

## Rationale

1. **Deployment Reliability**: Automated deployments must never hang waiting for user input. A missing `-f` flag will cause the deployment to wait indefinitely for a "y/N" response.

2. **CI/CD Pipeline Stability**: CI runners execute scripts non-interactively. Missing `-f` causes pipeline timeouts or failures.

3. **Resource Management**: Regular pruning prevents disk space exhaustion on deployment servers. Unreliable pruning (due to hangs) leads to storage issues.

4. **Consistency**: Always using `-f` ensures predictable behavior across all environments.

5. **Docker Best Practice**: Docker documentation recommends using `-f` for scripted/automated cleanup operations.

## Common Patterns

### Pre-deployment Cleanup

```bash
#!/bin/bash
set -e

echo "Cleaning up Docker resources..."
docker system prune -f

echo "Pulling latest images..."
docker compose pull

echo "Starting services..."
docker compose up -d
```

### Scheduled Cleanup (Cron)

```bash
# /etc/cron.daily/docker-cleanup
#!/bin/bash
docker system prune -af --volumes 2>&1 | logger -t docker-cleanup
```

### Post-deployment Cleanup

```yaml
# GitHub Actions
jobs:
  deploy:
    steps:
      - name: Deploy application
        run: ./deploy.sh

      - name: Cleanup unused resources
        run: docker system prune -f
        if: always()
```
