# Release checklist

Current release candidate: 2.1.0. A version in a file does not prove a
release was tagged, published or deployed; check repository tags and deployment
records. This document does not perform any release actions automatically.

## Prepare a candidate

- [ ] Review `git status` and preserve unrelated local changes.
- [ ] Choose the next version. Use a patch for compatible fixes, a minor for
  compatible additions and a major for intentionally incompatible behavior.
- [ ] Update `package.json` and both root-package versions in `package-lock.json`.
  `npm version NEXT_VERSION --no-git-tag-version` can update these files together.
- [ ] Update `config/theme.ini` and the current-version references in `README.md`.
  Keep historical changelog entries and upstream test-baseline versions intact.
- [ ] Move completed changes from Unreleased into a dated `CHANGELOG.md` entry.
  Record settings retirement, dependency changes and operator actions explicitly.
- [ ] If changing Omeka support, review [all overrides](overrides.md), Composer and
  theme version constraints, the CI PHP matrix, and the pinned archive/checksum.

## Verify

From the theme root, with the intended Node/PHP versions and a matching Omeka path:

```sh
npm ci
npm run build
npm test
npm run check:assets
composer test
php tests/omeka-integration.php /absolute/path/to/omeka-s
OMEKA_PATH=/absolute/path/to/omeka-s npm run test:browser
find view tests -type f \( -name '*.php' -o -name '*.phtml' \) -print0 | xargs -0 -n 1 php -l
git diff --check
```

- [ ] Review and commit regenerated CSS/vendor scripts with their source/lockfile.
  Remove obsolete generated outputs; the build does not automatically delete them.
- [ ] Confirm theme, package, lockfile and README current-version values agree.
- [ ] Run the [sample-record checks](sample-records.md) and capture/review
  [desktop/mobile screenshots](screenshots/README.md).
- [ ] Verify the real preview provider, media URLs, browser console and PHP logs.
- [ ] Review the [accessibility findings and remaining live-site checks](accessibility-review-2026-11-03.md),
  including screen readers, media alternatives and PDFs.
- [ ] Check advanced search at 200% text size and multi-image exhibit frames at
  desktop/mobile widths; inspect images after lazy loading completes.
- [ ] Record actual tested PHP, Omeka and module versions. Passing theme tests
  does not certify core/module compatibility or the database-backed application.
- [ ] Commit the reviewed candidate and verify its CI checks pass.

## Package and deploy

Package a clean, committed candidate. For example, substituting the selected version:

```sh
THEME_VERSION=2.1.0
git archive --format=zip --prefix=ija-omeka-s/ --output="/tmp/ija-omeka-s-${THEME_VERSION}.zip" HEAD
unzip -l "/tmp/ija-omeka-s-${THEME_VERSION}.zip"
```

`git archive` packages committed content only. Confirm the archive has exactly one
top-level `ija-omeka-s/` directory, includes compiled assets, and contains no
credentials or local dependency directories. The example version is not a release
declaration. Create the matching tag/release through your normal repository process.

- [ ] Back up the database, uploaded files, configuration, modules and old theme.
- [ ] Install and test the candidate on staging before production.
- [ ] Replace the theme directory, preserving its name and saved site assignment.
  Remove obsolete old files through the deployment process rather than overlaying
  the archive indefinitely.
- [ ] Confirm version 2.1.0 and settings, then repeat critical browser checks.
- [ ] Confirm `main.css` has its content-based `rev` query parameter. Clear cached
  HTML so clients receive the new stylesheet URL; other assets still use Omeka's
  theme-version query. Purge stale proxy/CDN entries when needed.
- [ ] Record deployed commit/tag, date, operator, versions and backup location in the
  private operations record. Preserve the previous release artifact.

## Roll back

For a theme-only regression, restore the previous theme directory/artifact with
the same directory name, then verify the site. If core, modules or database
migrations also changed, follow the coordinated backup restore procedure; copying
old theme files does not undo those changes. Record the failure and add a fixture
or regression before attempting another release.
