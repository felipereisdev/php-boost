<?php

return <<<'MARKDOWN'

### Vue.js

**Purpose:** Progressive JavaScript framework

**Installation (with Vite):**
```bash
npm install vue@next
npm install @vitejs/plugin-vue
```

**Configuration (vite.config.js):**
```js
import vue from '@vitejs/plugin-vue'

export default {
    plugins: [vue()],
}
```

**Usage:**

Component:
```vue
<template>
    <div>
        <h1>{{ title }}</h1>
        <button @click="increment">Count: {{ count }}</button>
    </div>
</template>

<script setup>
import { ref } from 'vue'

const title = ref('Hello Vue')
const count = ref(0)

function increment() {
    count.value++
}
</script>
```

**Best Practices:**
- Use Composition API for better reusability
- Implement proper component structure
- Use computed for derived state
- Leverage watchers appropriately
- Implement proper error boundaries
- Use Teleport for modals/overlays
- Optimize with v-memo when needed
- Use Pinia for state management

MARKDOWN;
