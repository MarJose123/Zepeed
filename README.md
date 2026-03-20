# Laravel Shadcn/Vue Starter Kit

A Laravel 12 starter kit wired for Inertia.js + Vue 3 with shadcn/vue components, Tailwind CSS v4, and a practical local dev stack.

## Stack

- Backend: Laravel 12, Fortify, Inertia.js (Laravel adapter)
- Frontend: Vue 3, shadcn/vue (reka-ui), Tailwind CSS v4
- Tooling: Vite, TypeScript, ESLint, Prettier, Pint, PHPStan, Rector
- Extras: Ziggy, Mailpit, Soketi/Reverb-ready scripts

## Requirements

- PHP 8.4+ (if running locally)
- Composer (if running locally)
- Docker Desktop / OrbStack / Colima (if using Sail)
- Bun (recommended) or Node 22+
- A database (SQLite/MySQL/Postgres)

## Quick start

### Using Laravel Sail (Docker)

If you have Docker installed, you can get started quickly with Sail:

```bash
cp .env.dev.example .env
composer install
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate
./vendor/bin/sail bun install
./vendor/bin/sail bun run build
```

### Native Setup

If you prefer to run things locally:

```bash
composer run setup
```

This installs PHP/JS deps, creates `.env`, generates an app key, runs migrations, and builds assets.

## Development

### Using Laravel Sail

```bash
./vendor/bin/sail up
```

- App: http://localhost
- Mailpit: http://localhost:8025 (Dashboard)
- Soketi: http://localhost:6001

### Native Development

```bash
composer run dev
```

Runs Vite + queue worker + scheduler + Reverb + Mailpit in one terminal.

If you are using Laravel Herd:

```bash
composer run herd:dev
```

## Useful scripts

```bash
composer run format     # Pint + Prettier + ESLint
composer run analyse    # PHPStan
composer run test       # PHPUnit
composer run rector     # Rector fixes
```

## Frontend structure

- `resources/js/Pages` for Inertia pages
- `resources/js/Components` for shared components
- `resources/js/Components/ui` for shadcn/vue components

## Inertia + Vue

Server-side routes live in `routes/web.php` and render Inertia pages that map to Vue files under `resources/js/Pages`.

## shadcn/vue

shadcn/vue components are generated into `resources/js/Components/ui` and styled with Tailwind. Configure components via `components.json`.

## Environment

Copy `.env.dev.example` to `.env` for local development or `.env.prod.example` for production and adjust your database and mail settings if needed. The setup script handles this automatically for development.

## License

MIT
