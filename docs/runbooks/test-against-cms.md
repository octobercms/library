# Test against the CMS

How to run the October CMS test suite against a local, uncommitted Rain change. The CMS loads this checkout through a Composer path repository, symlinked into its `vendor/october/rain`.

1. Clone the CMS next to this repository, so both share a parent folder. The steps below assume this repository is in `../library` relative to the CMS.

2. In the CMS's `composer.json`, add a path repository:

   ```json
   "repositories": [
       {
           "type": "path",
           "url": "../library",
           "options": {
               "symlink": true,
               "versions": { "october/rain": "dev-develop" }
           }
       }
   ]
   ```

   `versions` must satisfy the CMS's `october/rain` constraint (`dev-develop` on `develop`), so a feature branch of this repository still matches.

3. In the CMS, install the linked copy:

   ```
   composer update october/rain
   ```

   Verify: `ls -l vendor/october/rain` shows a symlink to this repository.

4. Run the CMS suite:

   ```
   vendor/bin/phpunit
   ```

   Verify: the failures match a run on the same CMS commit without the link.

5. When done, revert the CMS:

   ```
   git checkout composer.json
   composer update october/rain
   ```

   Verify: `git status` in the CMS is clean and `vendor/october/rain` is no longer a symlink.
