# WCAG 2.1 AA theme review — November 3, 2026

Reviewed the theme templates, shared styles and interaction scripts against the
[WCAG 2.1 A and AA criteria](https://www.w3.org/TR/WCAG21/), using rendered local
Omeka fixtures. This is a theme review, not a conformance certification for the
deployed site.

## Changes

| Finding | Criteria | Resolution |
| --- | --- | --- |
| AND/OR query selector had no accessible name, including cloned rows | 1.3.1, 4.1.2 | Added the translated name “Combine with previous query.” |
| Pale-blue keyboard outline had approximately 2.1:1 contrast against the reading surface | 1.4.11, 2.4.7 | Added a navy outline with a white separating ring for visibility on pale and dark surfaces; included textareas and custom focus targets. |
| Default form boundaries lacked 3:1 contrast | 1.4.11 | Added slate control borders, with at least 3.4:1 contrast against the shared pale surfaces. |
| Footer hover text darkened on the navy footer | 1.4.3 | Kept hovered/focused footer links white. |
| Legacy homepage overflowed at 320 CSS pixels | 1.4.10 | Removed negative row margins inside the homepage's unpadded container. |
| Core advanced-search rows stayed on one line at narrow widths | 1.4.10, 1.4.12 | Allowed query and item-set rows to wrap and controls to shrink within the viewport. |
| Search row operations did not place focus in a useful location | 2.4.3 | Focus the new property's selector after adding; focus a neighboring property's selector after removing. Core still handles insertion/removal and query indexing. |
| Pagination included an empty disabled link and an unnamed landmark | 2.4.4, 4.1.2 | Removed the empty link and named the pagination landmark. |
| Full-text input had adjacent attributes without whitespace | 4.1.1 | Corrected the markup. |

## Evidence

- Ran axe-core 4.14.0 with `wcag2a`, `wcag2aa`, `wcag21a` and `wcag21aa`
  tags on 12 fixture pages at desktop and mobile widths: designed and legacy
  homepages, content page, browse, advanced search, populated and empty search
  results, item, Arabic and RTL records, Recovery exhibit and generic exhibit.
  Also scanned the open Recovery image dialog at both widths. Final scans
  reported zero automatic violations; this does not cover every criterion.
- Added 32 desktop/mobile regression cases in
  `tests/browser/accessibility.spec.js`. Together with the existing 34 browser
  cases, all 66 pass. Checks cover accessible query names, contrast, 320-pixel
  reflow with prescribed text spacing, 200% root text sizing, query focus,
  modal focus containment and focus restoration. Text-sizing tests check
  overflow, not every possible instance of clipped text.
- Existing tests exercise keyboard menus, skip links, disclosures, image-dialog
  controls, copy fallback and status messages. Native dialog focus may enter
  browser chrome; tests reject focus entering underlying page controls.
- Corrected the advanced-search fixture to load Omeka's actual search CSS,
  which exposed the nowrap behavior absent from the earlier fixture.
- Reviewed the 320-pixel advanced-search screenshot with added text spacing.
- Asset tests, asset drift check, PHP compatibility/integration suites, changed
  PHP template syntax checks and `git diff --check` pass.
- axe could not automatically resolve every contrast case: native select
  options, metadata over the decorative background and legacy hero text over
  photography remain manual-review areas. Palette calculations give body text
  at least 9.4:1 and muted text at least 5.0:1 on the shared pale surfaces;
  these calculations do not establish contrast over arbitrary images.

## Checks still needed on the deployed site

- Review real image alternatives and captions for equivalent meaning (1.1.1),
  and identify language changes in actual multilingual content (3.1.2).
- Verify real videos/audio have the required captions, transcripts and audio
  descriptions (1.2.1–1.2.5), as applicable. Check PDFs and scanned documents
  for accessible reading order, text alternatives and usable text.
- Exercise the actual IIIF/viewer modules, media players, authentication and
  any module-provided forms using keyboard and screen reader. Fixtures contain
  a viewer placeholder rather than the production provider.
- Check VoiceOver/Safari and NVDA/Firefox or Chrome reading order, landmarks,
  announcements, expanded states and modal behavior. No screen-reader session
  was performed in this pass.
- Inspect real content at 200% text size and 400% browser zoom, with increased
  text spacing, including long headings, translated controls and open menus.
  The automated viewport and root-font checks approximate only part of this.
- Check hover/focus dropdown dismissal, validation errors, native select text
  and legacy image-backed hero contrast in the deployed browser/content mix.

Run `npm run test:browser` to repeat the committed regression tests. The one-off
axe audit used a temporary installation, leaving project dependencies unchanged.
