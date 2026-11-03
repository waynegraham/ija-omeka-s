# Approved archive design implementation

User approved the homepage concept and asked to extend its design across the site.
Execute inline using executing-plans, test-driven-development and one final
requesting-code-review gate. No deployment, commits or database writes requested.

Design: navy #002B5C, white #FFFFFF, image gray #F8F9FA, ink #263B46 and rule
#DCE2E6. Retain locally shipped Merriweather and Source Sans Pro. The archival
letter is the focal point; avoid generic rounded/shadowed cards and decorative
statistics. Align navigation, records and editorial sections to one content grid.

- [x] Add failing homepage integration/browser tests for new hero, search, ordered
  site-scoped highlights, missing records, opt-out and image failure handling.
- [x] Add `common/home` and reusable highlight/image partials. Homepage mode is
  enabled by default on `home`; preserve original content via a checkbox toggle.
  Configure featured record IDs, heading/intro and an optional uploaded hero image.
  Default highlights use the four existing IJA records reviewed in the mockup.
  Query public records through Omeka with site_id, one limited request, preserve
  curator order, omit inaccessible/missing records and allow an empty selection.
- [x] Replace the detached homepage actions/old hero only in the new mode. Preserve
  before/after hooks and legacy page layout in the opt-out mode. Use core routes
  and the existing search_type setting. Do not embed mockup base64 or remote assets.
- [x] Extend the palette, spacing and image treatment through navigation, browse,
  search, content sidebars, item preview/details and footer. Keep current sort,
  pagination, locale, annotation and viewer contracts.
- [x] Ship an accessible image-unavailable state for broken collection files.
  Do not silently copy production files into the local database/storage.
- [x] Build assets, run PHP and browser regressions, inspect screenshots, perform
  read-only local HTTP smoke checks, obtain review and document configuration.

Implementation paths: new `view/common/home.phtml`, `home-highlights.phtml`,
`archive-image.phtml`; new `asset/scss/_archive-design.scss` and
`asset/js/theme.js`; update page/layout/browse/search/item templates and settings.
Keep new Sass in a mixin included after existing rules to avoid specificity wars.
Keep the release version unchanged until the user releases; document cache refresh.

Verification ledger:
- Homepage assertions first failed on absent layout, then on unstyled split hero
  and missing image-error behavior. Added site/public/order/missing-ID, custom
  text/image, empty selection and opt-out coverage using real Omeka helpers.
- All 18 Chromium desktop/mobile checks passed after implementation, including
  functional hero GET search and deliberate failed-image handling.
- Local HTTP smoke check rendered the real split hero and four highlights with
  no duplicate IDs or PHP error markers. Local collection image URLs returning
  404 remain a storage-sync issue, explicitly covered by the new fallback.
- PHP suites, Sass/asset checks, PHP/JS syntax and diff whitespace checks passed.
  Independent review found no important regressions and confirmed API contracts.
- Original blocks are untouched; no publishing, database changes or version bump.
