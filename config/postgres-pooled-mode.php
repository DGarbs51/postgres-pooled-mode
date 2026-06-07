<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Package Enabled
    |--------------------------------------------------------------------------
    |
    | When enabled, the package replaces Laravel's database manager and
    | connection factory with direct-aware variants. Only PostgreSQL
    | connections that set "pooled" or "direct" are changed.
    |
    */

    'enabled' => env('POSTGRES_POOLED_MODE_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Console Command Overrides
    |--------------------------------------------------------------------------
    |
    | These overrides route db:wipe, db:show, db:table, and db through a direct
    | endpoint when the command should avoid a transaction pooler.
    |
    */

    'replace_console_commands' => env('POSTGRES_POOLED_MODE_REPLACE_COMMANDS', true),

    /*
    |--------------------------------------------------------------------------
    | Migration Routing
    |--------------------------------------------------------------------------
    |
    | When enabled, migrations use the configured direct endpoint for PostgreSQL
    | connections with a non-empty "direct" configuration.
    |
    */

    'migrations_use_direct' => env('POSTGRES_POOLED_MODE_MIGRATIONS_USE_DIRECT', true),
];
