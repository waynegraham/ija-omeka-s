# Disposable local setup

This reproduces the theme's Omeka 4.2.1 integration baseline. It does not reproduce
the production database or prove production modules support your PHP version.
Use a separate database and files directory for development.

## Get a known Omeka release

From a workspace outside this theme, download the same bundled release as CI:

```sh
mkdir ija-dev
cd ija-dev
curl --fail --location https://github.com/omeka/omeka-s/releases/download/v4.2.1/omeka-s-4.2.1.zip -o omeka.zip
echo 'cc27d1c7aca0209523d19aa285f4a08e29e34950dcc446951a7c1311de348e82  omeka.zip' | shasum -a 256 --check
unzip -q omeka.zip
```

The release includes its PHP dependencies. No Omeka core asset build is needed.
On Linux, `sha256sum --check` can replace `shasum -a 256 --check`.

## Configure the web stack

Use Apache/PHP with rewrite support and a dedicated MySQL/MariaDB database.
Check the [official installation requirements](https://omeka.org/s/docs/user-manual/install/)
for server configuration and extensions; the release also requires `fileinfo`.
Use PHP 8.5 to reproduce the local theme checks, then verify any added modules
independently. The CI matrix includes PHP 8.1 and 8.5.

1. Create a database and a development-only user with access to that database.
2. Enter those credentials in `omeka-s/config/database.ini` locally.
3. Point your local Apache document root at `omeka-s`, enable `mod_rewrite` and
   permit its `.htaccess` rules with `AllowOverride All`. Make `files/` writable by
   the web-server user. Configure a supported thumbnail generator if testing images.
4. Start your stack using its service manager. Open `/admin` on the configured
   local host and finish the installer. This repository does not provide a web
   server, database launcher or production credentials.

Do not commit `database.ini`, API keys, database dumps or copied production media.

## Add the theme and a site

Place a checkout of this repository at `omeka-s/themes/ija-omeka-s`. Alternatively,
symlink an existing checkout to that path if your local Apache configuration permits
it. Avoid an extra nested theme directory.

In Omeka admin, create a site titled **IJA Theme Test**, with slug `ija-test`.
Select **IJA Theme** in its Themes screen and save. Configure a test logo, footer
and description truncation. Public routes are relative to your installation root:

| Screen | Route |
| --- | --- |
| Site | `/s/ija-test` |
| Browse | `/s/ija-test/item` |
| Advanced search | `/s/ija-test/item/search` |
| Item | `/s/ija-test/item/ACTUAL_ITEM_ID` |
| Content page | `/s/ija-test/page/PAGE_SLUG` |

Add records from [the sample-record guide](sample-records.md) to the site's item
pool. Create a parent page and a child page, put them in navigation, and add a
browse-preview block. Core-only rendering needs no viewer module. To test Preview,
install and enable the deployment's chosen viewer and its dependencies, configure
its `after/items` placement, and verify its media/IIIF URLs. Do not enable both
viewers by accident and mistake duplicate previews for theme output.

## Build and verify

From the theme directory, use Node.js 20.19+ and run:

```sh
npm ci
npm test
npm run check:assets
composer test
php tests/omeka-integration.php /absolute/path/to/ija-dev/omeka-s
```

`composer test` only launches the standalone PHP suite; it does not install Omeka.
After SCSS/dependency edits, run `npm run build`, review the generated changes and
repeat the asset check. `npm run watch` rebuilds SCSS; refresh the browser manually.
Use [the screenshot protocol](screenshots/README.md) for visual checks.
