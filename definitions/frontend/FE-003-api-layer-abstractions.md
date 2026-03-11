# FE-003: API Layer Abstractions and Type Safety

**Documentation:** https://github.com/team-mate-pro/tmp-standards/blob/main/definitions/frontend/FE-003-api-layer-abstractions.md

## Check Method

| Method | Command |
|--------|---------|
| **Automated** | ESLint rule `local/no-direct-api-calls` |
| **Manual** | Code review |

## Definition

All HTTP calls via `$api` **must live in strongly-typed wrapper functions inside the `/api/` directory**. Components, composables, pages and stores **must never call `$api` directly** — they must import and invoke the typed wrappers instead.

Each API wrapper function must define the **response type as a TypeScript interface** that matches the backend contract. This interface lives alongside the wrapper function in the same `/api/` file.

## Rules

### 1. HTTP calls belong only in `/api/`

Every call to `$api.get()`, `$api.post()`, `$api.put()`, `$api.patch()`, `$api.delete()` must reside in a dedicated file under the `/api/` directory.

```
# Correct structure
api/
  shops/
    get.ts          ← export async function getShop()
    patch.ts        ← export async function patchShop()
    products/
      get.ts        ← export async function fetchShopProducts()
      put.ts        ← export async function updateShopProduct()
      delete.ts     ← export async function deleteShopProduct()
    logos/
      post.ts       ← export async function uploadShopLogo()
```

### 2. Define response interfaces in the same `/api/` file

Every API file must export TypeScript interfaces describing the backend response shape. This provides a single source of truth for the data contract.

```typescript
// api/shops/products/get.ts

export interface ShopProduct {
  code: string
  name: string
  enabled: boolean
  price: number
}

export async function fetchShopProducts(): Promise<ShopProduct[]> {
  const { $api } = useNuxtApp()
  const response = await $api.get('/api/shops/products')
  return response.data
}
```

### 3. Consumers import from `/api/`, never call `$api` directly

```typescript
// ❌ WRONG — direct $api call in a component/store/composable
const { $api } = useNuxtApp()
const response = await $api.get('/api/shops/products')

// ✅ CORRECT — import the typed wrapper
import { fetchShopProducts } from '~/api/shops/products/get'
const products = await fetchShopProducts()
```

### 4. Request body types must also be defined

When a function accepts a request body, define an interface for it:

```typescript
// api/shops/patch.ts

export interface PatchShopBody {
  enabled: boolean
  subdomain?: string
  shop_name?: string | null
  primary_color?: string | null
}

export async function patchShop(body: PatchShopBody): Promise<OrganizationShop> {
  const { $api } = useNuxtApp()
  const response = await $api.patch('/api/shops', body)
  return response.data.item ?? response.data
}
```

### 5. Allowed directories (exempt from the rule)

The following top-level directories are allowed to contain direct `$api` calls:

- `api/` — the designated API layer
- `controller/` — thin controller layer that orchestrates API calls

All other directories (`components/`, `composables/`, `pages/`, `stores/`, `utils/`, etc.) must use the typed wrappers.

## Enforcing with ESLint

Add the custom ESLint rule to your project:

```javascript
// eslint-plugins/no-direct-api-calls.mjs

const HTTP_METHODS = new Set(['get', 'post', 'put', 'patch', 'delete'])

/**
 * Directories exempt from the rule.
 * Add paths here when you need to allow direct $api calls in specific directories.
 */
const ALLOWED_DIRS = new Set([
  'api',        // the API layer itself
  'controller', // thin controller layer
])

const NUXT_TOP_LEVEL_DIRS = new Set([
  'api', 'components', 'composables', 'controller',
  'layouts', 'middleware', 'pages', 'plugins',
  'server', 'stores', 'tests', 'utils',
])

function resolveTopDir(absolutePath) {
  const parts = absolutePath.replace(/\\/g, '/').split('/')
  for (const part of parts) {
    if (NUXT_TOP_LEVEL_DIRS.has(part)) return part
  }
  return null
}

function isApiIdentifier(node) {
  return node.type === 'Identifier' && node.name === '$api'
}

function isNuxtAppApiMember(node) {
  return (
    node.type === 'MemberExpression'
    && node.property?.type === 'Identifier'
    && node.property.name === '$api'
    && node.object?.type === 'CallExpression'
    && node.object.callee?.type === 'Identifier'
    && node.object.callee.name === 'useNuxtApp'
  )
}

function extractStaticPath(arg) {
  if (!arg) return null
  if (arg.type === 'Literal' && typeof arg.value === 'string') return arg.value
  if (arg.type === 'TemplateLiteral' && arg.quasis.length > 0) {
    const head = arg.quasis[0]
    return head.value.cooked ?? head.value.raw
  }
  return null
}

const noDirectApiCallsPlugin = {
  rules: {
    'no-direct-api-calls': {
      meta: {
        type: 'problem',
        docs: {
          description:
            'Disallow direct $api HTTP calls outside the /api/ layer (FE-003). '
            + 'Move the call to a strongly-typed function in /api/ and import it.',
          category: 'Architecture',
        },
        // Schema allows configuring additional allowed directories:
        // { "allowedDirs": ["my-custom-layer"] }
        schema: [
          {
            type: 'object',
            properties: {
              allowedDirs: {
                type: 'array',
                items: { type: 'string' },
              },
            },
            additionalProperties: false,
          },
        ],
        messages: {
          directApiCall:
            '"$api.{{method}}(\'{{path}}\'...)" must not be called here (FE-003). '
            + 'Create a typed wrapper in /api/{{suggestion}} and import it instead.',
        },
      },

      create(context) {
        const filename = context.filename ?? context.getFilename()
        const topDir = resolveTopDir(filename)

        // Merge built-in allowed dirs with any configured extra dirs
        const extraAllowed = context.options[0]?.allowedDirs ?? []
        const effectiveAllowedDirs = new Set([...ALLOWED_DIRS, ...extraAllowed])

        if (effectiveAllowedDirs.has(topDir)) return {}

        return {
          CallExpression(node) {
            if (node.callee.type !== 'MemberExpression') return

            const callee = node.callee
            const methodName = callee.property?.name

            if (!HTTP_METHODS.has(methodName)) return

            const obj = callee.object
            if (!isApiIdentifier(obj) && !isNuxtAppApiMember(obj)) return

            const path = extractStaticPath(node.arguments[0])
            if (!path?.startsWith('/api/')) return

            const apiRelative = path.replace(/^\/api\//, '').replace(/\/$/, '')
            const suggestion = apiRelative
              ? `${apiRelative}/${methodName}.ts`
              : `<resource>/${methodName}.ts`

            context.report({
              node,
              messageId: 'directApiCall',
              data: { method: methodName, path, suggestion },
            })
          },
        }
      },
    },
  },
}

export default noDirectApiCallsPlugin
```

### Registering the plugin in `eslint.config.mjs`

```javascript
import noDirectApiCalls from './eslint-plugins/no-direct-api-calls.mjs'

export default [
  // ... other config
  {
    plugins: {
      local: noDirectApiCalls,
    },
    rules: {
      'local/no-direct-api-calls': 'error',

      // With custom allowed dirs (optional):
      // 'local/no-direct-api-calls': ['error', { allowedDirs: ['my-layer'] }],
    },
  },
]
```

## File Structure Example

```
api/
├── shops/
│   ├── get.ts                      # getShop(): Promise<ShopResponse>
│   ├── patch.ts                    # patchShop(body): Promise<OrganizationShop>
│   ├── put.ts                      # putShop(body): Promise<OrganizationShop>
│   ├── logos/
│   │   └── post.ts                 # uploadShopLogo(file): Promise<ShopLogoUploadResult>
│   ├── orders/
│   │   └── get.ts                  # fetchShopOrders(), fetchShopOrder(token)
│   └── products/
│       ├── get.ts                  # fetchShopProducts()
│       ├── put.ts                  # updateShopProduct(code, body)
│       └── delete.ts               # deleteShopProduct(code)
└── dashboards/
    └── finances/
        └── budget-forecast/
            └── get.ts              # fetchBudgetForecast(months), BudgetForecastData

stores/
├── shop.store.ts                   # imports from api/shops/*
└── context/finances/
    └── budgetForecast.store.ts     # imports from api/dashboards/finances/budget-forecast/get

composables/
└── useSyliusApi.ts                 # imports from api/shops/*

pages/
└── o/[organizationId]/shop/
    ├── settings.vue                # imports from api/shops/*
    └── products/
        └── index.vue               # imports from api/shops/products/*
```

## Rationale

- **Type safety** — response shapes are defined once and shared across the codebase
- **Single source of truth** — if the backend API changes, only the `/api/` file needs updating
- **Testability** — components can be unit tested by mocking `/api/` functions
- **Discoverability** — all API calls are in one place, making it easy to audit HTTP usage
- **Architectural clarity** — clear separation between data access and presentation logic
