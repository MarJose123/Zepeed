> [!WARNING]
> **This project is under active development.**
>
> Features, APIs, configuration options, and internal behavior may change without notice. Breaking changes can occur between releases as the project evolves.
>
> If you depend on this project in production, consider pinning to a specific version and reviewing release notes before upgrading.

# Zepeed

Zepeed is a powerful speedtest aggregator and monitor built with Laravel 13, Inertia.js, and Vue 3. It allows you to schedule and run speedtests using multiple providers like Speedtest Ookla, LibreSpeed, and Fast.com, providing a unified dashboard for monitoring your network performance.

![banner.png](/.showcase/banner.png)

## Features

- **Multiple Providers**: Support for Speedtest Ookla, LibreSpeed, Cloudflare, and Fast.com.
- **Scheduled Runs**: Automated speedtests with customizable schedules.
- **Customizable Templates**: Create and manage your own speedtest templates email notifications.
- **Maintenance Windows**: Define periods where tests should be paused or restricted.
- **Manual Triggers**: Run speedtests on demand from the dashboard.
- **Real-time Notifications**: Get notified of run successes or failures via Inertia notifications.
- **Modern Stack**: Built with Laravel 12, Vue 3, shadcn/vue, and Tailwind CSS v4.
- **Webhooks**: Integrate with your favorite services for real-time notifications.
- **Email Services**: Send speedtest results via SMTP, Mailgun, Postmark, AWS SES, and more.
- **Ping Test**: Check the latency of your internet connection.

## Stack

- **Backend**: Laravel 12, Fortify, Inertia.js (Laravel adapter)
- **Frontend**: Vue 3, shadcn/vue (reka-ui), Tailwind CSS v4
- **Tooling**: Vite, TypeScript, ESLint, Prettier, Pint, PHPStan, Rector
- **Extras**: Mailpit, Soketi/Reverb-ready scripts

## Requirements

- PHP 8.4+
- Composer
- Bun
- Docker Desktop or Orbstack

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

## Useful scripts

```bash
composer run format     # Pint + Prettier + ESLint
composer run analyse    # PHPStan
composer run test       # PHPUnit
composer run rector     # Rector fixes
```

## Speedtest Providers
- [mikkelam/fast-cli](https://github.com/mikkelam/fast-cli)
- [librespeed/speedtest-cli](https://github.com/librespeed/speedtest-cli)
- [ookla/speedtest-cli](https://www.speedtest.net/apps/cli)
- [kavehtehrani/cloudflare-speed-cli](https://github.com/kavehtehrani/cloudflare-speed-cli)

## License

MIT
