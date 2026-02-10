<?php

return <<<'MARKDOWN'

### Laravel Livewire

**Purpose:** Build dynamic interfaces without leaving PHP

**Installation:**
```bash
composer require livewire/livewire
```

**Create Component:**
```bash
php artisan make:livewire Counter
```

**Component Class:**
```php
namespace App\Http\Livewire;

use Livewire\Component;

class Counter extends Component
{
    public $count = 0;

    public function increment()
    {
        $this->count++;
    }

    public function render()
    {
        return view('livewire.counter');
    }
}
```

**Component View:**
```blade
<div>
    <h1>{{ $count }}</h1>
    <button wire:click="increment">+</button>
</div>
```

**Usage in Blade:**
```blade
<livewire:counter />
```

**Best Practices:**
- Keep components small and focused
- Use computed properties for expensive operations
- Emit events for component communication
- Use wire:model.defer for better performance
- Validate input with validation rules
- Use loading states for user feedback

**Features:**
- Real-time validation
- File uploads
- Polling
- Lazy loading
- Pagination
- Events

MARKDOWN;
