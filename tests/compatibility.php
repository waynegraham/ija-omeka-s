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
    public function partial($template, $variables = []): string { return $this->render($template, $variables); }
    public function assetUrl($path): string { return '/theme/asset/' . $path; }
    public function url(...$args): string { return '/search'; }
    public function setting(...$args) { return null; }
    public function siteSetting($name, $default = null) { return $this->siteSettings[$name] ?? $default; }
    public function lang(): string { return 'en'; }
    public function htmlElement($name): self { return $this; }
    public function appendAttribute(...$args): self { return $this; }
    public function pageTitle($title, $level = 2): string { return "<h$level>" . $this->escapeHtml($title) . "</h$level>"; }
    public function params(): self { return $this; }
    public function fromQuery($key = null, $default = null) { return $key === null ? [] : $default; }
    public function trigger($name, ...$args): void { $this->events[] = $name; }
    public function queryToHiddenInputs($exclude): string { $this->hiddenExclusions = $exclude; return ''; }
}

final class ThemeItem
{
    public function __construct(private array $media = [], private string|Stringable|null $type = null) {}
    public function value($term) { return $term === 'ija:record_type' ? $this->type : null; }
    public function media(): array { return $this->media; }
    public function displayTitle(): string { return 'Test item'; }
    public function displayValues(): string { return '<dl><dt>Title</dt><dd>Test item</dd></dl>'; }
    public function displaySubjectValues(array $options = []): string { return ''; }
}

final class ThemeMedia
{
    public function __construct(private ?string $filename, private ?string $type) {}
    public function filename(): ?string { return $this->filename; }
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
    'URL media with no filename renders without PHP deprecations' => static function () {
        $html = (new ThemeView)->render('omeka/site/item/show', [
            'item' => new ThemeItem([new ThemeMedia(null, null)]),
        ]);
        check(str_contains($html, 'Test item'), 'Item did not render');
        check(!str_contains($html, 'Download Full Text'), 'URL media treated as a PDF');
    },
    'PDF media identified by MIME type with nullable filename and thumbnail' => static function () {
        $html = (new ThemeView)->render('omeka/site/item/show', [
            'item' => new ThemeItem([new ThemeMedia(null, 'application/pdf')]),
        ]);
        check(str_contains($html, 'Download Full Text'), 'PDF download is missing');
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
        check(str_contains($html, 'Download Full Text'), 'Uppercase PDF download is missing');
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
