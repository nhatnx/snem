# CLAUDE.md

SNEM (`nhatnx/snem`) is a lightweight PHP MVC micro-framework shipped as a Composer **library**, not a runnable app. PHP >= 7.4; the only dependency is `adodb/adodb-php`. There is no test suite, linter, or build step.

[README.md](README.md) is the public API reference (routing, controllers, database, models, auth, helpers, config keys). This file covers only what you need to change the framework safely.

## Commands

- `composer install` — installs into `vendor/` (gitignored, along with `composer.lock`)
- `composer dump-autoload` — after adding or renaming classes

## Verifying a change

`examples/starter/` is a working MySQL app and the only end-to-end check: `schema.sql` seeds it, `php -S localhost:8000` runs it, `demo`/`demo1234` signs in. It is also the copy-me template the README points newcomers at, so keep it working when changing routing, rendering, or the model bases.

## Code layout

PSR-4 maps `app\core\` to the repo root: `SNEMLibrary.php` is `app\core\SNEMLibrary`, `models/SEUser.php` is `app\core\models\SEUser`. Framework classes are prefixed `SE`; each file holds one class whose short name matches the filename.

Consuming-app classes are different: they live in the global namespace and load through a lazy autoloader keyed by **unqualified** class name (`registerAppAutoloader`), so short-name-matches-filename is their only contract.

## Request flow

`SNEMLibrary::createApplication($config)->run()`; `SNEMLibrary::app()` returns the singleton. `resolveConfig()` merges config over defaults, and `run()` starts the session if needed.

`?page=X&task=Y&action=Z` maps to `{pagePath}/X/X.php`, `YController`, then `actionZ()`, all resolved in `dispatch()`. The action's echoed output is buffered into `$view`; the template is required afterwards and calls `$snem->mainBody()`, which echoes it through the page class.

Read [.claude/docs/request-lifecycle.md](.claude/docs/request-lifecycle.md) before changing `SNEMLibrary.php`, routing, or rendering.

## Rules that aren't obvious from the code

- `page`/`task`/`action` flow into file paths and class names. They must pass `sanitizeRouteToken()` before touching the filesystem.
- `render()`/`partialRender()` use `require`, deliberately not `require_once`: the same partial may render repeatedly. Ignore lint rule S2003 there.
- `SEModel::model()` must resolve through `static::class`, never `__CLASS__`, which would bind to the base class.
- The framework uses `die()`/`exit()` for flow control (redirects, responses, 404 and maintenance pages), so expect side effects rather than returned responses.

## Database layer

`ADODatabase` wraps the driver's `ADOConnection` by composition and forwards unknown methods and properties through `__call`/`__get`/`__set`. `getConn()` returns the raw connection, also registered as the Active Record adapter.

Read [.claude/docs/database-internals.md](.claude/docs/database-internals.md) before changing `ADODatabase.php`, `SEDbException.php`, or `SEActiveRecordModel.php`.

- Write SQL with `#__table`; `replacePrefix` substitutes the prefix in `getAll`/`getRow`/`getCol`/`getOne`/`execute`.
- Errors throw `SEDbException`. New paths that talk to ADOdb must keep that true: `assertResult()` covers calls that return `false` without raising, and `IgnoreErrors()` must still suppress both.
- `fnExecute` is set to `clearError()` because drivers cache the last error, and mysqli's prepare-failure paths don't refresh it. Removing this brings back stale messages.
- `genID()` is wrapped because drivers create a missing sequence by letting a query fail.
- ADOdb's output goes to `error_log()` through `ADODatabase::outp`. It is echoed only while `$echoOutput` is on, which follows `$db->debug` but not `getConn()->debug`.
