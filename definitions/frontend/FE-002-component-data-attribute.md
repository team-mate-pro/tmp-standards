# FE-002: Component data-component Attribute

**Documentation:** https://github.com/team-mate-pro/tmp-standards/blob/main/definitions/frontend/FE-002-component-data-attribute.md

## Check Method

| Method | Command |
|--------|---------|
| **ESLINT** | `eslint --rule 'local/require-data-component: error' 'components/**/*.vue'` |
| **AI** | `claude -p "$(cat vendor/team-mate-pro/tmp-standards/definitions/frontend/FE-002-component-data-attribute.prompt.txt)" --cwd .` |

## Definition

Every custom Vue component **must** include a `data-component` attribute on its root element. The attribute value must exactly match the component name (filename without `.vue` extension). This enables component identification in the DOM for testing, debugging, and analytics.

## Applies To

- All Vue single-file components (`*.vue`) in `components/` directory
- Components with the `App*` prefix (see FE-001)

## Required Format

```html
data-component="ComponentName"
```

The value must:
- Exactly match the component filename (minus `.vue`)
- Use PascalCase
- Be placed on the root/outermost element

## Correct Usage

### Simple Component

```vue
<!-- AppAlert.vue -->
<template>
  <v-alert
    data-component="AppAlert"
    :type="type"
    :message="message"
  >
    <slot />
  </v-alert>
</template>
```

### Component with Wrapper Div

```vue
<!-- AppUserAvatar.vue -->
<template>
  <div data-component="AppUserAvatar" class="avatar-wrapper">
    <v-avatar :image="avatarUrl" :size="size" />
    <span v-if="showName">{{ user.name }}</span>
  </div>
</template>
```

### Conditional Root Element

```vue
<!-- AppChartCard.vue -->
<template>
  <v-skeleton-loader
    v-if="loading"
    data-component="AppChartCard"
    type="article"
  />
  <v-sheet
    v-else
    data-component="AppChartCard"
    class="pa-5"
  >
    <slot />
  </v-sheet>
</template>
```

Note: When using `v-if`/`v-else` at root level, both branches must have the `data-component` attribute.

### Component with Single Root

```vue
<!-- AppLogo.vue -->
<template>
  <img
    data-component="AppLogo"
    alt="Logo"
    src="/logo.png"
  >
</template>
```

## Violation

```vue
<!-- WRONG: Missing data-component attribute -->
<template>
  <v-alert :type="type">
    {{ message }}
  </v-alert>
</template>
```

```vue
<!-- WRONG: Incorrect attribute value (doesn't match filename) -->
<!-- File: AppUserAvatar.vue -->
<template>
  <div data-component="Avatar">  <!-- Should be: AppUserAvatar -->
    <v-avatar :image="url" />
  </div>
</template>
```

```vue
<!-- WRONG: Attribute on nested element instead of root -->
<template>
  <div class="wrapper">
    <span data-component="AppAlert">  <!-- Should be on outer div -->
      {{ message }}
    </span>
  </div>
</template>
```

```vue
<!-- WRONG: Using data-testid instead of data-component -->
<template>
  <div data-testid="AppAlert">  <!-- Should be: data-component -->
    {{ message }}
  </div>
</template>
```

## DOM Output

With this convention, the rendered DOM clearly shows component boundaries:

```html
<div data-component="AppDashboard">
  <div data-component="AppStatSheet">
    <span>Active Users: 150</span>
  </div>
  <div data-component="AppChartCard">
    <canvas></canvas>
  </div>
</div>
```

## Use Cases

### E2E Testing (Playwright/Cypress)

```typescript
// Easy component selection in tests
await page.locator('[data-component="AppLoginForm"]').fill('username', 'test');
await page.locator('[data-component="AppAlert"]').toBeVisible();
```

### Browser DevTools

Quickly identify component boundaries when inspecting DOM:
```
Elements > Search: [data-component]
```

### Analytics & Monitoring

Track component visibility and interactions:
```javascript
document.querySelectorAll('[data-component]').forEach(el => {
  observer.observe(el);
});
```

## ESLint Implementation

Add a custom ESLint rule to enforce this standard automatically using `vue-eslint-parser`.

### Custom Rule Definition

```javascript
// eslint-plugins/require-data-component.mjs
const requireDataComponentPlugin = {
  rules: {
    'require-data-component': {
      meta: {
        type: 'problem',
        docs: {
          description: 'Require data-component attribute on root element matching filename',
          category: 'Best Practices',
          url: 'https://github.com/team-mate-pro/tmp-standards/blob/main/definitions/frontend/FE-002-component-data-attribute.md',
        },
        messages: {
          missingAttribute: 'Root element must have data-component attribute (FE-002)',
          wrongValue: 'data-component value "{{actual}}" must match filename "{{expected}}" (FE-002)',
          notOnRoot: 'data-component attribute must be on root element, not nested (FE-002)',
        },
      },
      create(context) {
        const filename = context.getFilename()

        // Only check files in components/ directory
        if (!filename.includes('/components/')) {
          return {}
        }

        // Extract expected component name from filename
        const fileNameWithExt = filename.split('/').pop() || ''
        const expectedName = fileNameWithExt.replace('.vue', '')

        return {
          // Vue template root element visitor
          'VElement[parent.type="VDocumentFragment"]'(node) {
            // Skip template, script, style tags
            if (['template', 'script', 'style'].includes(node.name)) {
              // Check the actual root inside <template>
              if (node.name === 'template' && node.children) {
                const rootElements = node.children.filter(
                  child => child.type === 'VElement'
                )

                rootElements.forEach(rootEl => {
                  checkDataComponent(context, rootEl, expectedName)
                })
              }
              return
            }

            checkDataComponent(context, node, expectedName)
          },
        }
      },
    },
  },
}

function checkDataComponent(context, node, expectedName) {
  // Find data-component attribute
  const dataComponentAttr = node.startTag?.attributes?.find(
    attr => attr.key?.name === 'data-component' ||
            (attr.key?.argument?.name === 'component' && attr.key?.name?.name === 'bind')
  )

  if (!dataComponentAttr) {
    context.report({
      node: node.startTag || node,
      messageId: 'missingAttribute',
    })
    return
  }

  // Get attribute value
  let actualValue = null
  if (dataComponentAttr.value?.type === 'VLiteral') {
    actualValue = dataComponentAttr.value.value
  } else if (dataComponentAttr.value?.expression?.type === 'Literal') {
    actualValue = dataComponentAttr.value.expression.value
  }

  // Check if value matches filename
  if (actualValue && actualValue !== expectedName) {
    context.report({
      node: dataComponentAttr,
      messageId: 'wrongValue',
      data: {
        actual: actualValue,
        expected: expectedName,
      },
    })
  }
}

export default requireDataComponentPlugin
```

### ESLint Configuration (Nuxt)

```javascript
// eslint.config.mjs
import withNuxt from './.nuxt/eslint.config.mjs'
import requireAppPrefixPlugin from './eslint-plugins/require-app-prefix.mjs'
import requireDataComponentPlugin from './eslint-plugins/require-data-component.mjs'

export default withNuxt(
  {
    files: ['components/**/*.vue'],
    plugins: {
      local: {
        rules: {
          ...requireAppPrefixPlugin.rules,
          ...requireDataComponentPlugin.rules,
        },
      },
    },
    rules: {
      'local/require-app-prefix': 'error',
      'local/require-data-component': 'error',
    },
  },
)
```

### ESLint Configuration (Vue CLI / Vite)

```javascript
// eslint.config.mjs
import vueParser from 'vue-eslint-parser'
import requireDataComponentPlugin from './eslint-plugins/require-data-component.mjs'

export default [
  {
    files: ['src/components/**/*.vue'],
    languageOptions: {
      parser: vueParser,
    },
    plugins: {
      local: requireDataComponentPlugin,
    },
    rules: {
      'local/require-data-component': 'error',
    },
  },
]
```

### Example Output

```
src/components/AppAlert.vue
  3:3  error  Root element must have data-component attribute (FE-002)  local/require-data-component

src/components/AppUserAvatar.vue
  3:3  error  data-component value "Avatar" must match filename "AppUserAvatar" (FE-002)  local/require-data-component

✖ 2 problems (2 errors, 0 warnings)
```

## Rationale

1. **Testability**: Provides stable selectors for E2E tests that don't break when CSS classes change.

2. **Debugging**: Instantly identify which component renders which DOM element in browser DevTools.

3. **Component Boundaries**: Clear visual separation of component hierarchies in the DOM tree.

4. **Framework Agnostic**: Works regardless of Vue version, build tool, or CSS framework.

5. **Analytics**: Enable component-level tracking without modifying business logic.

6. **Consistency**: Standard approach across all components ensures predictable DOM structure.
