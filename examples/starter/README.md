# SNEM Starter

A small but complete application on the [SNEM](../../README.md) micro-framework.
Every framework feature appears exactly once, in the place you would really use
it, so the app doubles as a template to copy and a tour to read.

## Run it

1. **Create the database** (MySQL/MariaDB). The schema also seeds two accounts
   and four posts:

   ```sh
   mysql -u root -p < schema.sql
   ```

   No `mysql` client on your PATH? Any GUI client works — just execute
   `schema.sql` as-is.

2. **Check the credentials** in `config.php` under `db`. They default to
   `root` with an empty password on `127.0.0.1`.

3. **Install dependencies**, if you copied this directory outside the SNEM
   repo. Inside the repo it already falls back to the framework's own
   `vendor/`, so you can skip this:

   ```sh
   composer install
   ```

4. **Serve it:**

   ```sh
   php -S localhost:8000
   ```

   Then open <http://localhost:8000/>. Sign in with **`demo` / `demo1234`**.

## The tour

| URL | Shows |
|---|---|
| `?page=home` | The default page: a page class and its view, no controller needed |
| `?page=posts` | A controller action, `#__` prefixed SQL with bound parameters, a view with arguments, a partial repeated per row, and a helper |
| `?page=posts&action=view&id=1` | A second action on the same controller, reading `?id` through `SEInput` |
| `?page=posts&action=api` | `jsonResponse()` — sends JSON and exits, skipping the layout |
| `?page=login` | A form posting to a **named task** (`task=auth`) |
| `?page=admin` | `beforeAction()` guarding a page with `checkAccess()`, plus Active Record reads, a lazy relation, and a validated write |
| `?page=nope` | The 404 template |

## Files worth reading, in order

```text
index.php                                front controller — four lines
config.php                               every option the app overrides
templates/default/default.php            the layout; calls $snem->mainBody()
pages/home/home.php                      the smallest possible page
pages/posts/controllers/DefaultController.php   the controller worth copying
pages/posts/views/list.php               rendering a partial in a loop
app/models/Post.php                      Active Record: table, relations, hooks
app/models/AppUserIdentity.php           the one method authentication needs
app/helpers/TextHelper.php               $snem->helper->text
pages/admin/controllers/DefaultController.php   access control and writes
```

## Turning it into your app

- Rename the pages under `pages/` and point `defaultPage` at the one you want.
- Replace `schema.sql`, then edit `app/models/` to match your tables. Active
  Record models name the **real** table (`st_posts`); raw SQL through
  `$snem->db` uses the `#__` placeholder instead.
- Delete the `$autoload` fallback at the top of `index.php` and keep the plain
  `require __DIR__ . '/vendor/autoload.php'`.
- Set `debugMode` to `false` before deploying — it controls `error_reporting`.
- Keep `.htaccess` if you deploy on Apache: the app directories sit under the
  document root, and it is what stops them being fetched directly. Serving only
  a `public/` directory is sturdier still.

## Notes

- `templates/notFound.php` and `templates/offline.php` live beside the template
  directories rather than inside one — the framework looks for them at the root
  of `templatePath`.
- Flip `maintenanceMode.enabledMode` to `true` in `config.php` to see the
  offline page; the app stops before it opens a database connection.
- The seeded password hash is a real bcrypt digest of `demo1234`. Generate your
  own with `password_hash()` rather than reusing it.
