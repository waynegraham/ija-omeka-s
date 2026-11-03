# Reference screenshots

No deployed-site reference images are committed here. `npm run test:browser`
captures isolated page, browse and item layouts at both sizes and attaches them
to the Playwright report (see the README). CI retains those artifacts for 14 days;
they are not pixel-diff baselines or evidence about deployed content or viewers.
This directory defines the broader baseline to collect on a running test site.
Use only the synthetic records from [the fixture guide](../sample-records.md).

## Capture consistently

Use the same browser/version, English site language, 100% zoom and viewport sizes
of **1440 × 1000** and **390 × 844** CSS pixels. Wait for fonts, images and viewer
content to load. Record whether each image is viewport-only or full-page. Keep
settings and test content consistent before/after an upgrade.

Capture these states at both sizes:

| Name | Content/state |
| --- | --- |
| `browse` | Populated cards, metadata, sorting and pagination |
| `book-pdf` | Book fixture, Preview, PDF download and metadata |
| `video` | One player with media embedding enabled |
| `advanced-search` | Populated property query and sort controls |
| `content-page` | Parent/child navigation and a heading-only browse preview |

On mobile, also capture `navigation-open` with the menu expanded. Test playback,
dropdowns, search submission, PDF downloads and viewer controls interactively;
screenshots cannot prove those actions work.

Store files under `VERSION/`, such as `2.0.1/browse-desktop.png`, alongside a
`capture.md` containing:

```text
Date and operator:
Theme version and exact commit:
Omeka / PHP / viewer-module versions:
Browser and operating system:
Viewport, zoom, device-pixel ratio, viewport-only/full-page:
Local fixture keys and actual record/page URLs:
Site language, embedding, locale, subnavigation and preview-component settings:
Console/PHP-log findings and interactive checks:
Known differences or intentionally accepted changes:
```

Review each before/after pair for missing content, overflow, font/color changes,
broken thumbnails, duplicate players and navigation behavior. Keep an older
baseline when accepting a new one; explain the reason in the capture notes.
