<?php

declare(strict_types=1);

use Stancl\Tenancy\Bootstrappers\CacheTenancyBootstrapper;
use Stancl\Tenancy\Bootstrappers\FilesystemTenancyBootstrapper;
use Stancl\Tenancy\Database\Models\Domain;
use Stancl\Tenancy\Database\Models\Tenant;
use Stancl\Tenancy\UUIDGenerator;

return [

    'tenant_model' => Tenant::class,
    'id_generator' => UUIDGenerator::class,

    'domain_model' => Domain::class,

    /*
    |--------------------------------------------------------------------------
    | Central Domains
    |--------------------------------------------------------------------------
    |
    | The list of domains hosting the central application. Only relevant when
    | using the domain or subdomain identification middleware. A request
    | arriving on one of these domains is treated as central (not tenant).
    |
    */

    'central_domains' => [
        '127.0.0.1',
        'localhost',
    ],

    /*
    |--------------------------------------------------------------------------
    | Tenancy Bootstrappers
    |--------------------------------------------------------------------------
    |
    | Executed when tenancy is initialized; they make Laravel features
    | tenant-aware. This module uses single-database tenancy, so the
    | DatabaseTenancyBootstrapper is intentionally omitted - data stays
    | in one database and is scoped by tenant_id columns.
    |
    */

    'bootstrappers' => [
        CacheTenancyBootstrapper::class,
        FilesystemTenancyBootstrapper::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Tenancy
    |--------------------------------------------------------------------------
    |
    | Used by CacheTenancyBootstrapper. Every cache key gets a tag based on
    | tag_base + tenant_id, scoping the cache per tenant. The tag is also
    | used to selectively flush a tenant's cache.
    |
    */

    'cache' => [
        'tag_base' => 'tenant',
    ],

    /*
    |--------------------------------------------------------------------------
    | Filesystem Tenancy
    |--------------------------------------------------------------------------
    |
    | Used by FilesystemTenancyBootstrapper. Disks listed below are suffixed
    | with suffix_base + tenant_id so tenant uploads never collide.
    |
    */

    'filesystem' => [
        'suffix_base' => 'tenant',
        'disks' => [
            'local',
            'public',
        ],
        'root_override' => [
            'local' => '%storage_path%/app/',
            'public' => '%storage_path%/app/public/',
        ],
        'suffix_storage_path' => true,
        'asset_helper_tenancy' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Features
    |--------------------------------------------------------------------------
    |
    | Additional classes run regardless of whether tenancy is initialized.
    | None are needed for the single-database starterkit setup.
    |
    */

    'features' => [],

    /*
    |--------------------------------------------------------------------------
    | Tenancy Routes
    |--------------------------------------------------------------------------
    |
    | Disabled: the starterkit is API-first and does not serve tenant asset
    | routes from the package.
    |
    */

    'routes' => false,

    /*
    |--------------------------------------------------------------------------
    | Tenant Migration Parameters
    |--------------------------------------------------------------------------
    |
    | Used by tenants:migrate. Points at the tenant-scoped migrations folder
    | inside this module.
    |
    */

    'migration_parameters' => [
        '--force' => true,
        '--path' => [realpath(__DIR__.'/../Database/Migrations/tenant')],
        '--realpath' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Tenant Seeder Parameters
    |--------------------------------------------------------------------------
    |
    | Used by tenants:seed.
    |
    */

    'seeder_parameters' => [
        '--class' => 'DatabaseSeeder',
    ],

];
