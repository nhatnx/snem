# SNEM

A lightweight PHP MVC micro-framework, packaged as a Composer library.

SNEM gives you query-string routing, controllers with before/after hooks, plain views + a site-wide template, a thin [ADOdb](https://adodb.org/) database wrapper with table-prefix support, two model base classes (plain and Active Record), session-based auth, and magic helpers — and nothing else.

**Requirements:** PHP >= 7.4, `ext-json`. The only dependency is `adodb/adodb-php`.

**In a hurry?** [`examples/starter/`](examples/starter/) is a complete, runnable
app covering every feature below — load its `schema.sql`, run `php -S localhost:8000`
inside it, and read the code alongside this document.

## Installation

Add the package to your app's `composer.json` via a path or VCS repository.

**Path repository** — when the framework lives next to your app on disk (changes are picked up instantly via a symlink; good for local development):

```json
{
    "repositories": [
        { "type": "path", "url": "../snem" }
    ],
    "require": {
        "nhatnx/snem": "*"
    }
}
```

**VCS repository** — when the framework is hosted in a git repository (Composer reads versions from branches and tags):

```json
{
    "repositories": [
        { "type": "vcs", "url": "https://github.com/nhatnx/snem.git" }
    ],
    "require": {
        "nhatnx/snem": "dev-main"
    }
}
```

Any git host works the same way — e.g. a private server over SSH:

```json
{
    "repositories": [
        { "type": "vcs", "url": "git@git.example.com:nhatnx/snem.git" }
    ],
    "require": {
        "nhatnx/snem": "dev-main"
    }
}
```

With a VCS repository, `dev-main` tracks the `main` branch; once you tag a release (e.g. `git tag v1.0.0`), you can require `"^1.0"` instead.

Then:

```sh
composer install
```

That's it — no constants to define, no manual includes. ADOdb is wired up automatically through Composer's autoloader.

## Local development & releasing

Both repository types above also work fully offline against a checkout on your own disk — useful when the framework and your app live side by side and nothing is published anywhere.

**Live link (develop framework and app together).** Point a path repository at the framework checkout, as in the path example above (an absolute URL like `c:/path/to/snem` is fine), and require `dev-main`:

```json
{
    "repositories": [
        { "type": "path", "url": "../snem" }
    ],
    "require": {
        "nhatnx/snem": "dev-main"
    }
}
```

Run `composer update nhatnx/snem` once; Composer symlinks (on Windows: junctions) `vendor/nhatnx/snem` to the checkout, so every framework edit is visible in the app immediately — no re-install.

Note: require `dev-main`, not `*` — with no tagged release and default stability settings, `*` matches nothing and the install fails.

**Local release (pin the app to a tagged snapshot).** Point a VCS repository at the framework's local git repo and cut releases with tags:

```sh
cd path/to/snem
git tag v1.0.0
```

```json
{
    "repositories": [
        { "type": "vcs", "url": "../snem" }
    ],
    "require": {
        "nhatnx/snem": "^1.0"
    }
}
```

Composer clones from the local `.git` history, so the app gets exactly the `v1.0.0` commit — a real copy in `vendor/`, unaffected by ongoing edits in the framework working tree. To ship the next release: commit, `git tag v1.0.1`, then run `composer update nhatnx/snem` in the app. Only committed and tagged work is visible here; the working tree doesn't count.

A good rhythm: use the live link while iterating on framework and app together, then switch the app to the tagged flow once you want it insulated from framework churn — the swap is just the `repositories` entry and the version constraint.

To run the app locally, PHP's built-in server is enough — from the app root:

```sh
php -S localhost:8000
```

then open `http://localhost:8000/?page=index`.

## Quickstart

The fastest start is to copy [`examples/starter/`](examples/starter/) and edit it.
Built by hand, a minimal app looks like this:

```text
myapp/
├── composer.json
├── config.php
├── index.php               ← entry point
├── app/
│   ├── controllers/        ← shared classes, lazily autoloaded
│   ├── models/
│   └── helpers/
├── pages/
│   └── index/
│       ├── index.php       ← page class (an SEPage subclass)
│       ├── controllers/    ← per-page controllers (optional)
│       └── views/
│           └── index.php   ← default view for this page
└── templates/
    ├── notFound.php        ← rendered for unknown pages (404)
    ├── offline.php         ← rendered in maintenance mode
    └── default/
        └── default.php     ← site-wide layout
```

**index.php**

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use app\core\SNEMLibrary;

SNEMLibrary::createApplication(__DIR__ . '/config.php')->run();
```

**config.php** — everything not listed here falls back to a framework default:

```php
<?php

return [
    'name' => 'My App',
    'basePath' => __DIR__,
    'db' => [
        'driver' => 'mysqli',
        'host' => 'localhost',
        'username' => 'root',
        'password' => '',
        'databaseName' => 'myapp',
        'tablePrefix' => 'myapp_',
        'debugMode' => false,
    ],
];
```

**templates/default/default.php** — the layout; `mainBody()` renders the current page:

```php
<!DOCTYPE html>
<html>
<head>
    <title>My App</title> <!-- auto-replaced with the configured site name -->
</head>
<body>
    <?php \app\core\SNEMLibrary::app()->mainBody(); ?>
</body>
</html>
```

**pages/index/index.php** — the page class (short name must equal the page name):

```php
<?php

class index extends \app\core\SEPage
{
}
```

**pages/index/views/index.php** — the default view:

```php
<h1>Hello from SNEM</h1>
```

Point your web server at the directory and open `/?page=index` (or just `/` — `index` is the default page).

## Routing

Routing is driven by query-string parameters mapped onto your directory layout:

| Parameter | Default | Resolves to |
|---|---|---|
| `?page=X` | `defaultPage` config (`index`) | `{pagePath}/X/X.php` must exist, else the `notFound.php` template is rendered |
| `?task=Y` | `default` | `{pagePath}/X/controllers/YController.php`, class `YController`; falls back to the base `SEController` if absent |
| `?action=Z` | `default` | method `actionZ()` on the controller |

The request flow: the controller's `beforeAction()` hook runs, then `actionZ()` — whose echoed output is captured as the page's view — then `afterAction()`. Finally the site template is rendered; when it calls `mainBody()`, the page class echoes the captured view into the layout.

The built-in `actionDefault()` simply renders the view named after the page (`views/{page}.php`).

`page`, `task` and `action` come straight from the query string and end up in
file paths and class names, so each must match `[A-Za-z0-9_-]+`; anything else
is rejected rather than escaped. An unknown page renders the 404 template, while
an unknown action simply produces no view output.

## Controllers

```php
<?php
// pages/posts/controllers/PostController.php

class PostController extends \app\core\SEController
{
    public function actionList()
    {
        $posts = $this->db->getAll("SELECT * FROM #__posts ORDER BY created_at DESC");

        $this->render('list', ['posts' => $posts]);   // pages/posts/views/list.php
    }

    public function actionApi()
    {
        $this->jsonResponse(['ok' => true]);           // sends JSON and exits
    }
}
```

Reached via `/?page=posts&task=post&action=list`.

- `render($view, $args)` includes `views/{$view}.php`; `$args` entries become local variables in the view. The same view can be rendered repeatedly (partials in loops are fine).
- `partialRender($view, $args)` renders one view and `exit()`s — useful for AJAX fragments.
- `sendResponse($result, $status, $contentType)` / `jsonResponse($result, $status)` send a response and `exit()`. The response format defaults to the controller's `$format` property and can be overridden per-request with `?format=json|xml`. `SEJsonController` is a base class that forces JSON.
- `SNEMLibrary::redirect($url, $msg, $statusCode)` redirects (302 by default) and exits.

## Database

`$snem->db` (also `$this->db` inside controllers) wraps the live ADOdb connection. Anything the wrapper doesn't define is forwarded to the underlying `ADOConnection`, so it can be used as a drop-in connection; `getConn()` returns the raw one.

Write SQL with the `#__` placeholder — it is replaced with the configured `tablePrefix` in every query:

```php
$db = \app\core\SNEMLibrary::app()->db;

$users = $db->getAll("SELECT * FROM #__users WHERE status = ?", ['active']);
$user  = $db->getRow("SELECT * FROM #__users WHERE id = ?", [$id]);
$name  = $db->getOne("SELECT name FROM #__users WHERE id = ?", [$id]);
$ids   = $db->getCol("SELECT id FROM #__users");

$db->execute("UPDATE #__users SET status = ? WHERE id = ?", ['banned', $id]);
$newId = $db->getInsertId();
```

The `get*` methods return `stdClass` objects (`$user->name`) by default; pass `false` as the last argument for raw ADOdb arrays.

Database errors throw `\app\core\SEDbException` instead of returning `false` — a failed connection, any failed query (`execute()`, `get*`, forwarded ADOdb calls, Active Record `save()`/`find()`). The message carries the driver error and the SQL; `getSql()` and `getOperation()` expose them. An empty result is still `null`/`[]`, not an error. To get ADOdb's old return-`false` behaviour for a block, wrap it in `$saved = $db->IgnoreErrors(); … $db->IgnoreErrors($saved);`.

```php
try {
    $db->execute("UPDATE #__users SET status = ? WHERE id = ?", ['banned', $id]);
} catch (\app\core\SEDbException $e) {
    // $e->getMessage(): "Database EXECUTE failed: ... [errno]\nSQL: UPDATE ..."
}
```

Uncaught, the exception stops the request like any fatal error; with `debugMode` off, `error_reporting(0)` keeps its details off the page.

ADOdb's own messages (e.g. mysqli's `SQL Statement failed on preparation: …`) go to the PHP error log (`error_log()`, prefixed `ADOdb:`) instead of being printed into the page. With `db.debugMode` on — or `$db->debug = true` at runtime — they are printed as before, alongside ADOdb's SQL trace. To handle them yourself, define `ADODB_OUTP` (a function name taking `$msg, $newline`) before SNEM loads.

## Models

### SEModel (plain)

A simple base with magic property accessors (`$m->foo` calls `getFoo()`, `$m->foo = x` calls `setFoo()`), `setAttributes([...])`, and a per-class singleton via `::model()`.

### SEActiveRecordModel (Active Record)

Built on ADOdb's Active Record. Subclasses declare their table, primary keys, and relations:

```php
<?php
// app/models/Post.php

use app\core\SEActiveRecordModel;

class Post extends SEActiveRecordModel
{
    public static function getTableName(): string
    {
        return 'myapp_posts'; // full table name (no #__ here)
    }

    public function relations(): array
    {
        return [
            'author'   => [self::SEAR_RELATION_BELONGS_TO, 'author_id', User::class],
            'comments' => [self::SEAR_RELATION_HAS_MANY, 'post_id', Comment::class],
        ];
    }
}
```

```php
// Reading
$post = Post::model()->find('id = ?', [$id])[0] ?? null;
echo $post->author->name;          // relations resolve lazily
foreach ($post->comments as $c) { /* ... */ }

// Writing
$post = new Post();
$post->title = 'Hello';
$post->save();                     // fills created_at / updated_at automatically
```

Override `beforeSave()` / `afterSave()` for custom hooks; `isNewRecord()` tells inserts from updates.

## Authentication

Subclass the abstract `SEUserIdentity` and implement `authenticate()`:

```php
<?php
// app/models/AppUserIdentity.php

use app\core\SNEMLibrary;
use app\core\models\SEUserIdentity;

class AppUserIdentity extends SEUserIdentity
{
    public function authenticate(): bool
    {
        $hash = SNEMLibrary::app()->db->getOne(
            "SELECT password FROM #__users WHERE username = ?",
            [$this->username]
        );

        return $hash && password_verify($this->password, $hash);
    }
}
```

```php
$snem = SNEMLibrary::app();

$snem->user->login(new AppUserIdentity($username, $password), '/?page=dashboard');

$snem->user->isGuest;              // true when not logged in
$snem->user->checkAccess();        // emits 403 + message when forbidden
$snem->user->logout();
```

Sessions are started automatically by `run()`. Only the username is stored in the session, under the `user_session_name` config key.

## Helpers

`$snem->helper->foo` magically resolves to a `FooHelper` class from your app's helper directory, falling back to the framework's `app\core\helpers\SEFooHelper`. The built-in `SEFileHelper` (`$snem->helper->file`) covers directory creation and file uploads.

## Path aliases

`$snem->getPath('@alias')` resolves `@root`, `@base`, `@page`, `@template`, `@helper`, `@abs`, and `@abs/template` to their configured paths. Strings without `@` pass through unchanged.

## Configuration reference

Your config array (or a PHP file returning one) is merged over these defaults:

| Key | Default | Meaning |
|---|---|---|
| `name` | `''` | Site name; injected into the template's `<title>` |
| `basePath` | `'.'` | App root; other paths derive from it |
| `db` | — | `driver`, `host`, `username`, `password`, `databaseName`, `tablePrefix`, `debugMode` |
| `pagePath` | `{basePath}/pages` | Page directories |
| `templatePath` | `{basePath}/templates` | Templates, plus `notFound.php` / `offline.php` |
| `templateName` | `'default'` | Active template directory |
| `helperPath` | `{basePath}/helpers` | App helpers |
| `rootPath` | `{basePath}` | The `@root` alias |
| `autoloadPaths` | `app/controllers`, `app/models`, `app/helpers` | Directories scanned by the lazy class autoloader (replaced wholesale when overridden) |
| `defaultPage` | `'index'` | Page used when `?page` is absent |
| `debugMode` | `false` | Toggles `error_reporting` |
| `charset` | `'UTF-8'` | Site charset |
| `locale` | — | Passed to `setlocale(LC_TIME, ...)` when set |
| `notFoundMessage` | `'Page not found.'` | Available to your 404 template |
| `forbiddenMessage` | `''` | Message emitted by `checkAccess()` (falls back to a built-in) |
| `maintenanceMode` | `['enabledMode' => false, 'message' => '']` | When enabled, renders `offline.php` and stops |
| `user_session_name` | `'user'` | Session key holding the logged-in username |
| `params` | `[]` | Free-form app params, read via `$snem->params('key')` |

App classes in `autoloadPaths` don't need namespaces — the one contract is that a file defines a single class whose short name matches the filename.
