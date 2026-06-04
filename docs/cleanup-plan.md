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

### [ ] 3. Collapse the two log handlers into one base
`src/Logs/InfoLogHandler.php` and `src/Logs/ErrorLogHandler.php` are ~95%
identical: same `register()` shape, same `write()` metadata block, differing
only in channel name (`info`/`error`) and Monolog level.

- Extract an abstract `AbstractLogHandler` carrying `register()`, the
  `getHandlers()`-empty guard (`LogHandlerException`), and the shared `write()`
  metadata assembly.
- Subclasses supply channel + level only.
- Keep the deliberate asymmetry from the logging work: the no-destination
  **throw applies to the info/request handler, not the error handler** (the
  error handler runs inside the error path and must not throw there).

### [ ] 4. De-duplicate env bool coercion
`DotEnv::get()` and `IniEnv::get()` have byte-identical truthy/falsy coercion
and "throw if missing" logic.

- Extract a shared helper (e.g. a trait or small `EnvValue::coerce()`).
- Preserve current loud behaviour: missing key throws.

### [ ] 5. Factor the repeated context reset in `Route::group()`
The 4-field `currentContext` default literal appears three times in
`src/Core/Route.php` (initial property, group-enter reset, group-exit reset).

- Extract a `private static function freshContext(): array` and call it in all
  three spots.

---

## P2 — Global state / consistency

### [ ] 6. Add a state-reset seam to the routing layer
`Route` and `RouteHandler` hold process-global state with no way to reset it.
We hit this directly: the test suite needs `@runTestsInSeparateProcesses`
because the route registry leaks across classes. This also matters for the
advertised **stateful / ReactPHP** mode, where global mutable state risks
cross-request bleed in a long-running process.

- Add `RouteHandler::reset()` and `Route::reset()` (clear `$contextStack`,
  `$currentContext`, `$instance`, registered routes).
- Once available, the test isolation annotations added in
  `NestedRoutesTest` / `MiddlewareInheritanceTest` can be dropped in favour of
  a `reset()` in `setUp()` — *but* note the mocks use `require_once`, so either
  switch them to plain `require` or register routes via a callable the test
  invokes after reset.
- Longer term: consider whether the router can avoid statics entirely for the
  stateful mode.

### [ ] 7. Make `RouteHandler` a consistent singleton
`src/Core/RouteHandler.php` instance methods call `self::getInstance()->routes`
instead of `$this->routes` — a singleton pretending to be an instance.

- Use `$this->` inside instance methods, or commit fully to static. Pick one.

### [ ] 8. Make the fluent `Route` API return consistently
`Route::prefix()` returns `new self()` while `middleware()` / `namespace()` /
`controller()` return `self::$instance` (`src/Core/Route.php`). Subtle, but it's
the kind of inconsistency that produces a confusing bug later.

- Standardise on one (returning the shared `self::$instance` is the safer
  default given the static context model).

### [ ] 9. Unify the exception namespacing
Some exceptions sit in the global namespace (`DotEnvException`,
`IniEnvException`); others are namespaced (`RequestException`,
`LogHandlerException`, `RouteException`). Pick the namespaced convention and
move the global ones under `Rockberpro\…\…`, updating their `use` sites.

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
