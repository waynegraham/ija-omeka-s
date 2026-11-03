# Exhibit layout and image viewer

The Recovery of Artifacts page (`recovery-artifacts`) has a presentation layer for
its existing migrated blocks. It shows one H1/introduction above the columns,
four narrative stages with selected photographs, a complete photograph gallery,
and related documents at the end. Saved Omeka blocks and the database are untouched.

## Settings and editorial changes

- **Use the recovery exhibit story layout**: enabled by default. Disable to render
  the saved blocks in their original layout.
- **Use uncropped exhibit images**: enabled by default. Page media blocks use
  large image derivatives instead of square thumbnails. Disable to respect their
  configured thumbnail sizes. Image loading still depends on the Omeka files.

`src/RecoveryExhibit.php` recognizes only the reviewed source structure: one page
heading, one related-materials block, four narrative blocks and three media blocks
in the original order. The narrative openings are also checked. All source text,
photographs, captions and links must be accounted for; unsupported content falls
back to the original blocks. This avoids silently hiding new editorial material.

If editing that page changes its structure or narrative openings, review/update
the preparer and its fixtures, or leave the story-layout setting disabled. Normal
text/emphasis edits within the recognized narratives remain in the new layout.
Do not remove the fallback to make arbitrary new blocks fit the old mapping.

Selected story photos (item IDs):

| Stage | Item IDs |
| --- | --- |
| Discovery | 288, 286 |
| Emergency recovery and stabilization | 298, 312 |
| Assessment and transport | 318, 332 |
| Drying | 336, 338 |

Only photographs already present in the saved content can appear in these stages.
Every source photograph, including ones outside that selection, remains in the
complete gallery. No item/API requests or copied production images are required.

The existing related PDFs link through verified records on the current site:
2003 Memorandum of Agreement (269) and Preservation Report (271). Unrecognized
resource URLs retain their original destinations and PDF labels where applicable.

## Shared navigation and viewer

Content pages with a sidebar get a native chapter disclosure. JavaScript closes
it on phones and opens it on desktop; without JavaScript the list stays available.
Sticky positioning is enabled only when the sidebar fits the desktop viewport.
Previous/next links use the destination page titles and preserve Omeka's ordering.

`common/block-layout/file.phtml` retains the core attachment, media renderer,
link type, title and caption contracts while selecting larger derivatives.
`asset/js/exhibit.js` enhances image links in exhibit media blocks and curated
figures. Native `<dialog>` provides modal focus containment; next/previous buttons,
arrow keys, Escape and explicit Close work without leaving the page. Closing
returns focus to the originating image button. Record links remain outside the
viewer, including when thumbnails fail. Without dialog/JavaScript support, the
original image links still open their item records.

The viewer uses large derivatives rather than original full-resolution scans to
avoid unnecessary downloads. Loading/error messages and a record link remain
available when a larger derivative cannot load. Existing viewer modules on item
pages are unaffected.

## Maintenance and checks

- Edit exhibit styles in `_exhibits.scss`, included after the shared archive rules.
- Keep `common/exhibit/viewer` queued once in the layout; its strings are translated
  server-side. Avoid a second lightbox library or global handlers on item viewers.
- Run standalone PHP, real-Omeka integration, Node/asset and browser tests from the
  README. Browser fixtures cover responsive chapters, the complete gallery,
  keyboard navigation, modal focus restoration and failed-image record links.
- Test real exhibit images after file synchronization, at phone widths and 200%
  zoom. Unknown-layout fallback is a deliberate safeguard, not a rendering error.
- Deploy `src/RecoveryExhibit.php`, the new partials, `asset/js/exhibit.js` and
  rebuilt CSS together; update the release asset version and clear caches.
