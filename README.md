# Iraqi Jewish Archive theme

Version 2.0.1 targets **Omeka S 4.x** and **PHP 8.1–8.5**. This is an
Omeka S theme; it is not for Omeka Classic. Compiled CSS and JavaScript are
included, so no asset rebuild is needed for this compatibility upgrade.

Returning to this project? Start with [MAINTENANCE.md](MAINTENANCE.md) for the
architecture, setup, sample records, override inventory and release procedures.
See [CHANGELOG.md](CHANGELOG.md) for recorded changes.

The upgrade replaces legacy Zend form classes with Laminas, uses Omeka 4's
`sortConfig` contract and Browse helper, and reads configured advanced-search
sort options from Omeka. The IJA browse sorts are preserved, including a corrected
`dcterms:date` option. PDF downloads handle media without filenames or thumbnails,
and item templates can render repeatedly without declaring global functions.

The custom IJA item display and its `view.show.after` preview integration remain.
The item display uses its fixed IJA layout rather than the configurable resource
page blocks introduced in Omeka 4. Core templates continue to handle media display
and other views the theme does not override.

Page subnavigation honors the site's display setting, browse previews honor their
selected components, and property-label information uses site settings before
global settings. Video items render their media once even when embedding is enabled.

## Upgrade

1. Back up the installation and database, and try the upgrade on staging first.
2. Upgrade Omeka S and installed modules to releases whose dependencies support
   the PHP version you will deploy. The theme cannot change core or module PHP
   requirements; its PHP 8.5 checks do not certify the whole installation.
3. Replace this theme's files in `themes/ija-omeka-s`, preserving the directory name
   so existing site assignments and saved theme settings continue to apply.
4. Confirm version 2.0.1 appears under the site's Themes settings. Open and save
   the theme settings, checking the logo, footer and description truncation.
5. Check public navigation, site pages, full-text search, advanced property search,
   sorting, pagination, item-set browsing, and linked resources. Test items with
   PDFs, video, URL/embed media, no media and no record type. Confirm any preview
   module still renders correctly and review PHP logs with `E_ALL` enabled.

The unused Header Image and Browse Layout controls have been retired. The theme
continues to use its existing navigation header and two-column browse cards.
Previously saved values for these controls are ignored; no database cleanup is
required. Browse and preview card titles/descriptions honor locale filtering.

## Asset development

Compiled CSS, fonts, icons and vendor JavaScript are committed, so running the theme and editing
PHP templates do not require Node.js or an asset build. To change the SCSS or
refresh vendor assets, install Node.js 20.19 or newer, then run:

```sh
npm ci
npm run build:css  # Compile SCSS to compressed CSS and apply Autoprefixer
npm run build:fonts # Copy local font subsets, generated font CSS and licenses
npm run build:icons # Generate the local Font Awesome SVG sprite and license
npm run watch      # Build CSS and rebuild when SCSS files change
npm run vendor     # Copy installed Bootstrap and Popper JavaScript into asset/js/vendor/bootstrap
npm run build      # Build CSS, vendor JavaScript, SVG icons and fonts
npm run check:assets # Verify committed assets match a clean build without changing them
npm test           # Test Sass compilation and asset-drift detection
```

The watch command also watches SCSS partials. It reports compilation errors and
keeps watching so you can correct them. Refresh the browser manually after edits;
there is no development proxy or automatic browser reload. Commit regenerated
assets alongside their source changes. Review vendor diffs when updating packages.
Only the Bootstrap and Popper minified scripts loaded by the layout and their
source maps are shipped. Use `npm ci` to reproduce the versions in the lockfile.
When removing or renaming an output, delete the obsolete generated file as well;
the asset check rejects extra files in the generated CSS/vendor/icon/font directories.

Icons use a local SVG sprite generated from the pinned Font Awesome Free package.
There is no remote Font Awesome kit, icon font or runtime icon-replacement script.
Render icons through `common/icon` (or `common/record-type-icon` for item types).
SVGs inherit text color and are decorative; keep an accessible name on their
surrounding control or indicator. The generated sprite retains attribution and
ships with `asset/icons/LICENSE.txt`. Update the curated icon list in
`scripts/assets.js`, then rebuild when adding an icon.

The existing Merriweather and Source Sans Pro families are self-hosted from pinned
Fontsource packages. `npm run build:fonts` copies Latin/Latin Extended WOFF2 files,
used regular/bold/italic weights and their licenses into `asset/fonts/`. The layout
loads generated `fonts.css` before the theme styles, using `font-display: swap`.
Deploy the entire fonts directory with the CSS. Hebrew/Arabic glyphs currently
use system fallbacks; keep real-device multilingual checks in the release process.

Theme-owned SCSS uses `@use` and the Sass color module. Bootstrap's internal legacy
imports remain isolated behind `custom-bootstrap.scss`; dependency deprecations
are quieted while warnings in theme-owned styles remain visible. The main theme
stylesheet is tested for deprecations. Coordinate any future Bootstrap Sass
migration with its upstream module support.

## Validation

Run the standalone template regressions with warnings and deprecations treated
as failures:

```sh
php tests/compatibility.php
# Or: composer test
find src view tests -type f \( -name '*.php' -o -name '*.phtml' \) -print0 | xargs -0 -n 1 php -l
```

CI runs syntax, standalone and integration checks on PHP 8.1 and 8.5. Integration
tests use the official Omeka S 4.2.1 release archive with a pinned SHA-256 checksum;
its bundled dependencies require no Composer resolution or database. CI also
runs the Node asset tests and checks committed assets against a clean build.
To run integration checks against an installed Omeka S locally:

```sh
php tests/omeka-integration.php /path/to/omeka-s
```

The integration checks exercise the real Omeka Browse and hidden-input helpers,
Laminas form rendering and navigation, settings fallback, theme configuration and
version constraints without a database. Rendering regressions cover subnavigation,
browse-preview components, locale filtering, property-label settings and module
hooks. They were run locally with Omeka S 4.2.1 on PHP 8.5.12. A complete
browser/database smoke test is still required for the deployed installation.

Browser regression checks run in Chromium at 1440 × 1000 and 390 × 844:

```sh
npm ci
npx playwright install chromium
OMEKA_PATH=/path/to/omeka-s npm run test:browser
npx playwright show-report
```

The suite renders the actual theme templates with Omeka/Laminas helpers and
synthetic records, then serves them on loopback port 8766. `OMEKA_PATH` must point
to an installed release with `vendor/autoload.php`; it defaults to the enclosing
Omeka installation. No database, DDEV or public website is required. The fixture
generator first runs the PHP integration regressions. CI downloads the same
checksum-pinned release used by those tests and installs Chromium automatically.

Checks cover desktop/sidebar and phone/stacked layouts, core Media Embed sizing,
visible browse thumbnails, title escaping, annotation keyboard toggling, dropdown
keyboard controls, the skip link, search submission and advanced-search row
addition/removal. The suite also checks homepage hero bounds, unclipped sort text, search summaries,
empty states and Hebrew/Arabic title direction. JavaScript exceptions and HTTP errors fail tests. Layout
screenshots are attached to the HTML report on successful runs too; failures also
retain traces. CI uploads the report for 14 days. Screenshots are review artifacts,
not pixel-diff baselines. These synthetic checks do not validate database content,
IIIF/viewer modules, real search results, other browser engines or deployment caches;
keep using the [release checks](docs/releases.md) for those.

The standalone manual fixture at
`/themes/ija-omeka-s/tests/fixtures/sidebar-layout.html` remains available on a
local Omeka host. It should report **PASS: navigation and content use the expected
responsive layout**.

When upgrading Omeka, compare overridden templates with the matching files in
the core version, update the pinned CI archive/checksum, and run both PHP suites.
Keep changes focused on the IJA layout and leave core helpers responsible for
sorting, navigation and settings fallback. Preserve `view.show.before` and
`view.browse.before`/`view.browse.after` when editing templates. The item
`view.show.after` event intentionally remains in the non-video preview area for
the existing IJA preview module; moving it can change where that module renders.

Default templates live in `application/view/common` and `application/view/omeka/site`.
See [theme development documentation](https://omeka.org/s/docs/developer/themes/theme_modifications/)
and [Omeka 4 changes](https://omeka.org/s/docs/developer/whatsnew/).

See the [content and deployment checklist](docs/design-content-checklist.md) for
homepage prose/link changes, institutional footer links, search facets and viewer
configuration that must be completed in Omeka or its modules.

## Archive homepage and shared design

The `home` page now uses the approved split introduction/search and page 5 of Personal Letters (IJA 3339), live
collection highlights, stories and background. Configure its heading, intro,
optional uploaded image/caption/record link and up to four featured IDs in theme settings.
Disable **Use the archive homepage design** to restore the saved Home page blocks;
those blocks are never edited or deleted. Highlights are public and scoped to the
current site, and missing records are omitted. See the [configuration and content
guide](docs/design-content-checklist.md) for defaults and publication checks.

The shared archive styles live in `asset/scss/_archive-design.scss`. Its mixin is
included after the legacy theme rules. Core search, sorting, pagination, locale,
annotations and preview hooks remain in use. `asset/js/theme.js` supplies the
unavailable-image state when migrated thumbnails cannot be loaded. Sync the actual
Omeka files to restore those images; the theme does not substitute production URLs.

## Exhibits

The Recovery of Artifacts page now presents its existing text and photographs in
four narrative stages, followed by the full gallery and related PDF records.
Theme settings can restore the original blocks or their configured thumbnail
sizes. Exhibit pages also gain responsive chapter navigation, named previous/next
links and an accessible image viewer with persistent collection-record links.
See the [exhibit guide](docs/exhibits.md) for supported content structure, settings,
photo mapping, fallbacks and release checks. No saved page content is changed.
