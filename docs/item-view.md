# Item view and research tools

The item page now presents title/IJA/type and a multilingual subtitle, then the
existing viewer, files/citation and grouped catalogue metadata. Long descriptions
appear once in the catalogue section rather than above the viewer and again below.

## Viewer contract

`view.show.before` remains first; non-video items retain `view.show.after` in the
viewer region. Videos render once. Explicit `item_media_embed` behavior remains,
with those embeds now in the viewer region rather than below catalogue details.
The theme styles the outer viewer container; it does not replace Mirador/Universal
Viewer, configure their thumbnail rails, intercept scan controls or create a second
item image viewer. Scan navigation and asynchronous loading/error behavior remain
owned by the selected module. Check the actual provider and files on deployment.

`src/ItemPreview.php` checks hook output for potential preview content without
changing that output. Scripts, styles, comments and hidden metadata alone do not
suppress the missing-preview explanation. Empty visible containers count as
potential viewer mounts; this check does not establish successful module loading.
Shared styles load for screen and print so print disclosure rules can apply.

## Catalogue grouping

The item calls `displayValues(['viewName' => 'common/item-metadata'])`, so Omeka's
resource-template ordering and `rep.resource.display_values` module filters run
before grouping. `src/ItemMetadata.php` partitions each visible property once:

- Description and identification: descriptive fields and all unknown terms.
- Publication and language: publisher, author, publication/calendar dates and language.
- Physical characteristics: dimensions, extent, material and format.
- Preservation and treatment: mold/dirty, mending, rehousing and treatment fields.
- Access and reuse: only actual catalogue rights/license/access-rights properties.

The first, second and rights groups start open; physical/treatment groups start
closed. Empty or locale-filtered groups are omitted. Each group uses the existing
`common/resource-values` renderer, preserving value HTML, locale labels, private
indicators, annotation modes, alternate labels and per-value hooks. The grouping
does not infer permissions, convert calendars or merge conflicting date fields.

Expand/collapse controls affect only the top-level metadata sections. Native
summary controls work without JavaScript. `?metadata=all#ija_metadata` opens every
section server-side, and printing temporarily opens them before restoring the
reader's disclosure state. Adjust the term mapping for new vocabularies; unknown
fields must remain in the default group rather than disappear.

## Files and citation

`src/ItemFiles.php` separates image media from PDFs and other formats. Image
numbers follow media order, excluding PDFs; these are image positions, not claims
about printed page numbers. File links retain original source names. Omeka's
`filename()` is a storage hash: use the basename of `source()` for display, stripping
URL queries/directory paths, then fall back to display title and stored filename.
Media with no original URL do not produce empty file links.

The basic suggested citation uses the item title, actual site title, IJA number
when present and the current site's canonical record URL. It does not invent an
author, publication year, persistent identifier or rights statement. It is a record
citation, not a replacement for a scholarly citation to the work itself.

Copy buttons use the Clipboard API after a click; failure reveals/selects a
read-only field for manual copying. Permalinks/citation text remain available
without JavaScript. No clipboard contents are read and no data is transmitted.

## Continuing research

Public, visible subject/place/creator values supply exact-property browse links,
up to six unique values. They are clearly labelled search paths, not automatically
asserted relationships or fabricated related-record counts. Existing linked
resources remain below the catalogue. No extra related-record API query is made.

`asset/js/item.js` stores only the real browse/search URL, current site scope and
clicked record path in session storage. Back to results appears only for that
record on the same origin/site; filters, sort and pagination remain in the URL.
Direct visits and cross-site/untrusted contexts do not get a fabricated back link.
When session storage is unavailable, ordinary navigation continues to work.

## Verification and release

Run the PHP/core integration, Node/asset and browser checks in the README. Browser
fixtures cover viewer-first layout, disclosures, image/PDF labels, copy success
and manual fallback, same-site results context and Hebrew/Arabic titles. Real local
checks confirmed the same field labels/counts for items 16426, 17159 and 1022.

Deploy the new PHP utilities/partials, `asset/js/item.js` and generated CSS together.
The main stylesheet URL includes a content hash; other assets use the theme
version. Update matching release versions and clear stale HTML/proxy/CDN caches
as needed. Check real image files and the
active viewer on phones and desktop; synthetic tests cannot validate its IIIF
service or permission to reuse particular scans.
