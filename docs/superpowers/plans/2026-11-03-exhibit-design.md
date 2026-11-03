# Exhibit design implementation

User approved the seven recommendations for Recovery of Artifacts and exhibits.
Execute inline with frontend-design, TDD and a final independent review.

- [x] Test named pagination, lossless recovery content preparation, fallback,
  responsive chapters and keyboard image-viewer behavior before implementation.
- [x] Add a pure PHP recovery layout preparer operating on rendered saved blocks.
  Recognize only the current title/resources/four narrative blocks/three media
  blocks. Keep original prose, captions and item links. Unknown structures fall
  back unchanged; never write the database or discard additional content.
- [x] Show one H1/introduction above the columns for the curated recovery layout.
  Group photographs by discovery, stabilization, assessment/transport and drying.
  Use selected photos alongside each original paragraph; keep all source photos
  in an expanded full gallery and the original documents in an end resource area.
- [x] Prefer uncropped larger thumbnails, retain captions/record links and add
  progressive accessible enlargement via a native dialog. Support keyboard next,
  previous, Escape, loading/error states and focus restoration; no external library.
- [x] Add native chapter disclosure on phones and sticky sidebar only when it fits
  the desktop viewport. Keep navigation usable without JavaScript.
- [x] Replace generic previous/next with destination titles and rel attributes.
  Label document links PDF and use known item records where verified.
- [x] Add recovery-layout opt-out, document source/curatorial mapping, run PHP,
  asset/browser tests and local HTTP smoke checks, inspect screenshots and review.

Keep navy/serif/archive-image visual language; do not turn every paragraph into a
card or invent dates. No auto-playing media, database edits, release or deployment.

Verification ledger:
- Pagination and recovery-preservation tests failed first, then passed with named
  links and the guarded saved-content adapter. Four exhibit browser assertions
  failed before styles/viewer logic and passed after implementation.
- Review reproduced unsupported resource notes being discarded and a generic
  image-only record link being replaced by a disabled button on image failure.
  Added failing tests and fixed consumed-source validation plus persistent links.
  Independent focused review confirmed both fixes with no remaining blockers.
- PHP suites, native-dialog keyboard/focus/error tests and all 24 desktop/mobile
  browser checks passed. Source paragraphs and complete gallery remain available.
- Local HTTP check confirmed the real recovery page uses four narrative stages
  with no PHP error markers. Source files must still exist in Omeka storage.
- Saved content/database, release version and remote sites were not modified.
