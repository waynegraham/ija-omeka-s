# Production/beta URL discovery

Discovered on 2026-11-02. This is the input set for the comparison audit.

**Verified:** 15 URL pairs, 30 successful HTTP 200 responses, matching page slugs
and independently matched item identities. Both no-result searches display an
empty-results message. No obvious server-error text was found in these responses.

**Not performed:** browser screenshots, JavaScript execution, accessibility scans,
playback/zoom/download interaction, or a complete asset/link crawl. HTTP 200 and
matching metadata do not establish correct browser behavior.

Machine-readable URLs, matching evidence and response metadata are in
[url-pairs.json](url-pairs.json). Raw HTML was used locally during discovery; it is
not included in this repository.

## Selected pairs

| Coverage | Production | Beta |
| --- | --- | --- |
| Home — header search, navigation, content images, embedded video | [Open](https://ijarchive.org/s/iraqi-jewish-archive/page/home) | [Open](https://beta.ijarchive.org/s/iraqi-jewish-archive/page/home) |
| The Preservation Project — parent subnavigation, content blocks | [Open](https://ijarchive.org/s/iraqi-jewish-archive/page/preservation-project) | [Open](https://beta.ijarchive.org/s/iraqi-jewish-archive/page/preservation-project) |
| Recovery of Artifacts — nested navigation, breadcrumbs, content images | [Open](https://ijarchive.org/s/iraqi-jewish-archive/page/recovery-artifacts) | [Open](https://beta.ijarchive.org/s/iraqi-jewish-archive/page/recovery-artifacts) |
| Arabic exhibit introduction — Arabic text, text direction, page blocks | [Open](https://ijarchive.org/s/iraqi-jewish-archive/page/discovery-recovery-ar_iraqi-jewish-archive) | [Open](https://beta.ijarchive.org/s/iraqi-jewish-archive/page/discovery-recovery-ar_iraqi-jewish-archive) |
| Browse, page 1 — cards, record icons, sort controls, result totals | [Open](https://ijarchive.org/s/iraqi-jewish-archive/item) | [Open](https://beta.ijarchive.org/s/iraqi-jewish-archive/item) |
| Browse, page 2 — pagination, query preservation | [Open](https://ijarchive.org/s/iraqi-jewish-archive/item?page=2) | [Open](https://beta.ijarchive.org/s/iraqi-jewish-archive/item?page=2) |
| Advanced search — property queries, add/remove controls, sort options | [Open](https://ijarchive.org/s/iraqi-jewish-archive/item/search) | [Open](https://beta.ijarchive.org/s/iraqi-jewish-archive/item/search) |
| Sitewide search: book — search submission, result groups, result links | [Open](https://ijarchive.org/s/iraqi-jewish-archive/index/search?fulltext_search=book) | [Open](https://beta.ijarchive.org/s/iraqi-jewish-archive/index/search?fulltext_search=book) |
| Sitewide search: no matches — empty-results message | [Open](https://ijarchive.org/s/iraqi-jewish-archive/index/search?fulltext_search=ijaauditnomatch20261102xyz) | [Open](https://beta.ijarchive.org/s/iraqi-jewish-archive/index/search?fulltext_search=ijaauditnomatch20261102xyz) |
| Iraqi Jewish Archive item set — item-set page, collection browsing | [Open](https://ijarchive.org/s/iraqi-jewish-archive/item-set/17227) | [Open](https://beta.ijarchive.org/s/iraqi-jewish-archive/item-set/17227) |
| Hebrew-titled book — Hebrew title, book icon, multi-image preview, metadata | [Open](https://ijarchive.org/s/iraqi-jewish-archive/item/17159) | [Open](https://beta.ijarchive.org/s/iraqi-jewish-archive/item/17159) |
| Archival map — archive icon, image preview, metadata | [Open](https://ijarchive.org/s/iraqi-jewish-archive/item/1022) | [Open](https://beta.ijarchive.org/s/iraqi-jewish-archive/item/1022) |
| YouTube video — video rendering, player count, embedded media | [Open](https://ijarchive.org/s/iraqi-jewish-archive/item/275) | [Open](https://beta.ijarchive.org/s/iraqi-jewish-archive/item/275) |
| Photograph — photograph icon, image preview | [Open](https://ijarchive.org/s/iraqi-jewish-archive/item/284) | [Open](https://beta.ijarchive.org/s/iraqi-jewish-archive/item/284) |
| PDF document without an IJA number — PDF download, thumbnail, fallback record icon | [Open](https://ijarchive.org/s/iraqi-jewish-archive/item/269) | [Open](https://beta.ijarchive.org/s/iraqi-jewish-archive/item/269) |

## Matching decisions

- Content pages were matched by site slug and page slug, including the Arabic
  introduction and nested preservation-project pages.
- The Hebrew-titled book was independently resolved by **IJA number 80** on each
  site; the archival map by **IJA number 238**. Titles also agree.
- The video, photograph and PDF document have no IJA number. Exact titles were
  queried independently and confirmed against record type and media metadata
  (ingester, MIME type, source and stored filename).
- The item set was matched by its exact public title, Iraqi Jewish Archive.
- Numeric IDs happen to agree for these samples. The discovery did not rely on
  equal IDs as evidence of equal content.

## Observations to investigate

1. **Beta links/embeds can use production.** Its homepage links to the production
   preservation-project page and embeds an image from production. The nested
   Recovery of Artifacts page also has production-origin links. The manifest
   lists the exact references per page. Confirm whether these are intentional;
   they can make beta checks depend on the existing site or leave beta during a test.
2. **Search ordering differs.** The `book` search returns **8 site pages and
   2,277 items on both sites**, but visible ordering and sampled results differ.
   Check default sort/relevance behavior and the first two result pages before
   treating this as a regression. Counts matching alone is not proof that result
   sets are identical.
3. **Record the deployed baseline.** Homepage asset URLs currently advertise
   production theme `None` / core `None` and beta theme `None` / core
   `None`. These are observed URL query versions, not an independent
   check of the backend runtime. Recheck after a deployment or cache purge.

## Coverage gaps

- Beta returned no public candidates for `site_id=1&has_media=0`. Production
  returned records that actually have media for the same filter, consistent with
  that older API ignoring it. Do not use those production results as no-media fixtures.
- Missing-thumbnail PDFs and null stored filenames were not established from the
  public samples. Keep the synthetic regressions for these cases.
- An Arabic page and a Hebrew book title cover non-Latin text. They do not prove
  the sites contain language-tagged values suitable for locale-filter interaction tests.

## Use this set for the browser audit

Run both environments with the same browser at 1440 × 1000 and 390 × 844 CSS pixels.
Capture screenshots, console errors and failed network responses for each pair.
Use fresh browser storage first, then repeat visits with normal caching. Test
mobile navigation, search add/remove controls, sort/pagination, viewer controls
and downloads interactively. Record intentional content/design changes alongside
each discrepancy rather than automatically accepting production as correct.
