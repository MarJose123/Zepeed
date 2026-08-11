<p align="center">
  <img src="/.showcase/Zepeed_MD_Logo.png" alt="Zepeed" width="200"/>
</p>
<h1 align="center">Zepeed</h1>

<p align="center"><strong>A self-hosted internet speed tracker for monitoring ISP performance.</strong></p>

<div align="center">

![Docker Image Version](https://img.shields.io/docker/v/marjose123/zepeed?style=flat-square&label=Docker%20Image&cacheSeconds=86400)
![GitHub Release](https://img.shields.io/github/v/release/marjose123/zepeed?style=flat-square&label=Latest%20Release&cacheSeconds=86400)
![Docker Pulls](https://img.shields.io/docker/pulls/marjose123/zepeed?style=flat-square&label=Docker%20Pulls&cacheSeconds=86400)


![GitHub License](https://img.shields.io/github/license/marjose123/zepeed?style=flat-square&label=License&cacheSeconds=432000)
![GitHub Actions Workflow Status](https://img.shields.io/github/actions/workflow/status/marjose123/Zepeed/api-tests.yml?branch=v1.x&style=flat-square&label=API%20Test&cacheSeconds=86400)
![GitHub Actions Workflow Status](https://img.shields.io/github/actions/workflow/status/marjose123/Zepeed/phpstan.yml?branch=v1.x&style=flat-square&label=PHPStan&cacheSeconds=86400)
![GitHub Actions Workflow Status](https://img.shields.io/github/actions/workflow/status/marjose123/Zepeed/lint.yml?branch=v1.x&style=flat-square&label=Code%20Style&cacheSeconds=86400)
![GitHub Actions Workflow Status](https://img.shields.io/github/actions/workflow/status/marjose123/Zepeed/mcp-tests.yml?branch=v1.x&style=flat-square&label=MCP%20Tests&cacheSeconds=86400)



</div>

Zepeed is a powerful speedtest aggregator and monitor built with Laravel 13, Inertia.js, and Vue 3. It allows you to schedule and run speedtests using multiple providers like Speedtest Ookla, LibreSpeed, and Fast.com, providing a unified dashboard for monitoring your network performance.

[**Explore the Documentation**](https://zepeed.mintlify.app/)

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
- **Apprise**: Fan out notifications to 80+ messaging services (Slack, Discord, Telegram, Signal, and more) through a self-hosted Apprise gateway.
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

Zepeed exposes an MCP (Model Context Protocol) server at `/mcp/zepeed` that AI tools like Claude, Cursor, and GitHub Copilot can connect to. All tools require a Sanctum API token for authentication, and every tool enforces the same [token abilities](#token-abilities) as the REST API — an AI can query monitoring data and manage providers, maintenance windows, webhooks, alerts, and exports, but only within the permissions granted to its token.

### 1. Generate an API Token

Log in to the Zepeed web UI and navigate to **Settings → API Tokens** (or the token management page). Create a new token with a descriptive name like `"mcp"` and copy the generated token — you'll need it to configure your AI client below.

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

Once connected, your AI can query and manage Zepeed using natural language:

| Prompt | Tool Called |
|--------|-------------|
| *"What version of Zepeed is running?"* | `GetAppVersion` |
| *"Show me the last 10 ping results"* | `ListPingResults` |
| *"List all speedtest results from the last 24 hours"* | `ListSpeedtestResults` |
| *"Which internet providers are configured?"* | `ListProviders` |
| *"Run a speedtest on Ookla now"* | `RunSpeedtest` |
| *"Show me the maintenance windows"* | `ListMaintenanceWindows` |
| *"Pause all speedtest runs"* | `ToggleGlobalPause` |
| *"List my webhooks and alert rules"* | `ListWebhooks`, `ListAlertRules`, `ListPingAlertRules` |
| *"Create an export of last month's ping results"* | `CreateExport` |
| *"What tools are available?"* | lists all 30 tools |

> **Note:** Replace `http://localhost:8000` with your production URL when deploying. All examples above use `GetAppVersion` — the simplest read-only tool — to verify connectivity.

### 4. Permissions (Token Abilities)

Every MCP tool maps to a REST API endpoint and requires the same Sanctum token ability as that endpoint. Read tools accept **any** ability of their module (`ability:` middleware semantics); write tools require their **specific** ability (`abilities:` middleware semantics). A token with the wildcard `*` ability (the default when creating a token) passes every gate.

| Module | Ability | MCP Tools |
|--------|---------|-----------|
| App | `app:view` | `GetAppVersion` |
| Ping Results | `ping-results:view` | `ListPingResults` |
| Speedtest | `speedtest:view` / `speedtest:run` | `ListSpeedtestResults` / `RunSpeedtest` |
| Providers | `providers:view`, `providers:update` | `ListProviders`, `UpdateProvider` |
| Schedules | `schedules:view`, `schedules:create`, `schedules:update`, `schedules:delete` | `ListProviderSchedules` |
| Maintenance | `maintenance:view`, `maintenance:create`, `maintenance:update`, `maintenance:delete` | `ListMaintenanceWindows`, `CreateMaintenanceWindow`, `UpdateMaintenanceWindow`, `DeleteMaintenanceWindow`, `ToggleGlobalPause` |
| Webhooks | `webhooks:view`, `webhooks:create`, `webhooks:update`, `webhooks:delete`, `webhooks:test` | `ListWebhooks`, `CreateWebhook`, `UpdateWebhook`, `DeleteWebhook`, `TestWebhook` |
| Apprise | `apprise:view`, `apprise:create`, `apprise:update`, `apprise:delete`, `apprise:test` | — (no MCP tools yet; use the REST API or web UI) |
| Alerts | `alerts:view`, `alerts:create`, `alerts:update`, `alerts:delete` | `ListAlertRules`, `CreateAlertRule`, `UpdateAlertRule`, `DeleteAlertRule`, `ToggleAlertRule` |
| Ping Alerts | `ping-alerts:view`, `ping-alerts:create`, `ping-alerts:update`, `ping-alerts:delete` | `ListPingAlertRules`, `CreatePingAlertRule`, `UpdatePingAlertRule`, `DeletePingAlertRule`, `TogglePingAlertRule` |
| Exports | `exports:view`, `exports:create` | `ListExports`, `CreateExport`, `GetExport` |

When a token lacks the required ability (or no token is presented), the tool returns an error and performs no action.

### 5. MCP & API Parity

The MCP server mirrors the REST API 1:1. The table below maps every MCP tool to its REST endpoint and required ability, so an API token scoped for the REST API behaves identically over MCP.

| MCP Tool | REST API Endpoint | Required Ability |
|----------|-------------------|------------------|
| `GetAppVersion` | `GET /api/v1/app/version` | `app:view` |
| `ListPingResults` | `GET /api/v1/pings` | `ping-results:view` |
| `ListSpeedtestResults` | `GET /api/v1/speedtest/results` | `speedtest:view` |
| `ListProviders` | `GET /api/v1/providers` | `providers:view` or `providers:update` |
| `UpdateProvider` | `PATCH /api/v1/providers/{provider}` | `providers:update` |
| `RunSpeedtest` | `POST /api/v1/providers/{provider}/run-now` | `speedtest:run` |
| `ListProviderSchedules` | `GET /api/v1/providers/schedules` | any `schedules:*` |
| `ListMaintenanceWindows` | `GET /api/v1/maintenance/schedules` | any `maintenance:*` |
| `CreateMaintenanceWindow` | `POST /api/v1/maintenance/schedules` | `maintenance:create` |
| `UpdateMaintenanceWindow` | `PATCH /api/v1/maintenance/schedules/{id}` | `maintenance:update` |
| `DeleteMaintenanceWindow` | `DELETE /api/v1/maintenance/schedules/{id}` | `maintenance:delete` |
| `ToggleGlobalPause` | `POST /api/v1/maintenance/global-pause` | `maintenance:update` |
| `ListWebhooks` | `GET /api/v1/webhooks` | any `webhooks:*` |
| `CreateWebhook` | `POST /api/v1/webhooks` | `webhooks:create` |
| `UpdateWebhook` | `PATCH /api/v1/webhooks/{id}` | `webhooks:update` |
| `DeleteWebhook` | `DELETE /api/v1/webhooks/{id}` | `webhooks:delete` |
| `TestWebhook` | `POST /api/v1/webhooks/{id}/test` | `webhooks:test` |
| `ListAlertRules` | `GET /api/v1/alerts` | any `alerts:*` |
| `CreateAlertRule` | `POST /api/v1/alerts` | `alerts:create` |
| `UpdateAlertRule` | `PATCH /api/v1/alerts/{id}` | `alerts:update` |
| `DeleteAlertRule` | `DELETE /api/v1/alerts/{id}` | `alerts:delete` |
| `ToggleAlertRule` | `POST /api/v1/alerts/{id}/toggle` | `alerts:update` |
| `ListPingAlertRules` | `GET /api/v1/ping-alerts` | any `ping-alerts:*` |
| `CreatePingAlertRule` | `POST /api/v1/ping-alerts` | `ping-alerts:create` |
| `UpdatePingAlertRule` | `PATCH /api/v1/ping-alerts/{id}` | `ping-alerts:update` |
| `DeletePingAlertRule` | `DELETE /api/v1/ping-alerts/{id}` | `ping-alerts:delete` |
| `TogglePingAlertRule` | `POST /api/v1/ping-alerts/{id}/toggle` | `ping-alerts:update` |
| `ListExports` | `GET /api/v1/exports` | `exports:view` or `exports:create` |
| `CreateExport` | `POST /api/v1/exports` | `exports:create` |
| `GetExport` | `GET /api/v1/exports/{id}` | `exports:view` or `exports:create` |

Not exposed over MCP (use the REST API or web UI instead): the Apprise API (`/api/v1/apprise*` — notification gateway management), `GET /api/v1/webhooks/{id}/deliveries` (delivery history) and `GET /api/v1/exports/{id}/download` (binary download — `GetExport` returns the file contents for `csv`/`json` exports when `include_content` is set).

## Speedtest Providers
- [mikkelam/fast-cli](https://github.com/mikkelam/fast-cli)
- [librespeed/speedtest-cli](https://github.com/librespeed/speedtest-cli)
- [ookla/speedtest-cli](https://www.speedtest.net/apps/cli)
- [kavehtehrani/cloudflare-speed-cli](https://github.com/kavehtehrani/cloudflare-speed-cli)

## License

MIT
