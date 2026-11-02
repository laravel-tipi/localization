# Changelog

## Unreleased (1.0.0)

First stable release candidate. See [UPGRADE.md](UPGRADE.md) for breaking changes.

### Added
- Laravel's `route()`, `to_route()`, `redirect()->route()` and signed URLs resolve logical localized names (via `resolveMissingNamedRoutesUsing`), with an optional `locale` parameter. `Localization::hasRoute()`.
- Errors for duplicate localized route names and for names also used by a regular route, in any registration order (checked at registration and again after boot), and a reserved-segment policy for locale codes equal to static route segments.
- `LocaleContext::isDefault()` for the context default (`Locale::isDefault()` stays the global default).
- CI matrix for SQLite, MySQL, MariaDB and PostgreSQL.
- Locale contexts (config- or database-backed) with per-context defaults and preferences.
- Context management actions: `CreateLocaleContext`, `UpdateLocaleContext`, `DeleteLocaleContext`.
- `DeleteLocale` and `ReorderLocales` actions; `position` column for deterministic ordering.
- Locale switch endpoint, `switchUrl()`, `switcher()`, `alternates()`, `currentUrl()`, `routeIs()`, `currentRouteName()`, `select()`, `preference()`, `negotiate()`.
- `UseLocaleContext` middleware (`localization.context:{name}`).
- MariaDB support.
- Management actions require locales and contexts to share one database connection when both are stored, so combined changes are atomic.

### Removed
- `localized_route()` helper (use `route()`).
- Pre-V1 schema upgrades: `create_locales_table` and `create_locale_contexts_tables` are the V1 schema, with no upgrade migration (see UPGRADE.md).

### Fixed
- Negotiation fell back to the first locale instead of the default, and crashed on regional codes.
- Root negotiation trapped visitors in a previously visited locale.
- Unprefixed catch-all routes captured locale prefixes; unnamed routes redirected to the wrong page.
- Default-locale redirects broke POST requests and used 302.
- `selectLocale()` never persisted its cookie; visits overwrote preferences and sent `Set-Cookie` on every response.
- The current locale went stale after `App::setLocale()`/`withLocale()` and threw for unsupported app locales.
- `localizedUrl()` threw on non-localized pages, turning 404s into 500s.
- Route parameters were shifted by the `{locale}` parameter in closures and scalar controller arguments.
- `locales_table` was ignored; `code` column too short; transactions used the default connection instead of the models' connection.
- Authorization allowed every authenticated user by default.
