# Changelog

Dates describe recorded development baselines. Check tags/releases and the private
deployment record to establish publication or installation status.

## Unreleased

### Fixed

- Replaced the unverified hero letter with page 5 of Personal Letters (IJA 3339),
  linking both image and verified caption to item 16426 in the current site.

- Honored hidden, collapsed and expanded annotation modes with native keyboard
  disclosure controls that avoid Bootstrap's `.collapse` class conflict.
- Omitted metadata property rows when locale filtering removes all their values.
- Escaped plain titles, custom item text, navigation attributes and breadcrumbs.
- Removed duplicate search-input attributes and nested breadcrumb navigation.

- Kept page content beside the navigation when a migrated Media Embed gallery
  would otherwise force the Bootstrap content column onto a second row.
- Restored Omeka's core page-block stylesheet in the custom page template so
  migrated Media Embed alignment and block-layout classes receive their styles.

### Changed

- Reworked item pages around the existing viewer, grouped catalogue metadata,
  independent multilingual subtitles and a unified files/citation area.
- Added image/PDF labels with original source filenames, copy/manual-fallback
  tools, exact-property research links and actual same-site results navigation.
- Removed repeated item descriptions while retaining all fields, annotations,
  locale/module filtering and linked resources through core rendering.

- Reorganized the recognized Recovery of Artifacts content into narrative stages,
  retaining its prose, all photographs and captions, with original-block fallback.
- Added larger uncropped exhibit images, an accessible keyboard image viewer,
  responsive chapter navigation and destination-named previous/next links.
- Moved recovery documents after the story and linked verified PDF item records.

- Implemented the approved archive homepage with a split search/letter introduction,
  public site-scoped collection highlights and concise story/background sections.
- Added homepage settings, including a switch to restore the original saved blocks.
- Extended the archive design to header/footer, browse/search images, record cards,
  content navigation and item details; added a missing-image fallback.

- Refined the responsive homepage hero, reading widths, type scale, browse toolbar
  and item metadata layout while retaining the IJA palette and type families.
- Added visible sort labels, collection-first search summaries and useful empty
  states, independently directed multilingual titles and clearer PDF actions.
- Added homepage/footer discovery links and direct-file/preview fallback controls.
- Replaced Google Fonts requests with reproducibly built local fonts and licenses.

- Stack page navigation on phones, keep compact browse thumbnails visible and
  scale item title icons to the viewport.
- Use named dropdown buttons with unique relationships, meaningful logo text
  and a keyboard-accessible skip link.

- Replaced Font Awesome font classes and the remote kit with locally generated SVG
  icons. Preserved accessible control labels, record-type choices and attribution.
- Added SVG sprite/license generation to the asset build and reproducibility checks.

### Added

- Desktop/mobile Chromium regression checks in CI with screenshots and failure
  traces, using actual templates and synthetic records without a database.

- Maintenance guide, disposable local setup, template override inventory,
  synthetic record manifest/test vocabulary, screenshot protocol and release checklist.

## 2.0.1 — 2026-11-02

### Changed

- Aligned theme, package and lockfile versions at 2.0.1.
- Replaced Gulp/BrowserSync with npm build, watch and vendor-copy commands.
- Added clean-build asset verification and pinned Omeka S 4.2.1 integration checks
  to the PHP 8.1/8.5 CI matrix.
- Refreshed generated assets to Bootstrap 5.3.8 and Popper 2.11.8; removed unused
  vendor distributions. Migrated theme SCSS to modules and isolated Bootstrap imports.
- Retired unused Header Image/Browse Layout controls and removed the legacy item
  template. Existing saved values for those controls are ignored.

### Fixed

- Omeka 4 sorting/Laminas compatibility, nullable PDF media handling and repeated rendering.
- Subnavigation visibility, browse-preview components and property-label settings fallback.
- Duplicate video rendering when embedding is enabled.
- Locale filtering for card titles/descriptions, module hooks, page body classes
  and invalid browse-description markup.

### Validation

- Local PHP 8.5.12 checks against Omeka S 4.2.1, plus PHP syntax and Node asset tests.
- PHP 8.1 execution is configured in CI; full-site browser/database validation
  was not completed during the recorded local maintenance work.
