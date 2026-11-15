# AGENTS.md

Rain (`october/rain`) is the core library of October CMS. It extends Laravel with October's database layer, file-based models, routing, asset pipeline and extension system, and supplies the base classes the CMS modules build on. The CMS itself lives in octobercms/october-private; on 5.x most backend widgets live in larajax/ui.

## Stack

- PHP and Laravel. `laravel/framework` is a dev dependency here; the CMS supplies it at runtime. Check `composer.json` for all versions.
- Key dependencies: `larajax/larajax` (AJAX framework), Twig, Doctrine DBAL, Intervention Image, scssphp, less.php, Symfony YAML and HTML Sanitizer, League CSV, `composer/composer` (installer and updates).
- Tooling: PHPUnit, phpbench.

## Structure

```
src/            October\Rain\*
  Database/     Model, Builder, relations, attachments, traits (Sortable, SoftDelete, ...), migrations, schema
  Halcyon/      file-based models and datasources for theme templates
  Extension/    Extendable and behaviors, the mechanism plugins use to extend classes
  Foundation/   Application, bootstrapping, core service providers
  Support/      Str, Arr, Collection, facades, ModuleServiceProvider
  Router/       the CMS page router and route compiler
  Assetic/      LESS, SCSS and JS combining and filters
  Parse/        Twig, YAML, INI, Markdown and component parsing
  Auth/         auth manager and base user, role and group models
  Installer/    october:install, october:build and setup helpers
  Scaffold/     create:* generator commands
  Html/ Mail/ Translation/ Validation/ Config/ Filesystem/ Resize/ Events/ Log/ Network/ Flash/ Exception/ Composer/
contracts/      October\Contracts\*
globals/        global facade aliases (Db, Str, Yaml, ...) loaded as a classmap
init/           helper functions and polyfills, loaded on every boot
tests/          mirrors src/; fixtures in tests/fixtures, benchmarks in tests/Benchmark
```

Assetic, Config, Database, Extension, Filesystem, Halcyon, Html, Router, Support and Translation each have a `README.md`; read it before changing that package.

`contracts/`, `src/Exception`, `src/Extension` and `src/Html` are also published as standalone `octoberrain/*` packages by `.github/workflows/split.yml`. Each has its own `composer.json`, so a dependency their code starts using goes there as well as in the root one.

## Branches

- `develop` is the 5.x line. `4.x` is the stable line and is merged forward into `develop`.
- A fix to code that also exists on 4.x targets `4.x`. Compare first: `git diff 4.x develop -- <path>`.
- A 4.x change may only call framework APIs that exist at the lower bound of the `laravel/framework` constraint in the CMS's 4.x `composer.json`. A change that needs a newer API targets `develop`.

## Commands

- `vendor/bin/phpunit ./tests` is the suite CI runs (PHP matrix in `.github/workflows/tests.yml`). `composer test` stops at the first failure.
- `composer bench` runs the phpbench benchmarks in `tests/Benchmark`.
- `phpcs.xml` holds the PSR-2 ruleset, but phpcs is not a dev dependency here: run a global `phpcs`, or the CMS checkout's `vendor/bin/phpcs`, from this repository's root.

## Tests

- Tests extend the plain PHPUnit `TestCase` in `tests/TestCase.php`; no Laravel application boots. Database tests build an Eloquent `Capsule` on in-memory SQLite in `setUp()` (see `tests/Database/SoftDeleteStateTest.php`).
- `require-dev` allows several PHPUnit majors, so use assertions that exist in all of them, or the shims in `TestCase`.
- To run the CMS suite against a library change, link this checkout into the CMS through a Composer path repository and leave that `composer.json` change uncommitted. See [docs/runbooks/test-against-cms.md](docs/runbooks/test-against-cms.md).

## Code

- PSR-2 with the exceptions in `phpcs.xml`.
- Every class, method and property has a docblock whose first word is its own name: `normalizeEol converts line breaks to a standard \r\n pattern`, `@var array implement behaviors for this model.`. Method bodies hold code only; the reasoning behind a change goes in the PR description.
- The CMS and third-party plugins use Rain's public classes and members directly. Keep existing classes and signatures working and mark replaced ones `@deprecated`.

## Docs

- Documentation lives in `docs/`; `docs/AGENTS.md` gives its structure and writing rules.
- Plans, specs, evidence, prototypes and scratch work are never committed and go in `playground/`.

## Security

This repository is public. Vulnerabilities go through October's private security policy (linked from the CMS's `CONTRIBUTING.md`), and exploit details stay out of issues, PRs and commit messages.
