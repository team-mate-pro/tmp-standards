---
title: "INF-004: GitLab Merge Request Policy"
subtitle: "Infrastructure standard"
author: "TMP Standards"
version: "v1.0"
date: 2026-05-04
template: sh
cover: true
toc: true
toc-depth: 3
---

# INF-004: GitLab Merge Request Policy

**Documentation:** https://github.com/team-mate-pro/tmp-standards/blob/main/definitions/infrastructure/INF-004-gitlab-merge-request-policy.md

## Check Method

| Method | Command |
|--------|---------|
| **MANUAL** | GitLab UI: Settings → Merge requests / Settings → Repository → Protected branches |

## Definition

All TMP GitLab projects must enforce consistent merge request policies to ensure code quality and prevent accidental commits to protected branches.

## Required Settings

### Merge Method

Every project **must** use **Fast-forward merge** in `Settings → Merge requests → Merge method`:

| Setting | Required Value | Description |
|---------|----------------|-------------|
| Merge method | `Fast-forward merge` | No merge commits, linear history only |

**Why Fast-forward merge:**
- **Clean linear history**: All commits appear in sequence on the main branch without merge commit noise
- **Forces rebasing**: Developers must rebase on latest target branch before merging, ensuring conflicts are resolved early
- **Easier bisecting**: Linear history makes `git bisect` more effective for finding bugs
- **Cleaner blame**: `git blame` shows the actual author, not merge commit authors

**Trade-off**: Feature branch integration points are not explicitly marked (no merge commits). Use descriptive commit messages and MR links in commits to maintain traceability.

### Merge Request Settings

Every project **must** have these settings enabled in `Settings → Merge requests`:

| Setting | Required Value | Description |
|---------|----------------|-------------|
| Merge method | `Fast-forward merge` | No merge commits created, linear history |
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

Every project **must** automatically protect `master`, `main`, and `stage` branches (whichever exist) with:

| Setting | Required Value | Description |
|---------|----------------|-------------|
| Allowed to push | Maintainers (40) | Developers cannot push directly |
| Allowed to merge | Maintainers (40) | Only maintainers can merge MRs |
| Allowed to force push | No one | Force push disabled |
| Code owner approval | Required | CODEOWNERS must approve |

**Mandatory branches to protect:**
- `main` - primary branch (new projects)
- `master` - primary branch (legacy projects)
- `stage` - staging/pre-production branch (if exists)

**Important:** These branches must be protected immediately when created. Use the automation script below to ensure compliance.

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
  --data "merge_method=ff" \
  --data "only_allow_merge_if_pipeline_succeeds=true" \
  --data "only_allow_merge_if_all_discussions_are_resolved=true"
```

**Merge method API values:**
| UI Value | API Value |
|----------|-----------|
| Merge commit | `merge` |
| Merge commit with semi-linear history | `rebase_merge` |
| Fast-forward merge | `ff` |

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
   - [x] Merge method: Fast-forward merge
   - [x] Pipelines must succeed
   - [x] All discussions must be resolved

2. **Settings → Repository → Protected branches**
   - [x] `master` / `main` protected
   - [x] `stage` protected (if exists)
   - [x] Push: Maintainers only
   - [x] Merge: Maintainers only
   - [x] Force push: Disabled

## Violation Examples

### Wrong Merge Method

```
Settings → Merge requests:
  Merge method: Merge commit ❌
```

**Problem:** Creates unnecessary merge commits, resulting in a non-linear history that's harder to navigate and bisect.

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

1. **Linear History**: Fast-forward merge creates a clean, linear commit history without merge commit noise, making `git log`, `git bisect`, and `git blame` more effective.

2. **Early Conflict Resolution**: Requiring rebase before merge forces developers to resolve conflicts against the latest target branch, not during the merge.

3. **Code Quality**: Requiring passing pipelines prevents broken code from being merged.

4. **Code Review**: Requiring resolved discussions ensures feedback is addressed.

5. **Branch Protection**: Preventing direct pushes to protected branches enforces the MR workflow.

6. **Audit Trail**: All changes go through merge requests, providing clear history.

7. **Consistency**: Same policy across all projects reduces confusion.

## Automation

Use the following script to verify and fix settings across all projects:

```bash
#!/bin/bash
# INF-004 Compliance Script
# Configures all GitLab projects according to merge request policy

GL_TOKEN="${GL_TOKEN:-your-token}"
GL_URL="${GL_URL:-https://gitlab.team-mate.pl/api/v4}"

# Groups to process (space-separated)
GROUPS="team-mate-pro sh"

# Branches that must be protected
PROTECTED_BRANCHES="main master stage"

protect_branch() {
  local project_id=$1
  local branch=$2

  # Check if branch exists
  exists=$(curl -s -H "PRIVATE-TOKEN: $GL_TOKEN" \
    "$GL_URL/projects/$project_id/repository/branches/$branch" | jq -r '.name // empty')

  if [ -n "$exists" ]; then
    # Remove existing protection (ignore errors)
    curl -s --request DELETE \
      -H "PRIVATE-TOKEN: $GL_TOKEN" \
      "$GL_URL/projects/$project_id/protected_branches/$branch" > /dev/null 2>&1

    # Add protection with correct settings
    curl -s --request POST \
      -H "PRIVATE-TOKEN: $GL_TOKEN" \
      "$GL_URL/projects/$project_id/protected_branches" \
      --data "name=$branch" \
      --data "push_access_level=40" \
      --data "merge_access_level=40" \
      --data "allow_force_push=false" > /dev/null

    echo "    Protected: $branch"
  fi
}

echo "=== INF-004 Compliance Check ==="
echo ""

for group in $GROUPS; do
  echo "Group: $group"

  # Get all projects in group (including subgroups)
  PROJECTS=$(curl -s -H "PRIVATE-TOKEN: $GL_TOKEN" \
    "$GL_URL/groups/$group/projects?include_subgroups=true&per_page=100" | jq -r '.[] | "\(.id):\(.name)"')

  for proj in $PROJECTS; do
    id=$(echo $proj | cut -d: -f1)
    name=$(echo $proj | cut -d: -f2-)

    echo "  [$id] $name"

    # Update merge request settings
    curl -s --request PUT \
      -H "PRIVATE-TOKEN: $GL_TOKEN" \
      "$GL_URL/projects/$id" \
      --data "merge_method=ff" \
      --data "only_allow_merge_if_pipeline_succeeds=true" \
      --data "only_allow_merge_if_all_discussions_are_resolved=true" > /dev/null

    echo "    MR settings: ff, pipeline_required, discussions_required"

    # Protect required branches
    for branch in $PROTECTED_BRANCHES; do
      protect_branch "$id" "$branch"
    done
  done
  echo ""
done

echo "=== Done ==="
```

### Quick Single-Project Fix

```bash
# Fix a single project by ID
PROJECT_ID=16
GL_TOKEN="your-token"
GL_URL="https://gitlab.team-mate.pl/api/v4"

# MR settings
curl -s --request PUT -H "PRIVATE-TOKEN: $GL_TOKEN" \
  "$GL_URL/projects/$PROJECT_ID" \
  --data "merge_method=ff" \
  --data "only_allow_merge_if_pipeline_succeeds=true" \
  --data "only_allow_merge_if_all_discussions_are_resolved=true"

# Protect main/master/stage (run for each existing branch)
for branch in main master stage; do
  curl -s --request DELETE -H "PRIVATE-TOKEN: $GL_TOKEN" \
    "$GL_URL/projects/$PROJECT_ID/protected_branches/$branch" 2>/dev/null
  curl -s --request POST -H "PRIVATE-TOKEN: $GL_TOKEN" \
    "$GL_URL/projects/$PROJECT_ID/protected_branches" \
    --data "name=$branch" \
    --data "push_access_level=40" \
    --data "merge_access_level=40" \
    --data "allow_force_push=false"
done
```

## Related Standards

- [INF-003: CI/CD Pipeline Docker GitLab](./INF-003-cicd-pipeline-docker-gitlab.md) - Pipeline configuration
- [INF-001: Local Development Makefile](./INF-001-infrastructure-local-makefile.md) - Local `make check` parity
