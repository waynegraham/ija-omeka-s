# Cooler archive palette implementation plan

**Goal:** Apply the cooler visual pass approved in conversation on November 3, 2026.

**Spec:** Mist canvas `#EDF2F5`, soft-white reading areas `#FAFCFD`, blue-gray navigation/supporting sections `#DFE8EE`, slate photograph surrounds `#E3E9ED`, and existing navy `#002B5C`. Preserve typography, content and responsive layout.

**Architecture:** Keep palette tokens and shared surfaces in `_archive-design.scss`; use those tokens in `_exhibits.scss`. Rebuild the committed CSS using the existing Sass pipeline.

**Tech stack:** Sass, Bootstrap, existing Playwright fixture suite.

## Task 1: Apply and verify the palette

- [x] Separate canvas, reading, panel and image surface tokens; apply them to shared page surfaces.
- [x] Tint exhibit introductions, supporting documents and the image dialog using the shared tokens.
- [x] Run `npm run build:css`, `npm test`, `npm run check:assets`, and `npm run test:browser`.
- [x] Inspect desktop and mobile screenshots of the homepage, Recovery exhibit and catalogue.

## Review focus

Check mobile overflow after panel padding, contrast of navy links and body text on tinted surfaces, contained photograph rendering, homepage hero contrast and image-dialog controls. This reversible visual change uses the existing layout and interaction checks rather than adding tests that duplicate color declarations.

Validation: CSS build, both asset tests, asset drift check and all 34 desktop/mobile browser tests passed. Reviewed synthetic homepage, Recovery exhibit and catalogue screenshots at both viewport sizes; no overflow or clipping observed. Browser tests required local-server sandbox escalation.
