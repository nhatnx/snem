# Database internals

Read this before changing `ADODatabase.php`, `SEDbException.php`, or `SEActiveRecordModel.php`. Usage is documented in the README; this explains why the error handling is shaped the way it is.

## What goes through the wrapper

`getAll`/`getRow`/`getCol`/`getOne` (through `getData()`) and `execute` do two things that forwarded calls don't:

- `replacePrefix()` substitutes `#__`, skipping quoted string literals.
- `assertResult()` catches a `false` result.

Anything reaching the connection through `__call`, or through `getConn()`, gets neither. `#__` stays literal, and only failures that invoke `raiseErrorFn` throw.

`get*` converts results to `stdClass` with a JSON round-trip unless `$fetchDTO` is `false`. Empty results (`null`, `[]`) are returned as they are. Fetch mode is `ADODB_FETCH_ASSOC` with native case.

## How errors become exceptions

| Mechanism | Covers |
|---|---|
| `raiseErrorFn` set to `ADODatabase::raiseError` | Connect failures, driver errors, Active Record, forwarded calls |
| `assertResult()` | ADOdb paths that return `false` without invoking `raiseErrorFn`, such as a placeholder count mismatch |
| `SEDbException` default message | Those same paths: the driver reported nothing, so the message points at the placeholder count |

`raiseErrorFn` is set on the connection, before `connect()`. That is why a failed connection throws and why Active Record is covered.

`IgnoreErrors()` clears `raiseErrorFn`. `assertResult()` and `genID()` both check it, so a suppressed block keeps ADOdb's return-`false` behaviour. Any new check must do the same.

## Stale error messages

`fnExecute` is set to `clearError()`, which resets `_errorMsg` and `_errorCode` before each query. Drivers cache the last error, and mysqli's prepare-failure and placeholder-mismatch paths don't refresh it, so without the reset a failure would report an earlier query's message.

`LogSQL()` takes over `fnExecute`. Errors still throw while it is on, but those paths can report a stale message.

## `genID()`

Drivers create a missing sequence by letting the first query fail, which would otherwise throw. The wrapper suppresses errors for the call with `IgnoreErrors()`, restores them in `finally`, then throws `SEDbException` itself if no ID came back.

## ADOdb's echoed output

`ADODatabase.php` defines `ADODB_OUTP` as `ADODatabase::outp` unless the app already did, so ADOdb's messages go to `error_log()` with an `ADOdb:` prefix.

They are echoed only while the static `$echoOutput` is on. It follows the connection's debug flag through the constructor's `debugMode` and through `$db->debug = …` via `__set`. Setting `getConn()->debug` bypasses `__set`, so the SQL trace goes to the log instead of the page.
