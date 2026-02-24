# FE-001: Custom Component App* Prefix Convention

**Documentation:** https://github.com/team-mate-pro/tmp-standards/blob/main/definitions/frontend/FE-001-component-app-prefix.md

## Check Method

| Method | Command |
|--------|---------|
| **ESLINT** | `eslint --rule 'local/require-app-prefix: error' 'components/**/*.vue'` |
| **AI** | `claude -p "$(cat vendor/team-mate-pro/tmp-standards/definitions/frontend/FE-001-component-app-prefix.prompt.txt)" --cwd .` |

## Definition

All custom Vue/Nuxt components **must** be prefixed with `App` to clearly distinguish application-specific components from third-party or framework components (e.g., Vuetify's `v-*`, Nuxt's `Nuxt*`).

## Applies To

- All Vue single-file components (`*.vue`) in `components/` directory
- Custom reusable UI components
- Feature-specific components in subdirectories

## Naming Convention

| Type | Prefix | Example |
|------|--------|---------|
| Custom components | `App` | `AppAlert.vue`, `AppUserAvatar.vue` |
| Framework components | None | `v-btn`, `v-card`, `NuxtLink` |

## Correct Usage

### Simple Component

```
components/
├── AppAlert.vue
├── AppLogo.vue
├── AppUserAvatar.vue
└── AppPersonSelect.vue
```

### Feature-Organized Components

```
components/
├── chat/
│   ├── AppChatMessage.vue
│   ├── AppChatMessageActions.vue
│   └── AppChatMessageReaction.vue
├── dashboard/
│   ├── AppChartCard.vue
│   └── AppStatSheet.vue
└── player/
    └── AppPlayerCard.vue
```

### Component Template

```vue
<template>
  <div data-component="AppUserAvatar">
    <!-- component content -->
  </div>
</template>

<script setup lang="ts">
// component logic
</script>
```

### Usage in Parent Component

```vue
<template>
  <div>
    <AppAlert type="success" message="Saved!" />
    <AppUserAvatar :user="currentUser" />
  </div>
</template>
```

## Violation

```
# WRONG: Missing App prefix
components/
├── Alert.vue           # Should be: AppAlert.vue
├── UserAvatar.vue      # Should be: AppUserAvatar.vue
├── Logo.vue            # Should be: AppLogo.vue
└── chat/
    └── Message.vue     # Should be: AppChatMessage.vue
```

```vue
<!-- WRONG: Generic naming without prefix -->
<template>
  <Alert type="error" />
  <UserAvatar :user="user" />
</template>

<!-- CORRECT: App-prefixed components -->
<template>
  <AppAlert type="error" />
  <AppUserAvatar :user="user" />
</template>
```

## Exceptions

The following do NOT require the `App` prefix:

1. **Layout components**: `default.vue`, `blank.vue` in `layouts/` directory
2. **Page components**: Files in `pages/` directory (route-based naming)
3. **Third-party wrappers**: When explicitly wrapping a library component with minimal changes

## ESLint Implementation

Add a custom ESLint rule to enforce this standard automatically.

### Custom Rule Definition

```javascript
// eslint-plugins/require-app-prefix.mjs
const requireAppPrefixPlugin = {
  rules: {
    'require-app-prefix': {
      meta: {
        type: 'problem',
        docs: {
          description: 'Require component filenames to start with "App" prefix',
          category: 'Best Practices',
          url: 'https://github.com/team-mate-pro/tmp-standards/blob/main/definitions/frontend/FE-001-component-app-prefix.md',
        },
        messages: {
          missingPrefix: 'Component filename "{{filename}}" must start with "App" prefix (FE-001)',
        },
      },
      create(context) {
        return {
          Program(node) {
            const filename = context.getFilename()

            // Only check files in components/ directory
            if (!filename.includes('/components/')) {
              return
            }

            // Extract filename without path and extension
            const fileNameWithExt = filename.split('/').pop() || ''
            const fileName = fileNameWithExt.replace('.vue', '')

            // Check for App prefix
            if (!fileName.startsWith('App')) {
              context.report({
                node,
                messageId: 'missingPrefix',
                data: {
                  filename: fileNameWithExt,
                },
              })
            }
          },
        }
      },
    },
  },
}

export default requireAppPrefixPlugin
```

### ESLint Configuration (Nuxt)

```javascript
// eslint.config.mjs
import withNuxt from './.nuxt/eslint.config.mjs'
import requireAppPrefixPlugin from './eslint-plugins/require-app-prefix.mjs'

export default withNuxt(
  {
    files: ['components/**/*.vue'],
    plugins: {
      local: requireAppPrefixPlugin,
    },
    rules: {
      'local/require-app-prefix': 'error',
    },
  },
)
```

### ESLint Configuration (Vue CLI / Vite)

```javascript
// eslint.config.mjs
import requireAppPrefixPlugin from './eslint-plugins/require-app-prefix.mjs'

export default [
  {
    files: ['src/components/**/*.vue'],
    plugins: {
      local: requireAppPrefixPlugin,
    },
    rules: {
      'local/require-app-prefix': 'error',
    },
  },
]
```

### Example Output

```
src/components/Alert.vue
  1:1  error  Component filename "Alert.vue" must start with "App" prefix (FE-001)  local/require-app-prefix

src/components/chat/Message.vue
  1:1  error  Component filename "Message.vue" must start with "App" prefix (FE-001)  local/require-app-prefix

✖ 2 problems (2 errors, 0 warnings)
```

## Rationale

1. **Instant Recognition**: Developers immediately know if a component is custom (`App*`) or from a library (`v-*`, `Nuxt*`).

2. **Autocomplete Efficiency**: Typing `App` in IDE shows all custom components, making discovery faster.

3. **Conflict Prevention**: Avoids naming collisions with current or future framework/library components.

4. **Codebase Navigation**: Easy to search for all custom components with `App` prefix.

5. **Onboarding**: New developers quickly understand the component ownership and architecture.

6. **Consistency**: Uniform naming across the entire codebase improves maintainability.
