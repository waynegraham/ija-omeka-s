# Sample records and browser checks

[records.json](fixtures/records.json) contains synthetic metadata and expected
behaviors. It is a manual fixture manifest, not an Omeka API import payload: there
are no database property/site IDs, uploaded binaries or automated importer.
Create these records in a disposable site and record the resulting URLs locally.
The stable fixture keys are for documentation, not Omeka resource IDs.

## Vocabulary setup

On a fresh test database, import [ija-test.ttl](fixtures/ija-test.ttl) through
Vocabularies using prefix `ija` and namespace `https://example.org/ija-test/`.
It defines only the two custom properties read directly by this theme.
This namespace is explicitly synthetic. On an installation already containing an
`ija` vocabulary, use that vocabulary instead; do not replace it or import a
second vocabulary with the same prefix. Preserve the real vocabulary's RDF source
separately when reconstructing a production-like environment. See
[Omeka's vocabulary guide](https://omeka.org/s/docs/user-manual/content/vocabularies/).

## Create the fixtures

Use the literal values in the JSON manifest, mark the records public, and add them
to the test site's item pool. Add English/Hebrew language tags where specified.
For each media recipe, supply locally created, rights-cleared test media:

- A two-page PDF and a small image for a book/archival preview.
- A short MP4 for the exact `Video` record type.
- URL/embed media for which Omeka reports no stored filename.

No external media URLs or production files are bundled. A missing-thumbnail PDF
and a URL with a null filename are also covered deterministically by the PHP
fixtures; a full Omeka installation may generate a thumbnail automatically.
Use an isolated media fixture to exercise the real missing-thumbnail path; do not
delete live thumbnails. For the filename fallback, the PHP test uses `scan.PDF`;
Omeka may normalize a real upload's stored filename, so the upload alone is not
proof that the fallback branch ran.

## Manual checks

| Fixture | Expected result |
| --- | --- |
| `book-pdf` | Book icon, IJA number, metadata, PDF download when media embedding is off |
| `archival-images` | Archive icon; configured viewer loads usable pages in Preview |
| `video` | One player with embedding both off and on; no second Preview viewer |
| `pdf-no-thumbnail` | Download remains usable; no broken empty thumbnail image |
| `url-media` | No null-filename warnings; no PDF download for non-PDF media |
| `minimal` | Generic icon; absent custom fields/media do not break rendering |
| `multilingual` | Browse/preview honor locale filtering; untagged values remain eligible |
| `unknown-type` | Generic icon for an unrecognized record type |

Also test empty and populated search results, each sort direction, pagination,
site/global property-label settings, preview component selections, and all three
subnavigation modes. Create parent/child content pages and a heading-only preview
block. Add a linked-resource value to another fixture and check Linked resources.
Review PHP logs and browser console/network errors while exercising the pages.

Save outcomes and screenshots using [the screenshot protocol](screenshots/README.md).
Fixtures and screenshots have **not** been imported/captured automatically.
