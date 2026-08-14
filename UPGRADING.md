# Upgrading Zepeed 1.x → 2.x

Zepeed **2.x** renames the speedtest **Alert Rules** feature to **Workflow
Rules** (models, REST API endpoints, MCP tools, token abilities, database
tables, and the web UI). Your data and configuration are preserved
automatically — the migration renames the tables **in place** and rewrites
existing API token abilities, so **no manual data move is required**.

> The separate **Ping Alert Rules** feature is **not** renamed and is
> unaffected by this upgrade.

## TL;DR

1. **Back up your database.**
2. **Deploy the 2.x code** (pull the new image / checkout the new release).
3. Run `php artisan migrate --force`.
4. Run `php artisan db:seed --force` (optional but recommended — idempotent,
   re-runs the token-ability rewrite as a safety net).
5. **Restart long-running processes** so they load the new code:
   - `php artisan queue:restart`, then restart PHP-FPM as needed, or
   - Docker: recreate the container (the queue workers and scheduler are
     s6-overlay services that start fresh with the new image).
6. If you build frontend assets yourself (outside the image):
   `bun install --ignore-scripts && bun run build`.

## What happens automatically

### 1. Tables are renamed in place (data preserved)

The migration `database/migrations/2026_08_12_000001_rename_legacy_alert_rules_to_workflow_rules_table.php`
renames the tables and their foreign-key column — all rows are kept:

| 1.x | 2.x |
| --- | --- |
| `alert_rules` | `workflow_rules` |
| `alert_rule_conditions` | `workflow_rule_conditions` |
| `alert_rule_actions` | `workflow_rule_actions` |

The FK column `alert_rule_id` on `workflow_rule_conditions` and
`workflow_rule_actions` is renamed to `workflow_rule_id`.

### 2. Existing API tokens keep working

Stored token abilities are rewritten by the migration (`up()`), so a plain
`php artisan migrate` is enough:

| 1.x | 2.x |
| --- | --- |
| `alerts:view` | `workflow-rules:view` |
| `alerts:create` | `workflow-rules:create` |
| `alerts:update` | `workflow-rules:update` |
| `alerts:delete` | `workflow-rules:delete` |

`WorkflowRulesMigratorSeeder` (registered in `DatabaseSeeder`) rewrites the
same abilities and is fully idempotent — running `php artisan db:seed --force`
after migrating is a harmless safety net.

### 3. Fresh 2.x installs need nothing extra

On a fresh 2.x install the tables are created directly as `workflow_*`; the
rename migration detects the legacy tables are absent and becomes a no-op.

## Breaking changes

| Area | 1.x | 2.x |
| --- | --- | --- |
| REST API | `/api/v1/alerts`, `/api/v1/alerts/{id}`, `/api/v1/alerts/{id}/toggle` | `/api/v1/workflow-rules`, `/api/v1/workflow-rules/{id}`, `/api/v1/workflow-rules/{id}/toggle` (old path removed, **not** aliased) |
| Token abilities | `alerts:*` | `workflow-rules:*` (existing tokens migrated automatically) |
| MCP tools | `ListAlertRules`, `CreateAlertRule`, `UpdateAlertRule`, `DeleteAlertRule`, `ToggleAlertRule` (kebab: `list-alert-rules`, …) | `ListWorkflowRules`, `CreateWorkflowRule`, `UpdateWorkflowRule`, `DeleteWorkflowRule`, `ToggleWorkflowRule` (`list-workflow-rules`, …) |
| Web UI | Settings → **Alert Rules** (`/speedtest/settings/alert-rules`) | Settings → **Workflow Rules** (`/speedtest/settings/workflow-rules`) |

Update any external consumers (scripts, dashboards, AI clients) that call the
old endpoint, tool, or ability names.

### Not renamed (intentionally)

- **Ping Alert Rules** — `ping-alerts:*` abilities, `/api/v1/ping-alerts`,
  `PingAlertRule*` models/tools, `ping_alert_*` tables.
- **Prometheus metric names** — `zepeed_alert_rule_active` and
  `zepeed_alert_rule_last_triggered_timestamp` are a stable external contract
  for dashboards and are kept unchanged.

## Rollback

`php artisan migrate:rollback` runs the migration's `down()`: it restores the
legacy `alert_*` table/column names and flips token abilities back to
`alerts:*`. If you only roll back the rename migration while 2.x code is
still deployed, the app will be broken — roll back the deployment as a unit.

> On MariaDB/MySQL, `down()` performs the same foreign-key column renames as
> `up()` and can hit error 1025 mid-way, leaving a mixed `alert_*`/`workflow_*`
> state. Follow the backup-restore and drop/re-create-FK guidance in
> [Troubleshooting](#troubleshooting) if that happens.

## Verification after upgrading

```sql
-- Your rules should be intact, just under the new names
SELECT COUNT(*) FROM workflow_rules;
SELECT COUNT(*) FROM workflow_rule_conditions;
SELECT COUNT(*) FROM workflow_rule_actions;
```

```bash
# No legacy tables should remain
#   MySQL / MariaDB:  SHOW TABLES LIKE 'alert\_rule%';
#   PostgreSQL:       \dt alert_rule*
#   SQLite:           SELECT name FROM sqlite_master WHERE type='table' AND name LIKE 'alert_rule%';
```

```bash
# An existing token's abilities should now use the workflow-rules: prefix
php artisan tinker --execute '
  $t = \App\Models\PersonalAccessToken::query()->latest("id")->first();
  dump($t?->abilities);
'
```

Then trigger a speedtest and confirm your rules fire.

## Troubleshooting

- **`Mailer [...] is not defined` after upgrade** — a long-running queue worker
  is still executing pre-upgrade code. Run `php artisan queue:restart` and
  recreate the container / restart PHP-FPM.
- **MariaDB/MySQL: the rename migration errors with code 1025** — renaming a
  foreign-key column can hit this on some versions. Test the migration against
  a copy of your production data first; if it fails, drop and re-create the two
  foreign keys around the rename (see the migration file for the exact columns).
- **The migration fails partway** — `Schema::rename` is not transactional on
  MySQL/MariaDB. Restore your backup and re-run, or complete the remaining
  renames manually (the migration is guarded and skips already-renamed tables).
