# Site reading and discovery implementation plan

> Execute inline using superpowers:executing-plans. The user approved the design review recommendations with “make these improvements.”

Design: retain navy #002B5C, white, light gray #F8F9FA and heading gray #405353;
Merriweather headings and Source Sans Pro reading/UI text. Use a restrained type
scale, narrative lines around 70 characters and responsive spacing. Preserve
complete catalogue titles, core search routes and preview module hooks.

- [x] Add failing rendered/browser regressions for homepage actions/hero sizing,
  visible sort labels, search ordering/snippets/empty states, bidirectional text
  and clearly labelled PDF links.
- [x] Refine the shared SCSS, homepage action partial and browse toolbar. Keep
  gallery layouts wide while constraining narrative paragraphs.
- [x] Add richer collection-first search results with locale-aware descriptions;
  provide useful empty-state actions. Preserve query propagation and totals.
- [x] Structure item title/viewer/metadata areas, isolate multilingual text and
  label PDF actions accurately without promising module loading/error signals
  the theme cannot observe.
- [x] Add footer discovery links and active-sidebar styling. Preserve configured
  footer HTML; do not invent contact/rights/accessibility destinations.
- [x] Ship existing font families and used weights locally from pinned Fontsource
  packages, including licenses and reproducible asset checks.
- [x] Build, run PHP/asset/browser suites, inspect screenshots and request one
  final independent review. Document deployment and remaining content/module work.

Scope rulings: database homepage prose/CTA edits, institutional footer destinations
and true search facets require content/module configuration, so provide a concrete
follow-up checklist. No remote database writes. No version bump for unreleased
work; deployment instructions must require an asset-version increment/cache refresh.

Verification ledger:
- Original regression tests failed for missing visible sort labels, misleading PDF
  text and page-first search ordering. New browser checks failed for the old 800px
  hero and clipped sort text.
- Browser checks then exposed 4px homepage overflow from `ps-2`; remove that padding
  only on the home page so breakout sections center on the viewport.
- The first RTL check exposed the English IJA label determining title direction.
  Isolate the primary title separately and test Hebrew plus Arabic mixed with IDs.
- Ship 400 regular/italic and 700 regular for both families, plus 600 UI text;
  the generated subset rules preserve upstream Unicode ranges and licenses.
- Strengthen the hero's black overlay to 60% for white text; even over a white
  source pixel its maximum background luminance corresponds to #666 (contrast
  greater than 5:1). Review the actual image on deployment as well.
- Independent review identified the RTL issue above and no other important PHP or
  asset regressions. Provider-specific async errors remain module-owned.
