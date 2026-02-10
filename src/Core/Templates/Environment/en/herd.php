<?php

return <<<'MARKDOWN'

## Laravel Herd Development Environment

### About Herd
- Native Laravel development environment for macOS
- No Docker required - runs natively on macOS
- Automatic PHP, Nginx, and database management
- Automatic SSL certificates
- Fast and lightweight

### Features
- Multiple PHP versions (switch via Herd UI)
- Automatic .test domain routing
- SSL certificates for local HTTPS
- Database management (MySQL, PostgreSQL)
- Redis support
- Mailpit for email testing

### Usage
- Park a directory: Sites are automatically served at `projectname.test`
- Switch PHP versions via Herd UI
- Access database via Herd UI or command line

### Configuration
- `.env` configuration for Herd:

```env
APP_URL=http://projectname.test
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=root
DB_PASSWORD=
```

### CLI
- `herd park` - Park current directory
- `herd unpark` - Unpark directory
- `herd php` - Use Herd's PHP binary

### Database
- MySQL and PostgreSQL included
- Access via `127.0.0.1:3306` (MySQL) or `127.0.0.1:5432` (PostgreSQL)
- Default user: `root` (no password)

### Email Testing
- Mailpit included for email testing
- Access at `http://localhost:8025`
- Captures all outgoing emails

### Performance
- Faster than Docker-based solutions
- Native macOS performance
- Low resource usage

MARKDOWN;
