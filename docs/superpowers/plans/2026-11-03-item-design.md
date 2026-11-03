# Item view design implementation

User requested the item design update after approving viewer-first layout,
metadata grouping, clearer files/tools, multilingual subtitles and research paths.
Execute inline with frontend-design/TDD and a final independent review.

- [x] Add failing tests for metadata partition/preservation, viewer-before-prose,
  image-versus-PDF labels, controls, citation/copy and results-context restoration.
- [x] Add an ItemMetadata utility and item-only metadata view through the real
  `displayValues(viewName)` pipeline, retaining module filters, term order, value
  HTML, annotations, private indicators and locale rules. Unknown fields remain.
- [x] Present title/IJA/type, independent original/transliteration direction and
  anchor navigation, then existing non-video `view.show.after` or video rendering.
  Keep explicit item_media_embed behavior, with media inside the viewer region.
- [x] Add a file description utility: images counted independently of PDFs;
  understandable image numbers plus original filenames; compact PDF links.
  Put full descriptions once in catalogue details rather than duplicating them.
- [x] Add record-link/citation copy with manual fallback, using canonical current-
  site URLs and title/site/IJA only. Do not invent creators, dates or permissions.
- [x] Offer exact-property research links from public, visible literal subjects/
  places/creators. Keep linked resources. Preserve actual browse/search URL context
  in same-site session storage; never manufacture a Back to results destination.
- [x] Add grouped native disclosures/expand-all and shared archive styling. Keep
  existing viewer controls; no second image viewer or module configuration writes.
- [x] Verify PHP/core integration, assets, desktop/mobile keyboard/copy tests,
  screenshots and read-only live item responses; document grouping and limitations.

Verification ledger:
- Metadata/file tests failed before utilities existed; browser tests failed for
  absent expand/copy/result-context interactions, then passed after implementation.
- Reviewer found original-file labels incorrectly used hashed filename(). Updated
  fixtures to model real Omeka source/storage distinctions, reproduced the failure
  and fixed source basenames with URL/Windows path privacy tests. Focused review
  confirmed the fix with no remaining blockers.
- All 30 desktop/mobile browser checks, both PHP suites, Node tests, asset drift,
  syntax and whitespace checks passed. Three local records returned HTTP 200 and
  identical metadata field labels/counts (14, 24, 16). Personal Letters verified
  seven images, one PDF and eight original source names.
- Existing viewer module remains responsible for scan controls and asynchronous
  errors; no database/module settings, release or remote publishing changes made.
