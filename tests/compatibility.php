<?php
/** Run with PHP 8.5: php tests/compatibility.php */
error_reporting(E_ALL);
set_error_handler(static function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

final class ThemeView
{
    public array $hiddenExclusions = [];
    public array $events = [];
    public array $eventOutputs = [];
    public array $siteSettings = [];

    public function render(string $template, array $variables = []): string
    {
        extract($variables);
        ob_start();
        try {
            include dirname(__DIR__) . '/view/' . $template . '.phtml';
            return ob_get_contents();
        } finally {
            ob_end_clean();
        }
    }

    public function plugin($name): Closure
    {
        return match ($name) {
            'translate' => static fn ($value) => $value,
            'escapeHtml' => static fn ($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'),
            default => throw new RuntimeException("Unexpected plugin: $name"),
        };
    }
    public function escapeHtml($value): string { return ($this->plugin('escapeHtml'))($value); }
    public function escapeHtmlAttr($value): string { return $this->escapeHtml($value); }
    public function partial($template, $variables = []): string { return $this->render($template, $variables); }
    public function assetUrl($path): string { return '/theme/asset/' . $path; }
    public function hyperlink($text, $url, $attributes = []): string {
        $html = '<a href="' . $this->escapeHtml($url) . '"';
        foreach ($attributes as $name => $value) $html .= ' ' . $name . '="' . $this->escapeHtml($value) . '"';
        return $html . '>' . $this->escapeHtml($text) . '</a>';
    }
    public function url(...$args): string { return '/search'; }
    public function setting(...$args) { return null; }
    public function siteSetting($name, $default = null) { return $this->siteSettings[$name] ?? $default; }
    public function lang(): string { return 'en'; }
    public function htmlElement($name): self { return $this; }
    public function appendAttribute(...$args): self { return $this; }
    public function pageTitle($title, $level = 2): string { return "<h$level>" . $this->escapeHtml($title) . "</h$level>"; }
    public function params(): self { return $this; }
    public function fromQuery($key = null, $default = null) { return $key === null ? [] : $default; }
    public function trigger($name, ...$args): void { $this->events[] = $name; echo $this->eventOutputs[$name] ?? ''; }
    public function queryToHiddenInputs($exclude): string { $this->hiddenExclusions = $exclude; return ''; }
}

final class ThemeItem
{
    public function __construct(private array $media = [], private string|Stringable|null $type = null) {}
    public function value($term) { return $term === 'ija:record_type' ? $this->type : null; }
    public function media(): array { return $this->media; }
    public function url(...$args): string { return 'https://example.test/item/1'; }
    public function displayTitle(): string { return 'Test item'; }
    public function displayValues(): string { return '<dl><dt>Title</dt><dd>Test item</dd></dl>'; }
    public function displaySubjectValues(array $options = []): string { return ''; }
}

final class ThemeMedia
{
    public function __construct(private ?string $filename, private ?string $type) {}
    public function filename(): ?string { return $this->filename === null ? null : 'storage-hash.' . pathinfo($this->filename, PATHINFO_EXTENSION); }
    public function source(): ?string { return $this->filename; }
    public function mediaType(): ?string { return $this->type; }
    public function thumbnailUrl($size): ?string { return null; }
    public function originalUrl(): string { return '/files/test.pdf'; }
    public function displayTitle(): string { return 'A "PDF"'; }
    public function render(...$args): string { return '<video></video>'; }
}

function check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$tests = [
    'Omeka 4 sort config renders escaped and selected options' => static function () {
        $view = new ThemeView;
        $html = $view->render('common/sort-selector', [
            'sortConfig' => ['title' => 'Title', 'dcterms:date' => 'Date <new>'],
            'sortByQuery' => 'dcterms:date', 'sortOrderQuery' => 'desc',
        ]);
        check(str_contains($html, 'value="dcterms:date" selected'), 'Selected sort is missing');
        check(str_contains($html, 'Date &lt;new&gt;'), 'Sort label is not escaped');
        check(in_array('sort_by_default', $view->hiddenExclusions, true), 'Default sort flag would override explicit selection');
        check(in_array('sort_order_default', $view->hiddenExclusions, true), 'Default order flag would override explicit selection');
    },
    'Item custom text remains plain while metadata rendering preserves HTML' => static function () {
        $item = new class {
            public function value($term) { return '<b>Custom text</b>'; }
            public function media() { return []; }
            public function url(...$args) { return 'https://example.test/item/1'; }
            public function displayTitle() { return '<b>Item title</b>'; }
            public function displayValues() { return '<dl><dt>Metadata</dt><dd><a href="/resource">Linked value</a></dd></dl>'; }
            public function displaySubjectValues(...$args) { return ''; }
        };
        $html = (new ThemeView)->render('omeka/site/item/show', ['item' => $item]);
        check(str_contains($html, '&lt;b&gt;Item title&lt;/b&gt;'), 'Item title is not escaped');
        check(substr_count($html, '&lt;b&gt;Custom text&lt;/b&gt;') >= 2, 'Custom item text is not consistently escaped');
        check(str_contains($html, '<a href="/resource">Linked value</a>'), 'Metadata HTML was escaped');
    },
    'Sort controls have visible associated labels' => static function () {
        $html = (new ThemeView)->render('common/sort-selector', ['sortConfig' => ['title' => 'Title'], 'sortByQuery' => 'title', 'sortOrderQuery' => 'desc']);
        check(str_contains($html, '<label'), 'Sort controls have no visible labels');
        check(str_contains($html, '>Sort by</span>') && str_contains($html, '>Sort order</span>'), 'Sort labels are missing');
    },
    'PDF action describes the actual new-tab behavior' => static function () {
        $html = (new ThemeView)->render('common/pdf-download', ['media' => new ThemeMedia('scan.pdf', 'application/pdf')]);
        check(str_contains($html, 'View PDF'), 'PDF action incorrectly promises a download');
        check(str_contains($html, 'opens in a new tab'), 'PDF new-tab behavior is unannounced');
    },
    'Preview empty states preserve real module markup and direct file links' => static function () {
        $view = new ThemeView;
        $html = $view->render('omeka/site/item/show', ['item' => new ThemeItem([new ThemeMedia('scan.pdf', 'application/pdf')])]);
        check(str_contains($html, 'No inline preview is available'), 'Missing viewer has no explanation');
        check(str_contains($html, 'PDF documents') && str_contains($html, 'href="/files/test.pdf"'), 'Direct file fallback is missing');
        $view->eventOutputs['view.show.after'] = '<div id="module-viewer">Viewer output</div>';
        $html = $view->render('omeka/site/item/show', ['item' => new ThemeItem]);
        check(substr_count($html, 'id="module-viewer"') === 1, 'Viewer hook output was lost or duplicated');
        check(!str_contains($html, 'No inline preview is available'), 'Empty message appears with a viewer');
    },
    'Invisible module output preserves the missing-preview explanation' => static function () {
        foreach (['<script type="application/ld+json">{"name":"Record"}</script>', '<!-- module metadata -->', '<style>.viewer { color: red; }</style>', '<div hidden><div>Hidden metadata</div></div>'] as $output) {
            $view = new ThemeView;
            $view->eventOutputs['view.show.after'] = $output;
            $html = $view->render('omeka/site/item/show', ['item' => new ThemeItem]);
            check(str_contains($html, $output), 'Invisible module output was removed');
            check(str_contains($html, 'No inline preview is available'), 'Invisible output suppresses the preview explanation');
        }
        $view = new ThemeView;
        $view->eventOutputs['view.show.after'] = '<script type="application/ld+json">{}</script><div class="mirador viewer"></div>';
        $html = $view->render('omeka/site/item/show', ['item' => new ThemeItem]);
        check(!str_contains($html, 'No inline preview is available'), 'A viewer mount was mistaken for metadata');
    },
    'Layout explicitly closes its header before main content' => static function () {
        $source = file_get_contents(dirname(__DIR__) . '/view/layout/layout.phtml');
        check(strpos($source, '</header>') !== false && strpos($source, '</header>') < strpos($source, '<main'), 'Header is not explicitly closed before main');
    },
    'Archival format uses the physical characteristics group' => static function () {
        require_once dirname(__DIR__) . '/src/ItemMetadata.php';
        $value = new class { public function lang() { return ''; } };
        $groups = IjaTheme\ItemMetadata::group(['ija:archival_materials_format' => ['values' => [$value]]]);
        check(isset($groups['physical']['values']['ija:archival_materials_format']), 'Actual archival format property is in the wrong group');
    },
    'Exhibit pagination names and escapes the destination pages' => static function () {
        $previous = new class { public function title() { return 'Preservation <Project>'; } public function siteUrl() { return '/previous'; } };
        $next = new class { public function title() { return 'Assessment and Planning'; } public function siteUrl() { return '/next'; } };
        $view = new ThemeView;
        $html = $view->render('common/site-page-pagination', ['prevPage' => $previous, 'nextPage' => $next]);
        check(str_contains($html, 'Previous: Preservation &lt;Project&gt;'), 'Previous destination is missing or unsafe');
        check(str_contains($html, 'Next: Assessment and Planning'), 'Next destination is missing');
        check(str_contains($html, 'rel="prev"') && str_contains($html, 'rel="next"'), 'Pagination relationships are missing');
    },
    'Recovery exhibit preserves prose, photographs and unknown-content fallback' => static function () {
        require_once dirname(__DIR__) . '/src/RecoveryExhibit.php';
        $content = file_get_contents(__DIR__ . '/fixtures/recovery-exhibit.html');
        $result = IjaTheme\RecoveryExhibit::prepare($content);
        check($result !== null, 'Known saved layout was not recognized');
        check(str_contains($result['sections'][0]['html'], '<em>original emphasis</em>'), 'Saved narrative HTML was changed');
        check(count($result['photos']) === 7, 'Original photographs were lost');
        check(in_array(999, array_column($result['photos'], 'id'), true), 'An uncurated gallery photograph was lost');
        check($result['documents'][0]['itemId'] === 269 && $result['documents'][1]['itemId'] === 271, 'Document records are incorrect');
        $changed = preg_replace('~</div>\s*$~', '<div class="block block-html"><p>New editorial material</p></div></div>', $content);
        check(IjaTheme\RecoveryExhibit::prepare($changed) === null, 'Unexpected added content must fall back unchanged');
        foreach ([
            str_replace('<h2>Related Materials</h2>', '<h2>Related Materials</h2><ul><li>UNIQUE_EDITORIAL_NOTE</li></ul>', $content),
            $content . '<p>Editorial content outside the block wrapper</p>',
            str_replace('<h2>Recovery of Artifacts</h2>', '<h2>Recovery of Artifacts</h2><p>New title introduction</p>', $content),
            str_replace('alt=""></a>', 'alt=""><img src="/extra.svg" alt="Additional image"></a>', $content),
        ] as $unsupported) {
            check(IjaTheme\RecoveryExhibit::prepare($unsupported) === null, 'Unsupported source content must not disappear');
        }
    },
    'Item metadata groups every visible property exactly once' => static function () {
        require_once dirname(__DIR__) . '/src/ItemMetadata.php';
        $value = new class { public function lang() { return ''; } };
        $french = new class { public function lang() { return 'fr'; } };
        $values = [];
        foreach (['dcterms:description', 'dcterms:publisher', 'ija:height', 'ija:mended', 'custom:unknown', 'dcterms:rights'] as $term) $values[$term] = ['values' => [$value]];
        $values['custom:french'] = ['values' => [$french]];
        $groups = IjaTheme\ItemMetadata::group($values, true, 'en');
        $terms = [];
        foreach ($groups as $group) $terms = array_merge($terms, array_keys($group['values']));
        check(count($terms) === 6 && count(array_unique($terms)) === 6, 'Metadata was duplicated, lost or not locale-filtered');
        check(isset($groups['description']['values']['custom:unknown']), 'Unknown properties must remain visible');
        check(isset($groups['preservation']['values']['ija:mended']), 'Treatment field is in the wrong section');
    },
    'Item files count images separately from PDFs and retain filenames' => static function () {
        require_once dirname(__DIR__) . '/src/ItemFiles.php';
        $files = IjaTheme\ItemFiles::describe([new ThemeMedia('03339_A.jpg', 'image/jpeg'), new ThemeMedia('03339_E.jpg', 'image/jpeg'), new ThemeMedia('03339.PDF', null)]);
        check(count($files['images']) === 2 && count($files['pdfs']) === 1, 'PDF was counted as an image');
        check($files['images'][1]['filename'] === '03339_E.jpg', 'Original filename was lost');
        $urlFile = new class {
            public function mediaType() { return 'image/jpeg'; }
            public function filename() { return 'another-storage-hash.jpg'; }
            public string $sourceValue = 'https://example.test/private/scan%20E.jpg?token=hidden';
            public function source() { return $this->sourceValue; }
            public function displayTitle() { return 'Catalogue image'; }
            public function originalUrl() { return '/files/original/hash.jpg'; }
        };
        $files = IjaTheme\ItemFiles::describe([$urlFile]);
        check($files['images'][0]['filename'] === 'scan E.jpg', 'Source URL paths or query parameters were exposed as filenames');
        $urlFile->sourceValue = 'C:' . chr(92) . 'private' . chr(92) . 'scan_E.jpg';
        $files = IjaTheme\ItemFiles::describe([$urlFile]);
        check($files['images'][0]['filename'] === 'scan_E.jpg', 'Windows source paths must not be exposed');
    },
    'URL media with no filename renders without PHP deprecations' => static function () {
        $html = (new ThemeView)->render('omeka/site/item/show', [
            'item' => new ThemeItem([new ThemeMedia(null, null)]),
        ]);
        check(str_contains($html, 'Test item'), 'Item did not render');
        check(!str_contains($html, 'View PDF'), 'URL media treated as a PDF');
    },
    'PDF media identified by MIME type with nullable filename and thumbnail' => static function () {
        $html = (new ThemeView)->render('omeka/site/item/show', [
            'item' => new ThemeItem([new ThemeMedia(null, 'application/pdf')]),
        ]);
        check(str_contains($html, 'View PDF'), 'PDF download is missing');
        check(!str_contains($html, 'src=""'), 'Missing thumbnail rendered as broken image');
    },
    'Templates may render multiple items in one request' => static function () {
        $view = new ThemeView;
        $html = $view->render('omeka/site/item/show', ['item' => new ThemeItem([], 'Books')]);
        $html .= $view->render('omeka/site/item/show', ['item' => new ThemeItem([], 'Video')]);
        check(str_contains($html, '#book"'), 'Book SVG is missing');
        check(str_contains($html, '#youtube"'), 'Video SVG is missing');
        check(substr_count($html, '<svg') === 2, 'Record icons must render as SVG elements');
    },
    'Record icons accept stringable Omeka values and missing values' => static function () {
        $view = new ThemeView;
        $value = new class implements Stringable {
            public function __toString(): string { return 'Archival Materials'; }
        };
        check(str_contains($view->render('common/record-type-icon', ['recordType' => $value]), '#archive"'), 'Representation value did not resolve to archive SVG');
        check(str_contains($view->render('common/record-type-icon', ['recordType' => null]), '#file"'), 'Missing value did not resolve to fallback SVG');
    },
    'Record-type SVGs preserve every icon choice and hide decorative graphics' => static function () {
        foreach (['Books' => 'book', 'Fragments' => 'images', 'Archival Materials' => 'archive',
            'Video' => 'youtube', 'Other' => 'folder', 'Documentation' => 'file-text',
            'Photograph' => 'image', 'Unknown' => 'file'] as $type => $icon) {
            $html = (new ThemeView)->render('common/record-type-icon', ['recordType' => $type]);
            check(str_contains($html, '<svg') && str_contains($html, '#' . $icon . '"'), 'Wrong SVG for ' . $type);
            check(str_contains($html, 'aria-hidden="true"') && str_contains($html, 'focusable="false"'), 'Decorative icon is exposed to assistive technology');
        }
    },
    'Header search uses a local SVG and retains its accessible button label' => static function () {
        $html = (new ThemeView)->render('common/search-form');
        check(str_contains($html, '<svg') && str_contains($html, '/theme/asset/icons/fontawesome.svg#search"'), 'Search SVG is missing');
        check(str_contains($html, 'class="visually-hidden">Search</span>'), 'Search button label is missing');
        check(!str_contains($html, '<i '), 'Legacy font icon remains in search button');
    },
    'PDF filename fallback accepts uppercase extensions' => static function () {
        $html = (new ThemeView)->render('omeka/site/item/show', [
            'item' => new ThemeItem([new ThemeMedia('scan.PDF', null)]),
        ]);
        check(str_contains($html, 'View PDF'), 'Uppercase PDF download is missing');
    },
    'Video media renders once without executing hidden legacy embeds' => static function () {
        $html = (new ThemeView)->render('omeka/site/item/show', [
            'item' => new ThemeItem([new ThemeMedia(null, 'video/mp4')], 'Video'),
        ]);
        check(substr_count($html, '<video>') === 1, 'Media was rendered again inside an HTML comment');
    },
    'Video media renders once when item media embedding is enabled' => static function () {
        $view = new ThemeView;
        $view->siteSettings['item_media_embed'] = true;
        $html = $view->render('omeka/site/item/show', [
            'item' => new ThemeItem([new ThemeMedia(null, 'video/mp4')], 'Video'),
        ]);
        check(substr_count($html, '<video>') === 1, 'Video rendered in both preview and media embeds');
    },
    'Non-video items retain media embeds and preview integration' => static function () {
        $view = new ThemeView;
        $view->siteSettings['item_media_embed'] = true;
        $html = $view->render('omeka/site/item/show', [
            'item' => new ThemeItem([new ThemeMedia(null, 'application/pdf')], 'Books'),
        ]);
        check(substr_count($html, '<video>') === 1, 'Non-video item media embed is missing');
        check(in_array('view.show.after', $view->events, true), 'Preview integration event is missing');
    },
    'Item before-show event is available to modules for every record type' => static function () {
        foreach (['Books', 'Video', null] as $type) {
            $view = new ThemeView;
            $view->render('omeka/site/item/show', ['item' => new ThemeItem([], $type)]);
            check(($view->events[0] ?? null) === 'view.show.before', 'Before-show event is missing or occurs after the preview event');
        }
    },
];

$failed = 0;
foreach ($tests as $name => $test) {
    try {
        $test();
        echo "PASS: $name\n";
    } catch (Throwable $error) {
        $failed++;
        echo "FAIL: $name: {$error->getMessage()}\n";
    }
}
exit($failed ? 1 : 0);
