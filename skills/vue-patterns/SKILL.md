# Vue.js Patterns & Best Practices

You are a senior Vue.js developer. Write clean, performant, maintainable Vue code using modern patterns (Vue 3, Composition API, script setup).

## Component Patterns

### Script Setup (Preferred)

```vue
<script setup>
import { ref, computed, onMounted } from 'vue'

const props = defineProps({
  title: { type: String, required: true },
  items: { type: Array, default: () => [] },
})

const emit = defineEmits(['update', 'delete'])

const search = ref('')
const filteredItems = computed(() =>
  props.items.filter(item =>
    item.name.toLowerCase().includes(search.value.toLowerCase())
  )
)

function handleUpdate(item) {
  emit('update', item)
}
</script>

<template>
  <div>
    <h2>{{ title }}</h2>
    <input v-model="search" placeholder="Search..." />
    <ul>
      <li v-for="item in filteredItems" :key="item.id">
        {{ item.name }}
        <button @click="handleUpdate(item)">Edit</button>
      </li>
    </ul>
  </div>
</template>
```

### Composables (Reusable Logic)

Think of composables like React hooks — extract reusable stateful logic:

```javascript
// composables/useDebounce.js
import { ref, watch } from 'vue'

export function useDebounce(source, delay = 300) {
  const debounced = ref(source.value)

  let timer
  watch(source, (newValue) => {
    clearTimeout(timer)
    timer = setTimeout(() => {
      debounced.value = newValue
    }, delay)
  })

  return debounced
}

// composables/useFetch.js
import { ref, watchEffect } from 'vue'

export function useFetch(url) {
  const data = ref(null)
  const error = ref(null)
  const loading = ref(true)

  watchEffect(async () => {
    loading.value = true
    error.value = null
    try {
      const response = await fetch(url.value)
      data.value = await response.json()
    } catch (e) {
      error.value = e
    } finally {
      loading.value = false
    }
  })

  return { data, error, loading }
}
```

### Provide / Inject (Dependency Injection)

```javascript
// Parent component
import { provide, ref } from 'vue'

const theme = ref('light')
provide('theme', {
  theme,
  toggleTheme: () => {
    theme.value = theme.value === 'light' ? 'dark' : 'light'
  },
})

// Any descendant component
import { inject } from 'vue'
const { theme, toggleTheme } = inject('theme')
```

### Slots for Composition

```vue
<!-- BaseCard.vue -->
<template>
  <div class="card">
    <div v-if="$slots.header" class="card-header">
      <slot name="header" />
    </div>
    <div class="card-body">
      <slot />
    </div>
    <div v-if="$slots.footer" class="card-footer">
      <slot name="footer" />
    </div>
  </div>
</template>

<!-- Usage -->
<BaseCard>
  <template #header>
    <h3>Dashboard</h3>
  </template>
  <p>Main content here</p>
  <template #footer>
    <button>Save</button>
  </template>
</BaseCard>

<!-- Scoped slots for render delegation -->
<DataTable :items="users">
  <template #cell-name="{ item }">
    <strong>{{ item.name }}</strong>
  </template>
  <template #cell-status="{ item }">
    <Badge :variant="item.active ? 'success' : 'danger'">
      {{ item.active ? 'Active' : 'Inactive' }}
    </Badge>
  </template>
</DataTable>
```

## Reactivity

### Ref vs Reactive

```javascript
// ref: for primitives and values you reassign
const count = ref(0)
const user = ref(null) // will be reassigned from API

// reactive: for objects you mutate (never reassign)
const form = reactive({
  name: '',
  email: '',
  errors: {},
})

// computed: derived state
const fullName = computed(() => `${form.firstName} ${form.lastName}`)

// watch: side effects on state change
watch(
  () => form.email,
  (newEmail) => { validateEmail(newEmail) },
  { debounce: 300 }
)

// watchEffect: auto-tracks dependencies
watchEffect(() => {
  console.log(`User: ${user.value?.name}`)
})
```

### Avoiding Reactivity Pitfalls

```javascript
// DON'T: destructure reactive objects (loses reactivity)
const { name, email } = form // name, email are NOT reactive

// DO: use toRefs
const { name, email } = toRefs(form) // name.value, email.value are reactive

// DON'T: reassign reactive objects
form = { name: 'new' } // breaks reactivity

// DO: mutate properties
form.name = 'new'
Object.assign(form, { name: 'new', email: 'new@test.com' })
```

## Performance

### v-once, v-memo, shallowRef

```vue
<!-- Render once, never update -->
<footer v-once>{{ copyright }}</footer>

<!-- Memoize list rendering -->
<div v-for="item in list" :key="item.id" v-memo="[item.selected]">
  <!-- Only re-renders when item.selected changes -->
</div>
```

```javascript
// shallowRef for large objects where you replace, not mutate
const bigList = shallowRef([])
bigList.value = newArray // triggers update
bigList.value[0].name = 'x' // does NOT trigger update

// shallowReactive for top-level-only reactivity
const state = shallowReactive({ nested: { deep: 'value' } })
state.nested = { deep: 'new' } // triggers
state.nested.deep = 'new' // does NOT trigger
```

### Async Components

```javascript
import { defineAsyncComponent } from 'vue'

const HeavyChart = defineAsyncComponent({
  loader: () => import('./HeavyChart.vue'),
  loadingComponent: Spinner,
  delay: 200,
  errorComponent: ErrorDisplay,
  timeout: 10000,
})
```

### Virtual Scrolling

```vue
<script setup>
import { useVirtualList } from '@vueuse/core'

const { list, containerProps, wrapperProps } = useVirtualList(
  items,
  { itemHeight: 50 }
)
</script>

<template>
  <div v-bind="containerProps" style="height: 400px; overflow: auto">
    <div v-bind="wrapperProps">
      <div v-for="{ data, index } in list" :key="index" style="height: 50px">
        {{ data.name }}
      </div>
    </div>
  </div>
</template>
```

## Vue Router Patterns

```javascript
// Lazy-loaded routes
const routes = [
  {
    path: '/dashboard',
    component: () => import('./pages/Dashboard.vue'),
    meta: { requiresAuth: true },
  },
]

// Navigation guards
router.beforeEach((to) => {
  if (to.meta.requiresAuth && !isAuthenticated()) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }
})
```

## Pinia (State Management)

```javascript
import { defineStore } from 'pinia'

export const useCartStore = defineStore('cart', () => {
  const items = ref([])
  const total = computed(() =>
    items.value.reduce((sum, item) => sum + item.price * item.qty, 0)
  )

  function addItem(product) {
    const existing = items.value.find(i => i.id === product.id)
    if (existing) {
      existing.qty++
    } else {
      items.value.push({ ...product, qty: 1 })
    }
  }

  function removeItem(id) {
    items.value = items.value.filter(i => i.id !== id)
  }

  return { items, total, addItem, removeItem }
})
```

## Transition & Animation

```vue
<template>
  <Transition name="fade" mode="out-in">
    <component :is="currentView" />
  </Transition>

  <TransitionGroup name="list" tag="ul">
    <li v-for="item in items" :key="item.id">{{ item.name }}</li>
  </TransitionGroup>
</template>

<style>
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

.list-enter-active, .list-leave-active { transition: all 0.4s ease; }
.list-enter-from, .list-leave-to { opacity: 0; transform: translateX(30px); }
.list-move { transition: transform 0.4s ease; }
</style>
```

## Anti-Patterns to Flag

- Options API in new code (use Composition API with `<script setup>`).
- Mutating props (emit an event to parent instead).
- `v-if` + `v-for` on same element (`v-if` takes precedence in Vue 3, but separate them for clarity).
- Missing `:key` on `v-for` loops.
- Watchers for derived state (use `computed` instead).
- Direct DOM manipulation (`document.querySelector` — use template refs).
- Giant components (>250 lines) — extract composables and sub-components.
- `this.$forceUpdate()` — a sign of broken reactivity.
