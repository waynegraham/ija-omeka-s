# Content and deployment follow-up for the visual refresh

The theme supplies responsive hero styles and an action bar on the `home` page,
reading widths, search summaries, footer discovery links and multilingual title
isolation. The following changes belong to Omeka content or module configuration.

## Homepage HTML blocks

Edit the Home page in the Omeka admin, retaining its existing media and attribution:

- Shorten the repeated discovery/history paragraphs. Keep one concise introduction
  and let each subsequent section introduce its own subject.
- Change the preservation section's “View Exhibit” link text to
  **Explore the preservation project**. Use the internal site page link rather
  than an absolute production-domain URL on beta.
- Change the Jewish Life section's “View Exhibit” text to
  **Explore Jewish life in Iraq**. Preserve its intended external exhibit URL.
- Review the heading's “Mukharabat” versus the body's “Mukhabarat” transliteration
  with the content owner before changing historical wording.
- Correct the embedded video's `widht` attribute to `width` (the existing ratio
  wrapper determines responsive sizing).

The action bar targets the existing `preservation-project` page. If that page's
slug changes, update `view/common/home-actions.phtml`. A homepage with a different
slug needs its detection updated in `view/omeka/site/page/show.phtml`.

## Footer and search

Use the existing **Footer Content** theme setting to add approved About, Contact,
Rights and Reuse, Accessibility and institutional attribution text/links. The
new Home/Browse/Advanced search links appear separately and preserve that setting.
No institutional/legal statements or destinations are invented by the theme.

True language/date/record-type facets require evaluating and configuring a search
module and its index. The theme now improves summaries and empty states using
core search; it does not implement a separate search backend.

## Preview provider

The item template retains `view.show.after`. When a provider renders no markup,
it explains that no inline preview is available; original file links remain
available when the media exposes an original URL. Providers that render their
own shell must supply loading/error behavior for asynchronous failures. Validate
real PDF, image, video and IIIF records on the deployed installation.

## Release verification

- Increment the theme version and matching package/lockfile version before release
  so Omeka's versioned asset URLs invalidate cached CSS/fonts. Rebuild and deploy
  `asset/fonts/`, CSS and templates together. Clear any proxy/CDN caches.
- Run PHP, asset and browser checks from the README. Review screenshots with the
  local fonts loaded. Latin and Latin Extended subsets are shipped; Hebrew/Arabic
  glyphs use the browser/system fallback fonts and need real-device review.
- Check home, browse, search and representative Hebrew/Arabic records at phone
  widths and 200% zoom. Confirm hero contrast with the actual background image.
- Verify footer destinations, beta links, direct files and the real viewer.
