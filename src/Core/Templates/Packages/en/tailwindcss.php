<?php

return <<<'MARKDOWN'

### Tailwind CSS

**Purpose:** Utility-first CSS framework

**Installation:**
```bash
npm install -D tailwindcss postcss autoprefixer
npx tailwindcss init -p
```

**Configuration (tailwind.config.js):**
```js
export default {
    content: [
        "./resources/**/*.blade.php",
        "./resources/**/*.js",
        "./resources/**/*.vue",
    ],
    theme: {
        extend: {},
    },
    plugins: [],
}
```

**Usage:**

In CSS:
```css
@tailwind base;
@tailwind components;
@tailwind utilities;
```

In HTML:
```html
<div class="flex items-center justify-center min-h-screen bg-gray-100">
    <div class="px-8 py-6 bg-white rounded-lg shadow-md">
        <h1 class="text-2xl font-bold text-gray-800">Hello Tailwind</h1>
    </div>
</div>
```

**Best Practices:**
- Configure content paths properly
- Extract components with @apply sparingly
- Use custom utilities in theme config
- Leverage plugins (@tailwindcss/forms, etc)
- Optimize for production
- Use JIT mode for development
- Configure responsive breakpoints
- Implement dark mode when needed

MARKDOWN;
