<?php

return <<<'MARKDOWN'

## Laravel Sail Development Environment

### About Sail
- Official Docker-based development environment for Laravel
- Provides PHP, MySQL/PostgreSQL, Redis, Mailpit, and more
- Works on macOS, Linux, and Windows

### Services
- PHP (multiple versions available)
- MySQL, PostgreSQL, or MariaDB
- Redis
- Mailpit (email testing)
- Selenium (browser testing)
- Meilisearch (search)

### Usage

Start Sail:
```bash
./vendor/bin/sail up
./vendor/bin/sail up -d  # Background
```

Stop Sail:
```bash
./vendor/bin/sail down
```

### Artisan Commands

```bash
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan queue:work
```

### Composer & NPM

```bash
./vendor/bin/sail composer install
./vendor/bin/sail npm install
./vendor/bin/sail npm run dev
```

### Database

```bash
./vendor/bin/sail mysql
./vendor/bin/sail psql
```

### Testing

```bash
./vendor/bin/sail test
./vendor/bin/sail test --filter UserTest
```

### Shell Access

```bash
./vendor/bin/sail shell
./vendor/bin/sail root-shell
```

### Configuration

`.env` for Sail:
```env
APP_URL=http://localhost
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=sail
DB_PASSWORD=password

REDIS_HOST=redis
```

### Alias

Create alias for convenience:
```bash
alias sail='./vendor/bin/sail'
```

Then use:
```bash
sail up
sail artisan migrate
sail test
```

### Ports
- Application: `http://localhost`
- MySQL: `localhost:3306`
- PostgreSQL: `localhost:5432`
- Redis: `localhost:6379`
- Mailpit: `http://localhost:8025`

### Performance
- Slower than native (Herd) due to Docker overhead
- Use volume optimization for better performance
- Compatible with all operating systems

MARKDOWN;
