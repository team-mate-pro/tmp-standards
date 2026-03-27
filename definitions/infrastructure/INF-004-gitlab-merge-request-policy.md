# INF-004: GitLab Merge Request Policy

**Documentation:** https://github.com/team-mate-pro/tmp-standards/blob/main/definitions/infrastructure/INF-004-gitlab-merge-request-policy.md

## Check Method

| Method | Command |
|--------|---------|
| **MANUAL** | GitLab UI: Settings → Merge requests / Settings → Repository → Protected branches |

## Definition

All TMP GitLab projects must enforce consistent merge request policies to ensure code quality and prevent accidental commits to protected branches.

## Required Settings

### Merge Request Settings

Every project **must** have these settings enabled in `Settings → Merge requests`:

| Setting | Required Value | Description |
|---------|----------------|-------------|
| `only_allow_merge_if_pipeline_succeeds` | `true` | Cannot merge if pipeline fails or has conflicts |
| `only_allow_merge_if_all_discussions_are_resolved` | `true` | All review comments must be addressed |

### Merge Request Approvals (Premium/Ultimate)

If available, configure approval rules in `Settings → Merge requests → Merge request approvals`:

| Setting | Required Value | Description |
|---------|----------------|-------------|
| Approvals required | `1` (minimum) | At least one approval required |
| Prevent author approval | `true` | Author cannot approve their own MR |
| Prevent committers approval | `true` | Committers cannot approve |
| Reset approvals on push | `true` | New commits require re-approval |
| Require CODEOWNERS approval | `true` | Code owners must approve |

### Protected Branches

Every project **must** protect `master`, `main`, and `stage` branches (whichever exist) with:

| Setting | Required Value | Description |
|---------|----------------|-------------|
| Allowed to push | Maintainers (40) | Developers cannot push directly |
| Allowed to merge | Maintainers (40) | Only maintainers can merge MRs |
| Allowed to force push | No one | Force push disabled |
| Code owner approval | Required | CODEOWNERS must approve |

### Additional Security Settings

Configure in `Settings → Repository → Branch defaults`:

| Setting | Required Value | Description |
|---------|----------------|-------------|
| Default branch | `master` or `main` | Consistent default branch |
| Auto-close issues | `true` | Issues closed when MR merged |

Configure in `Settings → General → Visibility`:

| Setting | Required Value | Description |
|---------|----------------|-------------|
| Project visibility | `Private` | Internal projects only |
| Forks | Disabled | Prevent unauthorized forks |

## GitLab API Configuration

### Merge Request Settings

```bash
# Enable merge request policy for a project
curl --request PUT \
  --header "PRIVATE-TOKEN: $GL_TOKEN" \
  "https://gitlab.team-mate.pl/api/v4/projects/$PROJECT_ID" \
  --data "only_allow_merge_if_pipeline_succeeds=true" \
  --data "only_allow_merge_if_all_discussions_are_resolved=true"
```

### Protected Branch Settings

```bash
# Protect a branch (Maintainers only)
curl --request POST \
  --header "PRIVATE-TOKEN: $GL_TOKEN" \
  "https://gitlab.team-mate.pl/api/v4/projects/$PROJECT_ID/protected_branches" \
  --data "name=master" \
  --data "push_access_level=40" \
  --data "merge_access_level=40" \
  --data "allow_force_push=false"
```

### Access Level Reference

| Level | Role |
|-------|------|
| 0 | No access |
| 30 | Developer |
| 40 | Maintainer |
| 60 | Admin |

## Correct Configuration

### GitLab UI Checklist

1. **Settings → Merge requests**
   - [x] Pipelines must succeed
   - [x] All discussions must be resolved

2. **Settings → Repository → Protected branches**
   - [x] `master` / `main` protected
   - [x] `stage` protected (if exists)
   - [x] Push: Maintainers only
   - [x] Merge: Maintainers only
   - [x] Force push: Disabled

## Violation Examples

### Missing Pipeline Requirement

```
Settings → Merge requests:
  Pipelines must succeed: ❌ Disabled
```

**Problem:** MRs can be merged even with failing tests or conflicts.

### Developers Can Push to Protected Branch

```
Protected branch 'master':
  Allowed to push: Developers + Maintainers ❌
```

**Problem:** Developers can bypass code review by pushing directly.

### No Protected Branches

```
Protected branches: (none)
```

**Problem:** Anyone can push or force push to any branch.

## Rationale

1. **Code Quality**: Requiring passing pipelines prevents broken code from being merged.

2. **Code Review**: Requiring resolved discussions ensures feedback is addressed.

3. **Branch Protection**: Preventing direct pushes to protected branches enforces the MR workflow.

4. **Audit Trail**: All changes go through merge requests, providing clear history.

5. **Consistency**: Same policy across all projects reduces confusion.

## Automation

Use the following script to verify and fix settings across all projects:

```bash
#!/bin/bash
GL_TOKEN="your-token"
GL_URL="https://gitlab.team-mate.pl/api/v4"

# Get all project IDs
PROJECT_IDS=$(curl -s -H "PRIVATE-TOKEN: $GL_TOKEN" \
  "$GL_URL/groups/team-mate-pro/projects" | jq -r '.[].id')

for id in $PROJECT_IDS; do
  # Update merge request settings
  curl -s --request PUT \
    -H "PRIVATE-TOKEN: $GL_TOKEN" \
    "$GL_URL/projects/$id" \
    --data "only_allow_merge_if_pipeline_succeeds=true" \
    --data "only_allow_merge_if_all_discussions_are_resolved=true"

  echo "Updated project $id"
done
```

## Related Standards

- [INF-003: CI/CD Pipeline Docker GitLab](./INF-003-cicd-pipeline-docker-gitlab.md) - Pipeline configuration
- [INF-001: Local Development Makefile](./INF-001-infrastructure-local-makefile.md) - Local `make check` parity
