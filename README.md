<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://avatars0.githubusercontent.com/u/993323" height="100px">
    </a>
    <h1 align="center">Yii 2 Advanced Project Template</h1>
    <br>
</p>

Yii 2 Advanced Project Template is a skeleton [Yii 2](https://www.yiiframework.com/) application best for
developing complex Web applications with multiple tiers.

The template includes three tiers: front end, back end, and console, each of which
is a separate Yii application.

The template is designed to work in a team development environment. It supports
deploying the application in different environments.

Documentation is at [docs/guide/README.md](docs/guide/README.md).

[![Latest Stable Version](https://img.shields.io/packagist/v/yiisoft/yii2-app-advanced.svg)](https://packagist.org/packages/yiisoft/yii2-app-advanced)
[![Total Downloads](https://img.shields.io/packagist/dt/yiisoft/yii2-app-advanced.svg)](https://packagist.org/packages/yiisoft/yii2-app-advanced)
[![build](https://github.com/yiisoft/yii2-app-advanced/workflows/build/badge.svg)](https://github.com/yiisoft/yii2-app-advanced/actions?query=workflow%3Abuild)

RUN WITHOUT DOCKER (LINUX)
--------------------------

### 1. Prerequisites

- PHP >= 7.4 with common extensions (`mbstring`, `openssl`, `pdo`, `pdo_mysql`, `intl`, `xml`, `zip`, `gd`)
- Composer
- MySQL or MariaDB

### 2. Install dependencies

```bash
cd /data/www/pelaporan_keselamatan
composer install
```

### 3. Initialize Yii environment

```bash
php init --env=Development --overwrite=all
```

### 4. Create database and configure connection

Create database (example):

```sql
CREATE DATABASE pelaporan_keselamatan CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Then adjust database credentials in [common/config/main-local.php](common/config/main-local.php).
Example:

```php
'db' => [
    'class' => \yii\db\Connection::class,
    'dsn' => 'mysql:host=127.0.0.1;dbname=pelaporan_keselamatan',
    'username' => 'root',
    'password' => 'root',
    'charset' => 'utf8',
],
```

### 5. Run migrations

```bash
php yii migrate --interactive=0
```

### 6. Start development servers

Run frontend:

```bash
php -S 127.0.0.1:20080 -t frontend/web
```

In another terminal, run backend:

```bash
php -S 127.0.0.1:21080 -t backend/web
```

Open in browser:

- Frontend: http://127.0.0.1:20080
- Backend: http://127.0.0.1:21080

### Notes

- `php yii serve` may fail on advanced template setups because frontend/backend are separate web roots.
- If permission issues occur, ensure runtime/assets folders are writable:

```bash
chmod -R 775 backend/runtime backend/web/assets frontend/runtime frontend/web/assets console/runtime
```

DIRECTORY STRUCTURE
-------------------

```
common
    config/              contains shared configurations
    mail/                contains view files for e-mails
    models/              contains model classes used in both backend and frontend
    tests/               contains tests for common classes    
console
    config/              contains console configurations
    controllers/         contains console controllers (commands)
    migrations/          contains database migrations
    models/              contains console-specific model classes
    runtime/             contains files generated during runtime
backend
    assets/              contains application assets such as JavaScript and CSS
    config/              contains backend configurations
    controllers/         contains Web controller classes
    models/              contains backend-specific model classes
    runtime/             contains files generated during runtime
    tests/               contains tests for backend application    
    views/               contains view files for the Web application
    web/                 contains the entry script and Web resources
frontend
    assets/              contains application assets such as JavaScript and CSS
    config/              contains frontend configurations
    controllers/         contains Web controller classes
    models/              contains frontend-specific model classes
    runtime/             contains files generated during runtime
    tests/               contains tests for frontend application
    views/               contains view files for the Web application
    web/                 contains the entry script and Web resources
    widgets/             contains frontend widgets
vendor/                  contains dependent 3rd-party packages
environments/            contains environment-based overrides
```
# siperisai
