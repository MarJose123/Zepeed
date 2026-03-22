# Zepeed

Zepeed is a powerful speedtest aggregator and monitor built with Laravel 12, Inertia.js, and Vue 3. It allows you to schedule and run speedtests using multiple providers like Speedtest Ookla, LibreSpeed, and Fast.com, providing a unified dashboard for monitoring your network performance.

## Features

- **Multiple Providers**: Support for Speedtest Ookla, LibreSpeed, and Fast.com.
- **Scheduled Runs**: Automated speedtests with customizable schedules.
- **Maintenance Windows**: Define periods where tests should be paused or restricted.
- **Manual Triggers**: Run speedtests on demand from the dashboard.
- **Real-time Notifications**: Get notified of run successes or failures via Inertia notifications.
- **Modern Stack**: Built with Laravel 12, Vue 3, shadcn/vue, and Tailwind CSS v4.

## Stack

- **Backend**: Laravel 12, Fortify, Inertia.js (Laravel adapter)
- **Frontend**: Vue 3, shadcn/vue (reka-ui), Tailwind CSS v4
- **Tooling**: Vite, TypeScript, ESLint, Prettier, Pint, PHPStan, Rector
- **Extras**: Ziggy, Mailpit, Soketi/Reverb-ready scripts

## Requirements

- PHP 8.4+
- Composer
- Bun (recommended) or Node 22+
- A database (SQLite/MySQL/Postgres)
- Speedtest CLI tools (depending on the provider used)

## Quick start

### Native Setup

If you prefer to run things locally:

```bash
composer run setup
```

This installs PHP/JS deps, creates `.env`, generates an app key, runs migrations, and builds assets.

## Development

### Native Development

```bash
composer run dev
```

Runs Vite + queue worker + scheduler + Reverb + Mailpit in one terminal.

If you are using Laravel Herd:

```bash
composer run herd:dev
```

## Contributing

We welcome contributions! Please follow these steps:

1.  **Fork and Clone**: Fork the repository and clone it to your local machine.
2.  **Setup Environment**: Follow the [Quick start](#quick-start) instructions.
3.  **Create a Branch**: Create a feature branch for your changes (`git checkout -b feature/amazing-feature`).
4.  **Code Standards**: Ensure your code follows the project's standards by running:
    ```bash
    composer run format   # Fixes Pint, Prettier, and ESLint issues
    composer run analyse  # Runs PHPStan static analysis
    composer run rector   # Applies Rector refactorings
    ```
5.  **Test**: Make sure all tests pass before submitting:
    ```bash
    composer run test
    ```
6.  **Submit PR**: Push your branch and create a Pull Request with a clear description of your changes.

## Deployment

### Production Environment

1.  **Environment File**: Copy `.env.prod.example` to `.env` and configure your production database, mail, and other settings.
    ```bash
    cp .env.prod.example .env
    ```
2.  **Install Dependencies**:
    ```bash
    composer install --no-dev --optimize-autoloader
    bun install
    ```
3.  **Build Assets**:
    ```bash
    bun run build
    ```
4.  **Optimize**:
    Laravel provides several commands to cache configurations and routes for better performance:
    ```bash
    php artisan optimize
    ```
    *(Note: If `AUTORUN_LARAVEL_OPTIMIZE` is set to `true` in your `.env`, some deployments might handle this automatically).*

5.  **Migrations**: Run your migrations in production:
    ```bash
    php artisan migrate --force
    ```

### Server Requirements

- PHP 8.4+
- Extension requirements (BCMath, Ctype, Fileinfo, JSON, Mbstring, OpenSSL, PDO, Tokenizer, XML)
- A supported database (MariaDB, MySQL, PostgreSQL, or SQLite)

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

Server-side routes live in `routes/web.php` and `routes/speedtest.php`, rendering Inertia pages that map to Vue files under `resources/js/Pages`.

## shadcn/vue

shadcn/vue components are generated into `resources/js/Components/ui` and styled with Tailwind. Configure components via `components.json`.

## Environment

Copy `.env.dev.example` to `.env` for local development or `.env.prod.example` for production and adjust your database and mail settings if needed. The setup script handles this automatically for development.

## License

MIT
