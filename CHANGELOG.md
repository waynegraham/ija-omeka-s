# Changelog

Dates describe recorded development baselines. Check tags/releases and the private
deployment record to establish publication or installation status.

## Unreleased

### Fixed

- Kept page content beside the navigation when a migrated Media Embed gallery
  would otherwise force the Bootstrap content column onto a second row.
- Restored Omeka's core page-block stylesheet in the custom page template so
  migrated Media Embed alignment and block-layout classes receive their styles.

### Changed

- Replaced Font Awesome font classes and the remote kit with locally generated SVG
  icons. Preserved accessible control labels, record-type choices and attribution.
- Added SVG sprite/license generation to the asset build and reproducibility checks.

### Added

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
