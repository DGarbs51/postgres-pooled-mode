# Postgres Pooled Mode for Laravel

[![Tests](https://github.com/DGarbs51/postgres-pooled-mode/actions/workflows/tests.yml/badge.svg)](https://github.com/DGarbs51/postgres-pooled-mode/actions/workflows/tests.yml)
[![PHP Version](https://img.shields.io/packagist/php-v/dgarbs51/postgres-pooled-mode.svg?style=flat-square)](https://packagist.org/packages/dgarbs51/postgres-pooled-mode)
[![License](https://img.shields.io/packagist/l/dgarbs51/postgres-pooled-mode.svg?style=flat-square)](https://packagist.org/packages/dgarbs51/postgres-pooled-mode)

Drop-in Laravel support for PostgreSQL transaction poolers such as PgBouncer,
Neon pooled connections, PlanetScale pooled connections, and AWS RDS Proxy.

The package keeps normal application traffic on the pooled endpoint while giving
schema and maintenance workflows a direct PostgreSQL endpoint for operations
that do not work reliably through transaction pooling.

## Installation

```bash
composer require dgarbs51/postgres-pooled-mode
```

The service provider is auto-discovered by Laravel.

You may publish the package config:

```bash
php artisan vendor:publish --tag=postgres-pooled-mode-config
```

## Configuration

Add `pooled` and `direct` keys to your PostgreSQL connection:

```php
'pgsql' => [
    'driver' => 'pgsql',
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', '5432'),
    'database' => env('DB_DATABASE', 'laravel'),
    'username' => env('DB_USERNAME', 'root'),
    'password' => env('DB_PASSWORD', ''),
    'charset' => env('DB_CHARSET', 'utf8'),
    'prefix' => '',
    'prefix_indexes' => true,
    'search_path' => 'public',
    'sslmode' => env('DB_SSLMODE', 'prefer'),

    'pooled' => env('DB_POOLED', false),
    'direct' => array_filter([
        'host' => env('DB_DIRECT_HOST'),
        'port' => env('DB_DIRECT_PORT'),
        'username' => env('DB_DIRECT_USERNAME'),
        'password' => env('DB_DIRECT_PASSWORD'),
        'sslmode' => env('DB_DIRECT_SSLMODE'),
    ]),
],
```

Then set the pooler and direct endpoints:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=your-pooler-host
DB_PORT=6432
DB_POOLED=true

DB_DIRECT_HOST=your-direct-host
DB_DIRECT_PORT=5432
DB_DIRECT_USERNAME=your-direct-username
DB_DIRECT_PASSWORD=your-direct-password
DB_DIRECT_SSLMODE=require
```

If your pooler uses the same host but differentiates pooled and direct
connections by port and username, leave `DB_DIRECT_HOST` unset and provide only
the direct port and username.

## What It Does

- Enables PDO emulated prepares for pooled PostgreSQL connections unless the
  option is explicitly configured.
- Keeps direct PostgreSQL connections on native prepares unless the direct
  config explicitly opts into emulated prepares.
- Adds `connection::direct` routing through the package database manager.
- Routes migrations to `connection::direct` when a direct endpoint is configured.
- Uses the direct endpoint for PostgreSQL schema dump/load.
- Routes `db:wipe`, `db:show`, and `db:table` through direct when appropriate.
- Adds `php artisan db --pooled`; without `--pooled`, `php artisan db` defaults
  to direct only when the raw connection config has `pooled => true`.
- Converts PHP boolean bindings to PostgreSQL-compatible `'true'` / `'false'`
  strings when PDO emulated prepares are active.

## Boolean Bindings

When PDO emulated prepares are active, PostgreSQL receives the query with values
interpolated on the client side. For boolean columns, bind PHP booleans:

```php
User::where('active', true)->get();
```

Avoid integer boolean values under pooled emulated prepares:

```php
User::where('active', 1)->get();
```

Laravel cannot infer column types from an arbitrary integer binding, so `1`
remains an integer literal.

## Disabling Behavior

```dotenv
POSTGRES_POOLED_MODE_ENABLED=false
POSTGRES_POOLED_MODE_REPLACE_COMMANDS=false
POSTGRES_POOLED_MODE_MIGRATIONS_USE_DIRECT=false
```

## Testing

```bash
composer test
composer format
```

## License

The MIT License (MIT). Please see [LICENSE](LICENSE) for more information.
