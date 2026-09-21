# DBAL dependency transition

Rain must retain `doctrine/dbal: ^2.13.3|^3.1.4` until consumers that rely on
its transitive dependency have a supported upgrade path. Laravel's native
schema operations do not establish compatibility for installed plugins.

## Confirmed consumer

At Builder commit `72e3589e0c46d04b91d22e007e2449913d7ff292` (the upstream
head checked on 2026-09-21), `Plugin.php` registers a Doctrine timestamp type
in `register()`. Its database tools also construct Doctrine schema objects.
Builder's `composer.json` requires Rain but does not require DBAL. Removing
Rain's requirement can therefore break registration after dependency updates.

## Companion patch for Builder

Apply this to Builder's `composer.json` before publishing a compatible release:

```diff
     "require": {
         "php": "^8.0.2",
         "october/rain": ">=3.0",
+        "doctrine/dbal": "^2.13.3|^3.1.4",
         "composer/installers": "~1.0|~2.0"
     },
```

This transfers ownership using Rain's existing constraint; it does not certify
DBAL 4 compatibility or change the supported DBAL majors.

## Release gates

1. Builder maintainers apply the direct dependency and test registration,
   table creation, schema inspection, and migrations on their supported CMS,
   PHP, and database versions. Publish a release containing the dependency.
2. Verify that Composer and marketplace installation/update paths install that
   dependency. Record the first compatible Builder version after publication.
3. For a future Rain removal release, protect upgrades from older Builder
   versions with a verified Composer conflict against versions before that
   release (where package metadata is available), plus a documented minimum
   Builder version and update-first path for marketplace/manual installations.
   A new Builder release alone does not protect existing older installations.
4. Audit other consumers before removing the Rain requirement. Test an upgrade
   from an older Builder installation as well as a fresh compatible installation.

No release number or conflict range is invented here. Until these gates are
satisfied, Rain keeps DBAL and its current develop PHP `^8.2` and larajax `^3.0`
requirements. The removal is deferred.

## Validation and limits

Isolated PHP 8.4 smoke tests with DBAL 2.13.9 and 3.10.6 loaded Builder's actual
`classes/doctrine/TimestampType.php`, executed its `Type::hasType`/`addType`
registration, obtained the registered type, checked MySQL and SQLite timestamp
SQL declarations, and constructed a Doctrine table with a timestamp column.
Both passed. Composer validates the retained Rain manifest (with the existing
missing-license warning).

These checks establish the narrow registration path for those two versions.
They do not cover a full Builder boot, all schema tools, all versions admitted
by the constraint, or marketplace dependency delivery. Library/CMS tests alone
cannot establish Builder compatibility. Companion publication and upgrade
validation remain maintainer release work.
