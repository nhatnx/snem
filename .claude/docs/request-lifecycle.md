# Request lifecycle

Read this before changing `SNEMLibrary.php`, routing, or rendering. Public behaviour is documented in the README; this covers the order of operations and the edge cases around it.

## Bootstrap

`ADODB_DIR` comes from adodb's Composer `files` autoload, with a fallback at the top of `SNEMLibrary.php`, so apps never define it.

`createApplication($config)` accepts an array or a path to a PHP file returning one, and throws `SEException` on a second call. The constructor then runs in this order:

1. `resolveConfig()` merges with `array_replace_recursive`. Path keys default to subdirectories of `basePath`. `autoloadPaths` is the exception: an override replaces the list, because index-wise merging would be meaningless.
2. `self::$_app` is published, so templates rendered during construction can call `app()`.
3. If `maintenanceMode.enabledMode` is set, `offline.php` renders and the request dies. This happens before the autoloader and the database exist, so `offline.php` can't use either.
4. `registerAppAutoloader()` walks `autoloadPaths` recursively and maps short class name to file path, skipping `index.php`. The map is built eagerly on every request; only the `require_once` is lazy. On a duplicate short name, the last file scanned wins.
5. `ADODatabase` connects, and the raw connection becomes the Active Record adapter.

## `run()`

1. Starts the session if none is active and headers aren't sent.
2. Applies `locale` and `debugMode` (`error_reporting`).
3. Creates `SEUser`, `SEInput`, and `SEHelper` unless a helper is already set.
4. Resolves `page` through `sanitizeRouteToken()`. An empty token or a missing `{pagePath}/X/X.php` sends a 404 header, renders `notFound.php`, and dies.
5. Calls `dispatch()`.
6. Opens an output buffer that rewrites `<title>` to the site name, then requires `{templatePath}/{templateName}/{templateName}.php`.

The template calls `$snem->mainBody()`, which requires the page file and runs `new $page($this)`. `SEPage::__construct` echoes `getView()`.

## `dispatch()`

- `task` and `action` read GET first, then POST, and default to `default`. `page` goes through `$input->request()` instead.
- A rejected `task` or `action` returns early with no view. Only `page` produces a 404, so the template still renders with status 200.
- `{pagePath}/X/controllers/YController.php` is required if present. `class_exists()` then runs through the autoloader, so a `YController` in `autoloadPaths` also resolves. Otherwise it falls back to `SEController`.
- `beforeAction()` and `afterAction()` run whether or not the action exists. A missing action leaves the view empty, with no 404.
- Only the action's output is captured into `$view`. Output from `beforeAction()` or `afterAction()` goes straight to the response, ahead of the template.

## Route token constraint

`sanitizeRouteToken()` allows `-`, but the tokens become PHP identifiers:

- `?page=my-page` passes the file check, then `new $page` fatals because a hyphen can't be in a class name.
- A hyphenated `task` falls back to `SEController`.
- A hyphenated `action` finds no method.

Avoid hyphens in page, task, and action names.
