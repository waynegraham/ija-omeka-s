# Archive design: configuration and content

The approved homepage design now runs on the page with slug `home`. It uses a
split introduction/search and archival letter, up to four collection highlights,
two story introductions and a short background section. The shared design extends
to navigation, catalogue cards, search, content sidebars, item details and footer.

## Configure the homepage

Open the site's theme settings. Existing installations use the defaults until
these settings are saved:

| Setting | Behavior |
| --- | --- |
| Use the archive homepage design | Enabled by default. Disable to render the original saved Home page blocks. The theme does not edit/delete those blocks or modify the database. |
| Homepage heading / introduction | Plain text; HTML is escaped. |
| Homepage featured item IDs | Up to four comma-separated IDs in display order. Defaults: `17159,1022,284,269`, the records used in the reviewed concept. Clear to omit highlights. |
| Homepage image | Optional uploaded Omeka asset; otherwise uses page 5 of Personal Letters (IJA 3339), linked to item 16426. |
| Uploaded homepage image caption and alternative text | Describe an uploaded image accurately. The built-in image has fixed, verified record/page attribution. |
| Uploaded homepage image item ID | Optional record link for an uploaded image. The built-in image always links to item 16426 on the current site. |

Highlights come from one public, site-scoped API query. Deleted, private,
unavailable or unassigned records are omitted; no unrelated records are substituted.
Titles and types come from the catalogue, respecting the locale filter. Original
collection images stay in Omeka's file storage; missing files show an accessible
fallback rather than a broken-image graphic. Sync the files to restore photographs.

The new introduction is in `view/common/home.phtml` and the highlights renderer in
`view/common/home-highlights.phtml`. Story/background copy is theme-owned in this
mode. The preservation route assumes the `preservation-project` slug; the Jewish
Life exhibit points to `https://exhibit.ijarchive.org/`. Update these if destinations
change. A homepage with a different slug needs its detection adjusted in
`view/omeka/site/page/show.phtml`.

## Stored Home page blocks

These remain intact and can be restored with the homepage setting. If maintaining
that alternative layout, shorten repeated history, use descriptive exhibit links,
correct the iframe's `widht` typo and review “Mukharabat” versus “Mukhabarat” with
the content owner. Editing those saved blocks affects the alternative layout,
not the new theme-owned homepage.

## Footer, search and preview provider

The existing Footer Content setting is preserved below the brand and discovery
links. Add approved Contact, Rights and Reuse, Accessibility and institutional
attribution there; the theme does not invent institutional/legal statements.

True language/date/record-type facets still require a search module and index.
Core search routes, summaries, query propagation and empty states are preserved.

Item previews retain `view.show.after`. If no provider emits markup, an inline
preview explanation appears. Original file links remain available when the media
exposes a URL. Providers that render an asynchronous shell still own their loading
and error states. Test real PDF, image, video and IIIF records after deployment.

## Release verification

- Increment the theme version and matching package/lockfile versions before release
  to invalidate cached asset URLs. Deploy templates, `asset/js/theme.js`, both new
  story images, CSS and the existing `asset/fonts/` directory together. Clear
  proxy/CDN caches and force-reload during local review.
- Run the README's PHP, asset and browser checks. Inspect home, browse, search,
  content navigation and item pages at phone widths and 200% zoom.
- Test representative Hebrew/Arabic records and the active viewer on real devices.
  Latin/Latin Extended fonts are shipped; other scripts use system fallbacks.
- Verify all homepage IDs, image descriptions, links, institutional footer content,
  direct files and image permissions/attribution before publication.

## Hero image provenance

`asset/images/personal-letters-3339-page-5.jpg` is an unchanged copy of the large
image for media **16453**, source filename **03339_E.jpg**: the fifth image in
item **16426**, **Personal Letters**, IJA **3339**. Verified against the public
item/media API and visually inspected on 2026-11-03.

- Record: https://ijarchive.org/s/iraqi-jewish-archive/item/16426
- Source: https://ijarchive.org/files/large/db6314925d1344a203f6d9561937e9cd11064661.jpg

The image and caption both use the local site's item route, so local/beta links
stay in their own environment. No request to production is needed at page load.
The previous generic letter caption is ignored for this built-in image, including
on installations that saved the old default. Uploaded images retain their own
caption and only get a record link when an uploaded-image item ID is configured.

## Shipped story image provenance

Reused unchanged from existing public IJA content for the approved design:

- `asset/images/boating-on-tigris.jpg`: existing homepage photograph labelled
  “Boating on the Tigris”, `https://ijarchive.org/files/medium/09085a22169470ed0812c1ada608afc006bff06a.jpg`.
- `asset/images/materials-awaiting-preservation.jpg`: public item 284,
  `https://ijarchive.org/files/medium/1cd70dc0c7df1a2dcfd470598c9f083439f907b9.jpg`.

These static thumbnails illustrate the stories; featured collection thumbnails
continue to use the live catalogue. No original collection files were copied or
changed by this theme update.
