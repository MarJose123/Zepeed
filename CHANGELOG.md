<!--- BEGIN HEADER -->
# Changelog

All notable changes to this project will be documented in this file.

<!--- END HEADER -->
## Release 2.0.0-beta.2 - 2026-08-15

### What's Changed

* ci: integrate GitHub App token for workflows to bypass branch protect… by @MarJose123 in https://github.com/MarJose123/Zepeed/pull/84

**Full Changelog**: https://github.com/MarJose123/Zepeed/compare/v2.0.0-beta.1...v2.0.0-beta.2

## Release 1.4.0 - 2026-08-10

### What's Changed

* chore(deps): bump softprops/action-gh-release from 3 to 3.0.1 by @dependabot[bot] in https://github.com/MarJose123/Zepeed/pull/59
* chore(deps): bump softprops/action-gh-release from 3.0.1 to 3.0.2 by @dependabot[bot] in https://github.com/MarJose123/Zepeed/pull/64
* chore(deps): bump docker/login-action from 4.4.0 to 4.5.1 by @dependabot[bot] in https://github.com/MarJose123/Zepeed/pull/65
* Add API endpoint for fetching latest speedtest results by provider by @MarJose123 in https://github.com/MarJose123/Zepeed/pull/66
* Feat(api): add missing REST API endpoints, Sanctum token abilities, and Scramble API docs by @MarJose123 in https://github.com/MarJose123/Zepeed/pull/67
* feat(mcp): implement missing MCP endpoints in parity with the REST API by @MarJose123 in https://github.com/MarJose123/Zepeed/pull/68
* chore(deps): bump docker/login-action from 4.5.1 to 4.6.0 by @dependabot[bot] in https://github.com/MarJose123/Zepeed/pull/69
* feat(ci): add workflow to update Docker Hub description by @MarJose123 in https://github.com/MarJose123/Zepeed/pull/70
* fix: correct ping-results export route name by @MarJose123 in https://github.com/MarJose123/Zepeed/pull/71
* feat: allow marking individual notifications as read by @MarJose123 in https://github.com/MarJose123/Zepeed/pull/72
* feat(alerts): add Apprise integration for alert notifications by @MarJose123 in https://github.com/MarJose123/Zepeed/pull/74

**Full Changelog**: https://github.com/MarJose123/Zepeed/compare/v1.3.0...v1.4.0

## Release 1.3.0 - 2026-07-11

### What's Changed

* feat(public): add public metrics dashboard with per-provider line charts, dark mode, and real-time refresh by @MarJose123 in https://github.com/MarJose123/Zepeed/pull/44
* chore(deps): bump actions/checkout from 4 to 7 by @dependabot[bot] in https://github.com/MarJose123/Zepeed/pull/43
* feat(dashboard): migrate charts to vccs with range filter, average reference line, and custom dot shape by @MarJose123 in https://github.com/MarJose123/Zepeed/pull/45
* feat(integration): add Prometheus metrics scrape endpoint by @MarJose123 in https://github.com/MarJose123/Zepeed/pull/46
* Refactor: Replace Raw SQL Queries with Eloquent and Improve Metrics Services by @MarJose123 in https://github.com/MarJose123/Zepeed/pull/47
* chore(deps): bump stefanzweifel/git-auto-commit-action from 7 to 7.1.0 by @dependabot[bot] in https://github.com/MarJose123/Zepeed/pull/48
* feat(speedtest-results): redesign result pages with sortable columns, date filters, provider icons, and numbered pagination by @MarJose123 in https://github.com/MarJose123/Zepeed/pull/49
* fix(deps): resolve type errors and peer conflicts from dependency bump by @MarJose123 in https://github.com/MarJose123/Zepeed/pull/50
* feat(mcp): add Model Context Protocol server for AI-assisted monitoring  by @MarJose123 in https://github.com/MarJose123/Zepeed/pull/51
* chore(deps): bump docker/build-push-action from 7.2.0 to 7.3.0 by @dependabot[bot] in https://github.com/MarJose123/Zepeed/pull/52
* chore(deps): bump stefanzweifel/git-auto-commit-action from 7.1.0 to 7.2.0 by @dependabot[bot] in https://github.com/MarJose123/Zepeed/pull/56
* chore(deps): bump docker/metadata-action from 6.1.0 to 6.2.0 by @dependabot[bot] in https://github.com/MarJose123/Zepeed/pull/57
* chore(deps): bump docker/login-action from 4.2.0 to 4.4.0 by @dependabot[bot] in https://github.com/MarJose123/Zepeed/pull/55
* chore(deps): bump docker/setup-buildx-action from 4.1.0 to 4.2.0 by @dependabot[bot] in https://github.com/MarJose123/Zepeed/pull/54
* chore(deps): bump docker/setup-qemu-action from 4.1.0 to 4.2.0 by @dependabot[bot] in https://github.com/MarJose123/Zepeed/pull/53
* feat: implement background data export with real-time notifications by @MarJose123 in https://github.com/MarJose123/Zepeed/pull/58

**Full Changelog**: https://github.com/MarJose123/Zepeed/compare/v1.2.1...v1.3.0

## Release 1.2.1 - 2026-06-24

### What's Changed

* fix(docker): install ext-intl in builder and production stages for lacodix/laravel-model-filter v4 by @MarJose123 in https://github.com/MarJose123/Zepeed/pull/42

**Full Changelog**: https://github.com/MarJose123/Zepeed/compare/v1.2.0...v1.2.1

## Release 1.2.0 - 2026-06-23

### What's Changed

* feat(docker): production Dockerfile, compose, default admin seeding, and Docker Hub CI by @MarJose123 in https://github.com/MarJose123/Zepeed/pull/39
* refactor(api): Enhance controller documentation by @MarJose123 in https://github.com/MarJose123/Zepeed/pull/40
* feat(api): paginated list endpoints with filtering, sorting and rate limiting by @MarJose123 in https://github.com/MarJose123/Zepeed/pull/41

**Full Changelog**: https://github.com/MarJose123/Zepeed/compare/v1.1.0...v1.2.0

## Release 1.1.0 - 2026-06-21

### What's Changed

* feat(sanctum): add API token management with IP and user agent tracking by @MarJose123 in https://github.com/MarJose123/Zepeed/pull/33
* refactor(auth): Remove unused login features by @MarJose123 in https://github.com/MarJose123/Zepeed/pull/34
* chore(deps): bump actions/github-script from 7 to 9 by @dependabot[bot] in https://github.com/MarJose123/Zepeed/pull/36
* chore(deps): bump actions/checkout from 6 to 6.0.3 by @dependabot[bot] in https://github.com/MarJose123/Zepeed/pull/35
* feat: implement authenticated API endpoints with versioning, documentation, and comprehensive testing by @MarJose123 in https://github.com/MarJose123/Zepeed/pull/37
* feat(api): implement v1 monitoring and statistics endpoints by @MarJose123 in https://github.com/MarJose123/Zepeed/pull/38

**Full Changelog**: https://github.com/MarJose123/Zepeed/compare/v1.0.0...v1.1.0
