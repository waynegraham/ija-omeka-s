# Template override inventory

Recorded 2026-11-02. Upstream comparison baseline: **Omeka S 4.2.1**. Each path
below is relative to `view/`; its core counterpart has the same path under
Omeka's `application/view/`. This is a maintenance inventory, not a promise that
every core feature is implemented. Recheck behavior as well as markup on upgrades.

| Override | Purpose / intentional difference | Contract to preserve |
| --- | --- | --- |
| `layout/layout.phtml` | Bootstrap header/footer, IJA branding and assets | Core jQuery/global script, queued assets, messages, content, `view.layout` |
| `omeka/site/item/show.phtml` | IJA title, preview, PDF downloads, metadata and linked resources; fixed layout instead of resource page blocks | Before-show hook; special non-video after-show placement; one video render; subject-value options array |
| `omeka/site/item/browse.phtml` | Two-column cards and fixed IJA property sorts | Both browse hooks, item-set query context, locale filtering, Browse helper sort contract |
| `omeka/site/item/search.phtml` | Bootstrap advanced-search form | GET submission, browse action, resource type and query propagation |
| `omeka/site/index/search.phtml` | Sitewide results grouped by resource kind | Page title versus resource display title; full-text links and totals |
| `omeka/site/page/show.phtml` | Bootstrap content/sidebar layout and breadcrumbs | Supplied page body classes, navigation visibility, content, hooks and page pagination |
| `common/advanced-search.phtml` | Styled search assembly; resource-class partial intentionally omitted | Partial exclude list, resource-type branches, `view.advanced_search` filtering and core search JS |
| `common/advanced-search/fulltext.phtml` | Bootstrap full-text input | Query value, escaping and input name |
| `common/advanced-search/properties.phtml` | Bootstrap property-query rows | Core query names, joiners, property select and add/remove JS hooks; review newly added core operators |
| `common/advanced-search/sort.phtml` | Styled sort controls through Laminas | Context/resource mapping, configured sorts and default-sort flags |
| `common/block-layout/browse-preview.phtml` | IJA preview markup | Selected components, locale filtering and browse query/link |
| `common/resource-values.phtml` | Bootstrap metadata definition list | Settings fallback, value rendering, locale/private/annotation behavior and `view.show.value` |
| `common/search-form.phtml` | Header search controls | Sitewide/cross-site routes and `fulltext_search` |
| `common/sort-selector.phtml` | Bootstrap sort form | `sortConfig`, selected values and hidden query inputs excluding default-sort flags |
| `common/pagination.phtml` | Compact previous/range/next controls | Core helper's totals, offsets, page bounds and generated URLs |
| `common/site-page-pagination.phtml` | Bootstrap previous/next page buttons | Core helper's previous/next page objects |

## Theme-only partials

These have no matching core template at the recorded baseline:

| Partial | Responsibility |
| --- | --- |
| `common/bootstrap-navigation.phtml` | Laminas navigation rendered with Bootstrap dropdowns plus header search |
| `common/bootstrap-breadcrumbs.phtml` | Bootstrap breadcrumb markup supplied with navigation pages |
| `common/record-type-icon.phtml` | Literal IJA record-type-to-SVG mapping, including generic fallback |
| `common/icon.phtml` | Decorative SVG referencing the local sprite; shared sizing and inherited text color |
| `common/pdf-download.phtml` | PDF detection, nullable thumbnail and escaped download card |

## Review an upstream change

From the theme root, set an absolute Omeka path and compare one file at a time:

```sh
OMEKA_PATH=/absolute/path/to/omeka-s
diff -u "$OMEKA_PATH/application/view/omeka/site/item/browse.phtml" view/omeka/site/item/browse.phtml
```

A nonzero diff exit status is expected when files differ. Identify upstream
changes to variables, helpers, settings, event placement and JavaScript hooks;
merge the relevant behavior into the IJA layout. Add a rendering regression for
changed behavior and perform the corresponding browser check. Update this
inventory's baseline only after comparing all listed overrides.

PHP regressions cover selected rendering contracts. They do not cover all
navigation/search JavaScript, every module listener, production settings or
visual appearance. Use the sample records and screenshots for those checks.
