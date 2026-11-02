# Iraqi Jewish Archive theme

Version 2.0.1 targets **Omeka S 4.x** and **PHP 8.1–8.5**. This is an
Omeka S theme; it is not for Omeka Classic. Compiled CSS and JavaScript are
included, so no asset rebuild is needed for this compatibility upgrade.

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
   the theme settings, checking the logo, header, footer and browse options.
5. Check public navigation, site pages, full-text search, advanced property search,
   sorting, pagination, item-set browsing, and linked resources. Test items with
   PDFs, video, URL/embed media, no media and no record type. Confirm any preview
   module still renders correctly and review PHP logs with `E_ALL` enabled.

## Asset development

Compiled CSS and vendor JavaScript are committed, so running the theme and editing
PHP templates do not require Node.js or an asset build. To change the SCSS or
refresh vendor assets, install Node.js 20.19 or newer, then run:

```sh
npm ci
npm run build:css  # Compile SCSS to compressed CSS and apply Autoprefixer
npm run watch      # Build CSS and rebuild when SCSS files change
npm run vendor     # Copy installed Bootstrap and Popper JavaScript into asset/js/vendor/bootstrap
npm run build      # Build CSS and copy vendor JavaScript
```

The watch command also watches SCSS partials. It reports compilation errors and
keeps watching so you can correct them. Refresh the browser manually after edits;
there is no development proxy or automatic browser reload. Commit regenerated
assets alongside their source changes. Review vendor diffs when updating packages.

## Validation

Run the standalone template regressions with warnings and deprecations treated
as failures:

```sh
php tests/compatibility.php
# Or: composer test
find view tests -type f \( -name '*.php' -o -name '*.phtml' \) -print0 | xargs -0 -n 1 php -l
```

The CI workflow runs these checks on PHP 8.1 and 8.5. For additional checks
against an installed Omeka S with Composer dependencies, run:

```sh
php tests/omeka-integration.php /path/to/omeka-s
```

The integration checks exercise the real Omeka Browse and hidden-input helpers,
Laminas form rendering and navigation, settings fallback, theme configuration and
version constraints without a database. Rendering regressions cover subnavigation,
browse-preview components and property-label settings. They were run locally with
Omeka S 4.2.1 on PHP 8.5.12. A complete
browser/database smoke test is still required for the deployed installation.

Default templates live in `application/view/common` and `application/view/omeka/site`.
See [theme development documentation](https://omeka.org/s/docs/developer/themes/theme_modifications/)
and [Omeka 4 changes](https://omeka.org/s/docs/developer/whatsnew/).
