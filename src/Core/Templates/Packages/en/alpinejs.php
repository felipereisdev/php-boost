<?php

return <<<'MARKDOWN'

### Alpine.js

**Purpose:** Lightweight JavaScript framework

**Installation:**
```bash
npm install alpinejs
```

**Usage:**

Basic component:
```html
<div x-data="{ open: false }">
    <button @click="open = !open">Toggle</button>
    <div x-show="open">Content</div>
</div>
```

With Laravel:
```html
<div x-data="{ count: 0 }">
    <button @click="count++">Increment</button>
    <span x-text="count"></span>
</div>
```

**Best Practices:**
- Keep components small and focused
- Use x-cloak to prevent flash of unstyled content
- Leverage directives properly (x-show vs x-if)
- Extract reusable logic to Alpine.data()
- Use $watch for reactive side effects
- Integrate with Laravel Livewire when needed
- Use x-bind for dynamic attributes

MARKDOWN;
