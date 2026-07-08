<p align="center">
  <img src=".showcase/Zepeed_MD_Logo.png" alt="Zepeed" width="200">
</p>
<h1 align="center">Zepeed</h1>

<p align="center"><strong>A self-hosted internet speed tracker for monitoring ISP performance.</strong></p>

<div align="center">

![Docker Image Version](https://img.shields.io/docker/v/marjose123/zepeed?style=flat-square&label=Docker%20Image)
![GitHub Release](https://img.shields.io/github/v/release/marjose123/zepeed?style=flat-square&label=Latest%20Release)

![GitHub License](https://img.shields.io/github/license/marjose123/zepeed?style=flat-square&label=License)
![GitHub Actions Workflow Status](https://img.shields.io/github/actions/workflow/status/marjose123/Zepeed/api-tests.yml?branch=main&style=flat-square&label=API%20Test)
![GitHub Actions Workflow Status](https://img.shields.io/github/actions/workflow/status/marjose123/Zepeed/phpstan.yml?branch=main&style=flat-square&label=PHPStan)
![GitHub Actions Workflow Status](https://img.shields.io/github/actions/workflow/status/marjose123/Zepeed/lint.yml?branch=main&style=flat-square&label=Code%20Style)
![GitHub Actions Workflow Status](https://img.shields.io/github/actions/workflow/status/marjose123/Zepeed/mcp-tests.yml?branch=main&style=flat-square&label=MCP%20Tests)



</div>

Zepeed is a powerful speedtest aggregator and monitor built with Laravel 13, Inertia.js, and Vue 3. It allows you to schedule and run speedtests using multiple providers like Speedtest Ookla, LibreSpeed, and Fast.com, providing a unified dashboard for monitoring your network performance.

[**Explore the Documentation »**](https://zepeed.mintlify.app/)

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
- **Prometheus Integration**: Expose a `/metrics` scrape endpoint for monitoring with Prometheus and Grafana.
- **Email Services**: Send speedtest results via SMTP, Mailgun, Postmark, AWS SES, and more.
- **Ping Test**: Check the latency of your internet connection.
- **MCP Server**: AI-ready Model Context Protocol server for querying monitoring data programmatically.

## Stack

- **Backend**: Laravel 13, Fortify, Inertia.js (Laravel adapter)
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
composer run test:mcp   # MCP tests
composer run rector     # Rector fixes
```

## MCP Server

Zepeed exposes an MCP (Model Context Protocol) server at `/mcp/zepeed` that AI tools like Claude, Cursor, and GitHub Copilot can connect to. All tools require a Sanctum API token for authentication.

### 1. Generate an API Token

First, create a Sanctum token for your MCP client:

```bash
php artisan tinker --execute '
$user = App\Models\User::factory()->create();
$token = $user->createToken("mcp")->plainTextToken;
echo $token;
'
```

Copy the token — you'll need it to configure your AI client below.

### 2. Connect Your AI Client

Choose your AI tool and add the Zepeed MCP server configuration:

#### Claude Desktop

Open **Claude** → **Settings** → **Developer** → **Edit Config**, and add:

```json
{
  "mcpServers": {
    "zepeed": {
      "type": "url",
      "url": "http://localhost:8000/mcp/zepeed",
      "headers": {
        "Authorization": "Bearer YOUR_TOKEN_HERE"
      }
    }
  }
}
```

Restart Claude. You can now ask things like *"What version of Zepeed is running?"* — Claude will call the `GetAppVersion` tool automatically.

#### Cursor

Create or edit `.cursor/mcp.json` in your project root:

```json
{
  "mcpServers": {
    "zepeed": {
      "type": "url",
      "url": "http://localhost:8000/mcp/zepeed",
      "headers": {
        "Authorization": "Bearer YOUR_TOKEN_HERE"
      }
    }
  }
}
```

Cursor will pick up the config on next launch. Open the Cursor AI panel and try: *"Use the Zepeed MCP to check the app version."*

#### VS Code / GitHub Copilot

Create `.vscode/mcp.json` in your workspace:

```json
{
  "servers": {
    "zepeed": {
      "type": "url",
      "url": "http://localhost:8000/mcp/zepeed",
      "headers": {
        "Authorization": "Bearer YOUR_TOKEN_HERE"
      }
    }
  }
}
```

Restart VS Code and ask Copilot: *"What tools does the Zepeed MCP server expose?"*

#### Continue.dev (VS Code / JetBrains)

Add to your `~/.continue/config.json`:

```json
{
  "experimental": {
    "mcpServers": {
      "zepeed": {
        "type": "url",
        "url": "http://localhost:8000/mcp/zepeed",
        "headers": {
          "Authorization": "Bearer YOUR_TOKEN_HERE"
        }
      }
    }
  }
}
```

### 3. What You Can Ask

Once connected, your AI can query Zepeed data using natural language:

| Prompt | Tool Called |
|--------|-------------|
| *"What version of Zepeed is running?"* | `GetAppVersion` |
| *"Show me the last 10 ping results"* | `ListPingResults` |
| *"List all speedtest results from the last 24 hours"* | `ListSpeedtestResults` |
| *"Which internet providers are configured?"* | `ListProviders` |
| *"Show me the maintenance windows"* | `ListMaintenanceWindows` |
| *"What tools are available?"* | lists all 6 tools |

> **Note:** Replace `http://localhost:8000` with your production URL when deploying. All examples above use `GetAppVersion` — the simplest read-only tool — to verify connectivity.

## Speedtest Providers
- [mikkelam/fast-cli](https://github.com/mikkelam/fast-cli)
- [librespeed/speedtest-cli](https://github.com/librespeed/speedtest-cli)
- [ookla/speedtest-cli](https://www.speedtest.net/apps/cli)
- [kavehtehrani/cloudflare-speed-cli](https://github.com/kavehtehrani/cloudflare-speed-cli)

## License

MIT
