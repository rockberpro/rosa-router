# Cleanup Plan

A prioritized, low-risk refactor pass. None of these change the framework's
public *behaviour* (except where explicitly noted); they're about consistency,
de-duplication, and reducing global mutable state. Ordered so the
correctness-affecting items come first and the cheap wins are easy to pick off.

Status legend: `[ ]` todo · `[~]` in progress · `[x]` done

---

## P0 — Correctness / contradictions

### [x] 1. Reconcile the supported PHP version — DONE
Floor settled on **8.0+**: `composer.json` now requires `">=8.0"` (platform
`8.4`) and the README says "PHP 8.0 or higher". The PHP 8 syntax in the code
(`mixed`, `string|array` unions) is therefore valid. Nothing further needed.

### [x] 2. Finish the `RestRouter` → `RosaRouter` rename — DONE
Whole codebase is now `Rockberpro\RosaRouter\*` — `composer.json` PSR-4, all
`namespace`/`use`, the hand-rolled `autoload.php` fallback, docs, and the test
mocks. `composer dump-autoload` regenerated; suite green.

---

## P1 — De-duplication

### [x] 3. Collapse the two log handlers into one base — DONE
`AbstractLogHandler` now carries `register()`, the `getHandlers()`-empty guard,
and the shared `write()` metadata assembly. `InfoLogHandler` / `ErrorLogHandler`
are thin subclasses supplying only `channel()`, `level()`, and
`throwOnNoDestination()`. The deliberate asymmetry is preserved via that last
hook: info throws on no destination, error does not. `register()` uses
`static::class` / `new static()` so each subclass binds under its own container
key.

### [x] 4. De-duplicate env bool coercion — DONE
Extracted `Utils\EnvValue::coerce()`, called by both `DotEnv::get()` and
`IniEnv::get()` after their `getenv()` lookup. The "throw if missing" logic
stays in each class so the distinct exception types (`DotEnvException` /
`IniEnvException`) are preserved — loud behaviour unchanged.

### [x] 5. Factor the repeated context reset in `Route::group()` — DONE
The 4-field default now lives in a single `private const DEFAULT_CONTEXT`,
referenced by the property default and both group-enter / group-exit resets. A
const rather than a `freshContext()` method because PHP property defaults can't
call a method but can reference a constant expression — one source of truth for
all three spots.

---

## P2 — Global state / consistency

### [x] 6. Add a state-reset seam to the routing layer — DONE
`RouteHandler::reset()` drops the singleton (and with it the route registry);
`Route::reset()` clears `$contextStack` / `$currentContext`, nulls the shared
`$instance`, and delegates to `RouteHandler::reset()`. `$instance` was retyped
`?self` (was non-nullable, can't be `unset()`); the existing `isset()` guards
already treat null as "not set", so no call sites changed.

`NestedRoutesTest` / `MiddlewareInheritanceTest` dropped their
`@runTestsInSeparateProcesses` / `@preserveGlobalState disabled` annotations in
favour of `Route::reset()` in `setUp()`. The mock `api.php` /
`middleware_merge_api.php` requires switched `require_once` → `require` so the
route registration re-runs after each reset (the `TestMiddleware.php` /
`vendor/autoload.php` requires stay `require_once` — class definitions). Suite
green in-process (67 tests).

Longer term: consider whether the router can avoid statics entirely for the
stateful mode.

### [x] 7. Make `RouteHandler` a consistent singleton — DONE
Instance methods now use `$this->routes` directly instead of routing back
through `self::getInstance()->routes`. `addRoute()` appends straight to
`$this->routes`; the now-redundant `setRoutes()` indirection was dropped.
`getInstance()` / `reset()` stay static (the singleton accessors); the call
sites in `Route.php` already go through `RouteHandler::getInstance()`, so
nothing else changed.

### [x] 8. Make the fluent `Route` API return consistently — DONE
`Route::prefix()` now returns the shared `self::$instance` like `middleware()` /
`namespace()` / `controller()`, instead of a throwaway `new self()`. One return
convention across the fluent methods, matching the static context model.

### [x] 9. Unify the exception namespacing — DONE
`DotEnvException` / `IniEnvException` moved into `Rockberpro\RosaRouter\Utils`,
so they're now PSR-4 autoloadable like the other namespaced exceptions. Their
`?Throwable` constructor hints became `?\Throwable` (no longer in the global
namespace); the stale `use DotEnvException;` / `use IniEnvException;` imports in
`DotEnv` / `IniEnv` were removed (same namespace now). `EnvCoercionTest` dropped
its manual `require_once` of the two files in favour of `use` imports — the
autoloader resolves them. Suite green (67 tests).

---

## P3 — Test coverage

### [ ] 10. Broaden core dispatch tests
Current coverage is thin (route nesting, middleware inheritance, the log-handler
guard). The matching/dispatch core deserves more:

- URL param extraction (`/user/{id}` → params).
- Method mismatch / no-match behaviour.
- The full middleware pipeline *executing* (order, short-circuit via early
  `Response`), not just route-table assembly.
- Auth middleware (JWT + KEY) happy/again paths.

---

## Suggested sequencing
1. **P0 #1 + #2** first — they're correctness/consistency contradictions and
   touch many files, so do them before building more on top.
2. **P1 #3–#5** — pure de-duplication, fully behaviour-preserving, quick wins.
3. **P2 #6** — the highest-value structural change; unblocks cleaner tests and
   safer stateful mode.
4. **P2 #7–#9** then **P3 #10** as polish.

Each item is independently shippable as its own small PR/commit.
