# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

SNEM (`nhatnx/snem`) is a lightweight PHP MVC micro-framework packaged as a Composer **library** — not a runnable app. There is no test suite, linter, or build step. The only dependency is `adodb/adodb-php`; PHP >= 7.4 is required.

Commands:
- `composer install` — install dependencies into `vendor/` (gitignored, along with `composer.lock`)
- `composer dump-autoload` — after adding/renaming classes

PSR-4 maps `app\core\` to the repo root, so `SNEMLibrary.php` at the root is `app\core\SNEMLibrary`, `models/SEUser.php` is `app\core\models\SEUser`, etc. Framework classes are prefixed `SE` and each file contains one class whose short name matches the filename.

## How a request flows

A consuming app calls `SNEMLibrary::createApplication($config)` (singleton; `$config` is an array or a path to a PHP file returning one) followed by `->run()`. `SNEMLibrary::app()` retrieves the singleton anywhere. `ADODB_DIR` is defined automatically by adodb's composer `files` autoload (with a fallback in `SNEMLibrary.php`); apps do not need to define it.

Config is merged over framework defaults in `resolveConfig()` — a minimal app only supplies `name`, `basePath`, and `db`. Path keys (`pagePath`, `templatePath`, `helperPath`, `rootPath`) default to subdirectories of `basePath`; `autoloadPaths` (the app class directories scanned by the lazy autoloader) defaults to `{basePath}/app/controllers|models|helpers` and is replaced wholesale (not merged) when overridden. `run()` auto-starts the PHP session if none is active.

Routing is query-string based, driven entirely by the consuming app's directory layout (configured via `pagePath`, `templatePath`, `templateName` config keys):

1. `?page=X` → requires `{pagePath}/X/X.php` to exist, else a 404 header is sent and the `notFound.php` template is rendered. `page`/`task`/`action` are validated against `[A-Za-z0-9_-]+` by `sanitizeRouteToken()` before touching the filesystem (they flow into paths and class names).
2. `?task=Y` → loads `{pagePath}/X/controllers/YController.php` and instantiates class `YController` (ucfirst'd, global namespace); falls back to base `SEController` if absent.
3. `?action=Z` → calls `actionZ()` (default: `actionDefault`, which renders the view named after the page), wrapped by optional `beforeAction`/`afterAction` hooks. The action's echoed output is captured into `$view` via output buffering.
4. Finally the template `{templatePath}/{templateName}/{templateName}.php` is required; inside it the template calls `$snem->mainBody()`, which instantiates the page class (`SEPage` subclass) that echoes `getView()`.

Controller resolution and action invocation live in `SNEMLibrary::dispatch()`. `SNEMLibrary::__construct` registers a lazy autoloader over the app's `autoloadPaths` directories, keyed by **unqualified** class name → file path (see `registerAppAutoloader`). Those app classes need not be namespaced; the only contract is short-name-matches-filename.

## Database layer

`ADODatabase` wraps the driver-specific `ADOConnection` by composition; methods and properties it doesn't define are forwarded to the live connection via `__call`/`__get`/`__set`, so it can be used anywhere a plain connection would be. `getConn()` returns the raw connection (also the one registered as the Active Record adapter).

- Every SQL string passed to `getAll`/`getRow`/`getCol`/`getOne`/`execute` has the `#__` placeholder replaced with the configured table prefix (`replacePrefix`). Write SQL with `#__table_name`.
- `get*` methods return objects (stdClass DTOs via a json round-trip) by default; pass `$fetchDTO = false` for raw ADOdb arrays. Fetch mode is `ADODB_FETCH_ASSOC` with native case.
- Errors throw `SEDbException` (connect, `execute`, `get*`, forwarded calls, Active Record): the constructor sets the connection's `raiseErrorFn` to `ADODatabase::raiseError`, and `assertResult()` catches the few ADOdb paths that return `false` without calling it. `IgnoreErrors()` still suppresses both. `fnExecute` is set to `clearError()` because drivers cache the last error and mysqli's prepare-failure/placeholder-mismatch paths don't refresh it (stale messages otherwise). `genID()` is wrapped because drivers create a missing sequence by letting a query fail.

Two model base classes:
- `SEModel` — plain model with magic get/set property accessors (`getFoo()`/`setFoo()`) and a per-class singleton via `::model()`, which resolves through `static::class` (never `__CLASS__` — that would bind to the base class).
- `SEActiveRecordModel` — extends ADOdb's `ADODB_Active_Record` (the "x" variant). Subclasses override `getTableName()`, `primaryKeys`, and `relations()` (returning `[name => [SEAR_RELATION_BELONGS_TO|SEAR_RELATION_HAS_MANY, foreignKey, modelClass]]`, resolved lazily via `__get`). `save()` auto-fills `created_at`/`updated_at` in `beforeSave()`.

## The starter example

`examples/starter/` is a working app on the framework (MySQL; `schema.sql` seeds it, `php -S localhost:8000` runs it, `demo`/`demo1234` signs in). It is the fastest way to check a framework change end to end, and it doubles as the copy-me template the README points newcomers at — keep it working when changing routing, rendering, or the model bases.

## Other conventions and gotchas

- Controllers: `render()`/`partialRender()` include view files from `{pagePath}/{page}/views/` with `require` (deliberately not `require_once` — the same partial may render repeatedly; ignore lint rule S2003 there). `partialRender()`, `sendResponse()`, and `jsonResponse()` all `exit()`. `SEJsonController` just forces `format = 'json'`.
- Response format defaults to the controller's `$format` but is overridable per-request with `?format=json|xml`.
- `SEHelper` resolves `$snem->helper->foo` magically to class `FooHelper`, falling back to `app\core\helpers\SEFooHelper`.
- Auth: apps subclass the abstract `SEUserIdentity` and implement `authenticate()`; `SEUser` stores only the username in `$_SESSION` (key from `user_session_name` config) and exposes `isGuest`/`checkAccess()` (403 text comes from the `forbiddenMessage` config key).
- `SNEMLibrary::redirect()` defaults to 302; pass a third argument for 301.
- Flow-control style: the framework uses `die()`/`exit()` liberally (redirects, responses, maintenance/404 pages) — expect side effects, not returned responses.
- `getPath()` resolves `@`-prefixed aliases (`@root`, `@base`, `@page`, `@template`, `@helper`, `@abs`, ...) to config-defined paths; unknown `@x` strips the `@`.
