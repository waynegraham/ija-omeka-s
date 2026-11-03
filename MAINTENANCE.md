# Maintaining the IJA theme

Last checked: 2026-11-02, against Omeka S 4.2.1 and PHP 8.5.12.

Start here after a long break. This repository is an Omeka S theme, deployed as
`themes/ija-omeka-s` inside an independent Omeka installation. The database,
uploaded files, vocabularies, module settings and enabled-module state live outside
this repository. Keeping this folder alone is not a complete site backup.

## Find the right instructions

| Task | Guide |
| --- | --- |
| Build a disposable development site | [Local setup](docs/local-setup.md) |
| Recreate representative records | [Sample records](docs/sample-records.md) |
| Understand a template override | [Override inventory](docs/overrides.md) |
| Compare the visual appearance | [Screenshot protocol](docs/screenshots/README.md) |
| Find production/beta pages for a comparison audit | [Discovered URL pairs](docs/audit/discovery.md) |
| Prepare, deploy or roll back a release | [Release checklist](docs/releases.md) |
| Understand previous changes | [Changelog](CHANGELOG.md) |

## The decisions that matter

- **The item page is a fixed IJA layout.** It does not use Omeka's configurable
  resource page blocks. Metadata comes from `displayValues()`; linked resources
  come from `displaySubjectValues()` with an options array.
- **The preview hook is deliberately in the middle of the item page.**
  `view.show.after` runs inside the non-video Preview area. Moving it changes
  where listeners render. Video items render their media directly and skip this
  hook. `view.show.before` runs for every item; browse pages preserve both browse
  hooks. A module expecting the usual end-of-page hook placement needs review.
- **Record types are literal values.** `ija:record_type` selects the icon and the
  exact value `Video` selects video rendering. Recognized icon values are Books,
  Fragments, Archival Materials, Video, Other, Documentation and Photograph.
  Missing/unrecognized values use a generic file icon. `ija:ija_number` supplies
  the displayed IJA number. The production vocabulary namespace is not stored here.
- **Settings belong to different layers.** Theme controls are logo, footer and
  description truncation. Site settings control subnavigation, locale filtering,
  preview components and media embedding; property labels use site/global fallback.
  Header Image and Browse Layout were retired because they had no effect.
- **Generated assets are shipped.** Edit SCSS, then build and commit CSS. Vendor
  scripts come from the npm lockfile. Bootstrap's legacy Sass stays inside its
  dependency boundary; theme styles use modules. Do not hand-edit generated files.
- **Annotations use native disclosure controls.** Site setting
  `show_value_annotations` supports hidden, `collapsed` and `expanded`. Keep
  `.value-annotation` independent of Omeka's legacy expand/collapse handlers and
  Bootstrap's `.collapse` styles. Render metadata through `asHtml()` as before;
  titles and custom item text are plain text.
- **Responsive layout is intentional.** Navigation sits beside content at 768px
  and wider and stacks below that breakpoint. Keep `min-width: 0` on the content
  flex item for core media galleries. Browser regressions and screenshots are
  available through `npm run test:browser`; see the README for setup and limits.
- **Icons are self-hosted SVGs.** `common/icon` references a curated symbol in
  `asset/icons/fontawesome.svg`; `common/record-type-icon` maps record types to those
  symbols. `npm run build:icons` regenerates the sprite and license from the pinned
  Font Awesome Free package. Keep its attribution and accessible control labels.

## Preview dependencies: what is actually known

The inspected local installation contains Universal Viewer, Mirador and IIIF
Server. Both viewer modules attach to `view.show.after` for legacy theme layouts,
using `universalviewer_placement` or `mirador_placement` with `after/items`.
Installed files do not prove either viewer is enabled or used in production.
Check the Modules screen and site settings before replacing either integration.
Follow each module's own dependency and viewer-library installation instructions.

## Before changing anything

Record the current Git commit, PHP/Omeka/module versions and deployment settings.
Run the checks in the README, reproduce the problem with a sample record, and add
a regression for changed behavior. After an Omeka upgrade, compare the overrides
with the matching upstream templates; passing syntax checks alone is insufficient.

If a preview is blank, check the viewer's enabled state, placement, IIIF manifest
and browser network errors. If a theme change is invisible, confirm the site's
selected theme, deployed directory and generated CSS before editing more code.

## Deployment worksheet

Keep this current in an appropriate private operations record; do not add secrets.

- Public/staging base URLs and responsible maintainer: **not recorded here**.
- Hosting/service startup and backup restore procedure: **not recorded here**.
- Active viewer, its version, required modules and placement: **verify on site**.
- Production IJA vocabulary namespace and source/export location: **verify on site**.
- Actual PHP/Omeka versions and last successful browser check: **record per release**.
