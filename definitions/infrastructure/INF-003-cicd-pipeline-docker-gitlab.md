---
title: "INF-003: CI/CD Pipeline with Docker on GitLab"
subtitle: "Infrastructure standard"
author: "TMP Standards"
version: "v1.0"
date: 2026-05-04
template: sh
cover: true
toc: true
toc-depth: 3
---

# INF-003: CI/CD Pipeline with Docker on GitLab

**Documentation:** https://github.com/team-mate-pro/tmp-standards/blob/main/definitions/infrastructure/INF-003-cicd-pipeline-docker-gitlab.md

## Check Method

| Method | Command |
|--------|---------|
| **AI** | `claude -p "$(cat vendor/team-mate-pro/tmp-standards/definitions/infrastructure/INF-003-cicd-pipeline-docker-gitlab.prompt.txt)" --cwd .` |

## Definition

GitLab CI/CD pipelines for PHP/Symfony projects **must** follow a consistent structure with Docker-based jobs. Pipelines run on:
- **Merge Requests**: Static analysis runs automatically, functional tests are manual
- **Protected branches** (`main`, `master`, `stage`): Full pipeline runs — static analysis, functional tests (automatic), build, and deploy

Commits prefixed with `HOTFIX:` bypass all pipeline stages entirely, allowing emergency deployments without CI checks.

## Pipeline Stages

Every project must define exactly four stages in this order:

```yaml
stages:
  - static_analysis
  - test
  - build
  - deploy
```

| Stage | Runs on MR | Runs on push to protected branch |
|-------|-----------|----------------------------------|
| `static_analysis` | Yes (auto) | Yes (auto) |
| `test` | Yes (manual) | Yes (auto, after static_analysis) |
| `build` | No | Yes (after test passes) |
| `deploy` | No | Yes (after build) |

> **Note:** Functional tests (`test` stage) are **manual on MRs** but **automatic on protected branches** (after static analysis passes). On MRs, they require explicit trigger because they need external services (database, queues, etc.). On protected branches, they must pass to ensure code quality before deployment.

## Pipeline Rules

### INF-003.1: HOTFIX bypass

Commits with message starting with `HOTFIX:` skip all pipeline stages:

```yaml
workflow:
  rules:
    - if: '$CI_COMMIT_TITLE =~ /^HOTFIX:/'
      when: never
    - when: always
```

### INF-003.2: Static analysis — MR and protected branches

Static analysis jobs run automatically on:
- Open Merge Requests (`merge_request_event`)
- Pushes to protected branches (`main`, `master`, `stage`)

```yaml
.rules_for_tests: &rules_for_tests
  rules:
    - if: '$CI_COMMIT_TITLE =~ /^HOTFIX:/'
      when: never
    - if: '$CI_PIPELINE_SOURCE == "merge_request_event"'
    - if: '$CI_COMMIT_BRANCH =~ /^(main|master|stage)$/'
```

### INF-003.3: Functional tests — manual on MR, automatic on protected branches

Functional tests have different behavior depending on context:
- On **MRs**: Manual trigger (requires developer to click "play")
- On **protected branches**: Automatic after static analysis passes

This ensures:
- Developers can choose when to run resource-intensive tests during MR review
- Code merged to protected branches is always tested before deployment

```yaml
.rules_for_functional_tests: &rules_for_functional_tests
  rules:
    - if: '$CI_COMMIT_TITLE =~ /^HOTFIX:/'
      when: never
    - if: '$CI_PIPELINE_SOURCE == "merge_request_event"'
      when: manual
    - if: '$CI_COMMIT_BRANCH =~ /^(main|master|stage)$/'
      when: on_success
```

### INF-003.4: Build and deploy — protected branches only, after tests pass

Build and deploy jobs run only on pushes to protected branches (`main`, `master`, `stage`) and only after functional tests pass:

```yaml
.rules_for_build: &rules_for_build
  rules:
    - if: '$CI_COMMIT_TITLE =~ /^HOTFIX:/'
      when: never
    - if: '$CI_COMMIT_BRANCH =~ /^(main|master|stage)$/'
      when: on_success
```

> **Important:** On protected branches, functional tests run automatically after static analysis. Build and deploy stages run only after tests pass successfully. This ensures production/stage deployments always have passing tests.

## Correct Usage

### Full `.gitlab-ci.yml`

```yaml
image: docker:20.10

services:
  - docker:20.10-dind

variables:
  DOCKER_HOST: tcp://docker:2375
  DOCKER_DRIVER: overlay2
  DOCKER_TLS_CERTDIR: ""
  FF_NETWORK_PER_BUILD: 1

stages:
  - static_analysis
  - test
  - build
  - deploy

# -------------------- Workflow --------------------
# Skip entire pipeline for HOTFIX commits

workflow:
  rules:
    - if: '$CI_COMMIT_TITLE =~ /^HOTFIX:/'
      when: never
    - when: always

# -------------------- Reusable Rules --------------------

.rules_for_tests: &rules_for_tests
  rules:
    - if: '$CI_COMMIT_TITLE =~ /^HOTFIX:/'
      when: never
    - if: '$CI_PIPELINE_SOURCE == "merge_request_event"'
    - if: '$CI_COMMIT_BRANCH =~ /^(main|master|stage)$/'

.rules_for_functional_tests: &rules_for_functional_tests
  rules:
    - if: '$CI_COMMIT_TITLE =~ /^HOTFIX:/'
      when: never
    - if: '$CI_PIPELINE_SOURCE == "merge_request_event"'
      when: manual
    - if: '$CI_COMMIT_BRANCH =~ /^(main|master|stage)$/'
      when: on_success

.before_script_template: &prepare-ssh
  before_script:
    - 'command -v ssh-agent >/dev/null || ( apk add --update openssh )'
    - eval $(ssh-agent -s)
    - echo "$SSH_PRIVATE_KEY" | tr -d '\r' | ssh-add -
    - mkdir -p ~/.ssh
    - chmod 700 ~/.ssh
    - ssh-keyscan $VM_IP_ADDRESS >> ~/.ssh/known_hosts
    - chmod 644 ~/.ssh/known_hosts
    - echo "$SSH_PRIVATE_KEY" > ~/.ssh/id_rsa
    - chmod 600 ~/.ssh/id_rsa
    - ssh -o StrictHostKeyChecking=no $VM_USER@$VM_IP_ADDRESS "ls -l -a"

# -------------------- Static Analysis --------------------

phpcs:
  stage: static_analysis
  <<: *rules_for_tests
  script:
    - docker-compose -f docker-compose.gitlab-qa.yml run --rm tests_phpcs

phpstan:
  stage: static_analysis
  <<: *rules_for_tests
  script:
    - docker-compose -f docker-compose.gitlab-qa.yml run --rm tests_phpstan

tests_unit:
  stage: static_analysis
  <<: *rules_for_tests
  script:
    - docker-compose -f docker-compose.gitlab-qa.yml run --rm tests_unit

# -------------------- Tests --------------------

functional_tests:
  stage: test
  <<: *rules_for_functional_tests
  before_script:
    - apk add --no-cache bash
  script:
    - chmod +x ./tools/test/test.sh
    - bash tools/test/test.sh

# -------------------- Build: Production --------------------

build_app_production:
  stage: build
  rules:
    - if: '$CI_COMMIT_BRANCH =~ /^(main|master)$/'
      when: on_success
  script:
    - echo "$CI_REGISTRY_PASSWORD" | docker login $CI_REGISTRY --username "$CI_REGISTRY_USER" --password-stdin
    - docker build -f docker/app/Dockerfile --target prod -t $CI_REGISTRY/$CI_PROJECT_PATH/app:prod .
    - docker push $CI_REGISTRY/$CI_PROJECT_PATH/app --all-tags

build_proxy_production:
  stage: build
  rules:
    - if: '$CI_COMMIT_BRANCH =~ /^(main|master)$/'
      when: on_success
  script:
    - echo "$CI_REGISTRY_PASSWORD" | docker login $CI_REGISTRY --username "$CI_REGISTRY_USER" --password-stdin
    - docker build -f docker/proxy/Dockerfile -t $CI_REGISTRY/$CI_PROJECT_PATH/proxy:prod .
    - docker push $CI_REGISTRY/$CI_PROJECT_PATH/proxy --all-tags

# -------------------- Deploy: Production --------------------

deploy_production:
  image: alpine:3.17
  stage: deploy
  <<: *prepare-ssh
  needs:
    - job: build_app_production
    - job: build_proxy_production
  rules:
    - if: '$CI_COMMIT_BRANCH =~ /^(main|master)$/'
      when: on_success
  script:
    - scp -i ~/.ssh/id_rsa docker-compose.prod.yml $VM_USER@$VM_IP_ADDRESS:~/docker-compose.prod.yml
    - ssh $VM_USER@$VM_IP_ADDRESS "docker compose -f docker-compose.prod.yml pull && exit"
    - ssh $VM_USER@$VM_IP_ADDRESS "docker compose -f docker-compose.prod.yml down --remove-orphans && exit"
    - ssh $VM_USER@$VM_IP_ADDRESS "docker compose -f docker-compose.prod.yml up --build --force-recreate -d && exit"
    - ssh $VM_USER@$VM_IP_ADDRESS "docker compose -f docker-compose.prod.yml exec app sh -c 'chmod +x ./tools/prod/post_deploy.sh && ./tools/prod/post_deploy.sh && exit' && exit"
    - ssh $VM_USER@$VM_IP_ADDRESS "docker system prune -af --volumes && exit"

# -------------------- Build: Stage --------------------

build_app_stage:
  stage: build
  rules:
    - if: '$CI_COMMIT_BRANCH == "stage"'
      when: on_success
  script:
    - echo "$CI_REGISTRY_PASSWORD" | docker login $CI_REGISTRY --username "$CI_REGISTRY_USER" --password-stdin
    - docker build -f docker/app/Dockerfile --target stage -t $CI_REGISTRY/$CI_PROJECT_PATH/app:stage .
    - docker push $CI_REGISTRY/$CI_PROJECT_PATH/app:stage

build_proxy_stage:
  stage: build
  rules:
    - if: '$CI_COMMIT_BRANCH == "stage"'
      when: on_success
  script:
    - echo "$CI_REGISTRY_PASSWORD" | docker login $CI_REGISTRY --username "$CI_REGISTRY_USER" --password-stdin
    - docker build -f docker/proxy/Dockerfile --target stage -t $CI_REGISTRY/$CI_PROJECT_PATH/proxy:stage .
    - docker push $CI_REGISTRY/$CI_PROJECT_PATH/proxy:stage

# -------------------- Deploy: Stage --------------------

deploy_stage:
  image: alpine:3.17
  stage: deploy
  <<: *prepare-ssh
  variables:
    VM_IP_ADDRESS: $STAGE_VM_IP_ADDRESS
    VM_USER: $STAGE_VM_USER
  needs:
    - job: build_app_stage
    - job: build_proxy_stage
  rules:
    - if: '$CI_COMMIT_BRANCH == "stage"'
      when: on_success
  script:
    - scp -i ~/.ssh/id_rsa docker-compose.stage.yml $VM_USER@$VM_IP_ADDRESS:~/docker-compose.stage.yml
    - scp -i ~/.ssh/id_rsa .env.stage $VM_USER@$VM_IP_ADDRESS:~/.env.stage
    - ssh $VM_USER@$VM_IP_ADDRESS "docker compose -p backend-stage -f docker-compose.stage.yml pull && exit"
    - ssh $VM_USER@$VM_IP_ADDRESS "docker compose -p backend-stage -f docker-compose.stage.yml down --remove-orphans && exit"
    - ssh $VM_USER@$VM_IP_ADDRESS "docker compose -p backend-stage -f docker-compose.stage.yml --env-file .env.stage up --build --force-recreate -d && exit"
    - ssh $VM_USER@$VM_IP_ADDRESS "docker compose -p backend-stage -f docker-compose.stage.yml exec app sh -c 'chmod +x ./tools/stage/post_deploy.sh && ./tools/stage/post_deploy.sh && exit' && exit"
```

## Violation

### Missing HOTFIX bypass

```yaml
# WRONG: No workflow rule to skip HOTFIX commits
# All commits trigger the pipeline, including emergency hotfixes

stages:
  - static_analysis
  - test
  - build
  - deploy

phpcs:
  stage: static_analysis
  rules:
    - if: '$CI_PIPELINE_SOURCE == "merge_request_event"'
  script:
    - docker-compose -f docker-compose.gitlab-qa.yml run --rm tests_phpcs
```

### Missing HOTFIX check in functional tests

```yaml
# WRONG: Functional tests will run even on HOTFIX commits
# Emergency fixes should bypass all CI stages

functional_tests:
  stage: test
  rules:
    - if: '$CI_PIPELINE_SOURCE == "merge_request_event"'
      when: manual
    - if: '$CI_COMMIT_BRANCH =~ /^(main|master|stage)$/'
      when: on_success
    # Missing: HOTFIX bypass rule at the start
  script:
    - bash tools/test/test.sh
```

### Functional tests always manual

```yaml
# WRONG: Functional tests are manual on protected branches
# Deployments can happen without tests passing

functional_tests:
  stage: test
  rules:
    - if: '$CI_PIPELINE_SOURCE == "merge_request_event"'
      when: manual
    - if: '$CI_COMMIT_BRANCH =~ /^(main|master|stage)$/'
      when: manual   # Should be: when: on_success
  script:
    - bash tools/test/test.sh
```

## Project Structure

Each PHP project typically has two Docker services that must be built and deployed:

| Service | Dockerfile | Purpose |
|---------|-----------|---------|
| `app` | `docker/app/Dockerfile` | PHP-FPM application (always present) |
| `proxy` | `docker/proxy/Dockerfile` | Nginx reverse proxy (present when project serves HTTP) |

Both services follow the same multi-stage build pattern with `stage` and `prod` targets.

## Required CI/CD Variables

### GitLab built-in variables (automatic, no setup needed)

| Variable | Value | Description |
|----------|-------|-------------|
| `CI_REGISTRY` | `gitlab.team-mate.pl` | GitLab Container Registry URL |
| `CI_REGISTRY_USER` | `gitlab-ci-token` | Registry auth username |
| `CI_REGISTRY_PASSWORD` | `$CI_JOB_TOKEN` | Registry auth token (auto-generated per job) |
| `CI_PROJECT_PATH` | `group/project-name` | Full project path for image tagging |

### Custom variables (must be added manually)

| Variable | Description |
|----------|-------------|
| `SSH_PRIVATE_KEY` | SSH key for deployment server access |
| `VM_IP_ADDRESS` | Production server IP |
| `VM_USER` | Production server SSH user |
| `STAGE_VM_IP_ADDRESS` | Stage server IP |
| `STAGE_VM_USER` | Stage server SSH user |

### Setting variables via GitLab CLI (`glab`)

Install the GitLab CLI: https://gitlab.com/gitlab-org/cli

```bash
# Set all required custom CI/CD variables for a project
# Registry variables (CI_REGISTRY, CI_REGISTRY_USER, CI_REGISTRY_PASSWORD) are built-in — no setup needed
# Use --masked for sensitive values (keys) and --protected to limit to protected branches

# SSH key for deployment
# 1. Generate a dedicated deploy key (do NOT reuse personal keys)
#    ssh-keygen -t ed25519 -C "gitlab-ci-deploy" -f ~/.ssh/gitlab_deploy_key -N ""
# 2. Add the public key to the target server(s)
#    ssh-copy-id -i ~/.ssh/gitlab_deploy_key.pub deploy@10.0.0.1
#    ssh-copy-id -i ~/.ssh/gitlab_deploy_key.pub deploy@10.0.0.2
# 3. Store the private key as a CI/CD variable (--type file for multiline SSH keys)
glab variable set SSH_PRIVATE_KEY --value "$(cat ~/.ssh/gitlab_deploy_key)" --type file --masked --protected

# Production server
glab variable set VM_IP_ADDRESS --value "10.0.0.1" --protected
glab variable set VM_USER --value "deploy" --protected

# Stage server
glab variable set STAGE_VM_IP_ADDRESS --value "10.0.0.2" --protected
glab variable set STAGE_VM_USER --value "deploy" --protected
```

```bash
# Verify all variables are set
glab variable list
```

```bash
# Update an existing variable
glab variable update VM_IP_ADDRESS --value "10.0.0.5" --protected
```

```bash
# Set variables for a specific project (when not in the project directory)
glab variable set VM_IP_ADDRESS --value "10.0.0.1" --protected --repo "group/project-name"
```

> **Note:** Always use `--masked` for secrets (passwords, tokens, keys) to prevent them from appearing in job logs. Use `--protected` to ensure variables are only available in pipelines running on protected branches.

## Rationale

1. **Manual tests on MRs, automatic on protected branches**: On MRs, functional tests are manual because they require external services and take time — developers trigger them when ready. On protected branches, tests run automatically to ensure deployed code is always validated.

2. **HOTFIX bypass**: Emergency fixes to production need to skip the pipeline to minimize downtime. The `HOTFIX:` prefix provides an explicit, auditable opt-out mechanism.

3. **Docker-in-Docker execution**: All test and analysis jobs run inside Docker containers via `docker-compose.gitlab-qa.yml`, ensuring environment parity between local development and CI.

4. **Separate app/proxy builds**: Independent build jobs allow proxy-only or app-only deploys, reducing build times when only one service changes.

5. **Sequential dependencies on protected branches**: Static analysis → functional tests → build → deploy. Each stage depends on the previous passing, ensuring quality gates before deployment.
