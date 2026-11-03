<?php
/** Render database-free fixtures through the real Omeka/Laminas helpers. */
$argv[1] = getenv('OMEKA_PATH') ?: dirname(__DIR__, 4);
require dirname(__DIR__) . '/omeka-integration.php';

$view->doctype('HTML5');
$helpers->setAllowOverride(true);
foreach ([
    'url' => new FixtureHelper(static fn ($route = null, $params = [], ...$args) => $route === 'site/resource-id' ? '/items/' . $params['id'] : '/items/search'),
    'pageTitle' => new Omeka\View\Helper\PageTitle,
    'userBar' => new FixtureHelper(static fn () => ''),
    'messages' => new FixtureHelper(static fn () => ''),
    'themeSettingAssetUrl' => new FixtureHelper(static fn () => null),
    'thumbnail' => new FixtureHelper(static fn ($item) => $item->hasThumbnail ? '<img src="/thumbnail.svg" alt="Archive document" loading="lazy" decoding="async">' : ''),
    'propertySelect' => new FixtureHelper(static fn ($options) => '<select class="form-select query-property" aria-label="Property" name="' . htmlspecialchars($options['name'], ENT_QUOTES) . '"><option value="">Any property</option><option value="1">Title</option></select>'),
] as $name => $helper) {
    $helper->setView($view);
    $helpers->setService($name, $helper);
}
$siteSettings->values = ['subnav_display' => 'show', 'show_page_pagination' => false, 'show_value_annotations' => 'collapsed'];
$globalSettings->values = [];
$container = new Laminas\Navigation\Navigation([
    ['label' => 'Explore', 'uri' => '/page.html', 'active' => true, 'pages' => [
        ['label' => 'Recovery of artifacts', 'uri' => '/page.html'],
        ['label' => 'Browse records', 'uri' => '/browse.html'],
    ]],
    ['label' => 'Learn', 'uri' => '/item.html', 'pages' => [
        ['label' => 'Example item', 'uri' => '/item.html'],
        ['label' => 'Advanced search', 'uri' => '/search.html'],
    ]],
]);
$nav = $view->navigation($container);
$site = new class($nav) {
    public function __construct(private object $nav) {}
    public function publicNav() { return $this->nav; }
    public function id() { return 7; }
    public function url() { return '/page.html'; }
    public function title() { return 'Iraqi Jewish Archive'; }
};
$property = new class {
    public function __construct(private string $label = 'Title', private int $id = 1) {}
    public function label() { return $this->label; }
    public function id() { return $this->id; }
};
$value = new class('Catalogued title', true) implements Stringable {
    public function __construct(private string $text, private bool $annotated = false) {}
    public function __toString() { return $this->text; }
    public function type() { return 'literal'; }
    public function lang() { return 'en'; }
    public function valueAnnotation() { return $this->annotated ? new class { public function displayValues() { return '<p>Annotation text</p>'; } } : null; }
    public function asHtml(...$args) { return htmlspecialchars($this->text, ENT_QUOTES); }
    public function isPublic() { return true; }
};
$metadataValues = ['dcterms:title' => ['property' => $property, 'values' => [$value]]];
foreach ([
    ['dcterms:description', 'Description', 'A description placed below the viewer.'],
    ['dcterms:publisher', 'Publisher', 'Example publisher'],
    ['ija:height', 'Object Height', '19.60 cm'],
    ['ija:mended', 'Mended', 'No'],
    ['ija:metadata', 'Keywords', 'Correspondence'],
    ['dcterms:rights', 'Rights', 'Rights statement supplied by the catalogue'],
] as $index => [$term, $label, $text]) {
    $metadataValues[$term] = ['property' => new ($property::class)($label, $index + 2), 'values' => [new ($value::class)($text)]];
}
$metadata = $view->render('common/item-metadata', ['values' => $metadataValues]);
$trigger = new FixtureHelper(static function ($name, $args = []) {
    if ($name === 'view.show.after') echo '<div class="mirador viewer" style="display:flex;align-items:center;justify-content:center"><img src="/thumbnail.svg" alt="Viewer integration fixture" style="height:90%;max-width:90%;object-fit:contain"></div>';
    return $args;
});
$trigger->setView($view); $helpers->setService('trigger', $trigger);
$item = new class($metadata) {
    public bool $hasThumbnail = true;
    public int $resourceId = 17159;
    public function id() { return $this->resourceId; }
    public function url(...$args) { return 'http://127.0.0.1:8766/item.html'; }
    public string $title = '';

    public function __construct(private string $metadata) {}
    public function value($term, ...$options) { return match ($term) {
        'ija:record_type' => 'Books', 'ija:ija_number' => '123',
        'dcterms:alternative' => 'Alternative <title>',
        'dcterms:description' => 'A record from the archive.', default => null,
    }; }
    public function displayTitle(...$args) { return $this->title ?: 'Archive <title> ' . str_repeat('Long title ', 8); }
    public function displayDescription(...$args) { return 'A short description of this archive record.'; }
    public function media() {
        $files = [];
        foreach ([['image/jpeg', 'scan_A.jpg'], ['image/jpeg', 'scan_E.jpg'], ['application/pdf', 'scan.pdf']] as [$type, $filename]) {
            $files[] = new class($type, $filename) {
                public function __construct(private string $type, private string $filename) {}
                public function mediaType() { return $this->type; }
                public function filename() { return 'storage-hash.' . pathinfo($this->filename, PATHINFO_EXTENSION); }
                public function source() { return $this->filename; }
                public function displayTitle() { return $this->filename; }
                public function originalUrl() { return '/original/' . $this->filename; }
            };
        }
        return $files;
    }
    public function displayValues() { return $this->metadata; }
    public function displaySubjectValues(...$args) { return ''; }
    public function link($text, ...$args) { return '<a href="/item.html">' . htmlspecialchars((string) $text, ENT_QUOTES) . '</a>'; }
    public function linkRaw($html, ...$args) { return '<a href="/item.html">' . $html . '</a>'; }
};
$withoutThumbnail = clone $item;
$withoutThumbnail->hasThumbnail = false;
$featured = [];
foreach ([17159, 1022, 284, 269] as $index => $id) {
    $resource = clone $item;
    $resource->resourceId = $id;
    $resource->title = ['מחזור לראש השנה', 'Map: The World Series 1145', 'Books awaiting preservation', '2003 Memorandum of Agreement'][$index];
    $featured[] = $resource;
}
$api = new class($featured) {
    public function __construct(private array $items) {}
    public function search(...$args) {
        return new class($this->items) {
            public function __construct(private array $items) {}
            public function getContent() { return $this->items; }
        };
    }
};
$helper = new FixtureHelper(static fn () => $api);
$helper->setView($view);
$helpers->setService('api', $helper);
$gallery = '<h1>Recovery of artifacts</h1><div class="blocks-inner page-layout-normal"><div class="block block-media"><div class="media-embed layout-horizontal multiple-attachments">';
for ($i = 0; $i < 6; $i++) {
    $gallery .= '<div class="item resource"><div class="media-render"><img src="/thumbnail.svg" alt="Recovered artifact"></div><h3>Recovered artifact</h3></div>';
}
$gallery .= '</div></div></div>';
$page = new class {
    public function __construct(private string $slug = 'example') {}
    public function title() { return 'Recovery of artifacts'; }
    public function slug() { return $this->slug; }
};
$home = new ($page::class)('home');
$exhibit = new ($page::class)('recovery-artifacts');
$exhibitContent = file_get_contents(dirname(__DIR__) . '/fixtures/recovery-exhibit.html');
$rtl = clone $item;
$rtl->title = 'מחזור לראש השנה — IJA 80';
$arabic = clone $item;
$arabic->title = 'وثائق من الأرشيف — IJA 238';
$homeContent = '<div class="block block-html"><div class="jumbotron breakout parallax parallax1"><h1>The Iraqi Mukhabarat<br>Archive of Jewish Materials</h1><p>Chronicling Iraqi Jewish Heritage</p></div></div><div class="block block-html"><h2>Preserving the Iraqi Jewish Archive</h2><p>' . str_repeat('The archive preserves a record of Jewish life in Iraq. ', 12) . '</p></div>';

$directory = __DIR__ . '/generated';
if (!is_dir($directory)) mkdir($directory, 0777, true);
foreach (['page', 'exhibit', 'generic-exhibit', 'home', 'legacy-home', 'browse', 'item', 'rtl', 'arabic', 'results', 'empty-results', 'search'] as $fixture) {
    $view->headLink()->getContainer()->exchangeArray([]);
    $view->headScript()->getContainer()->exchangeArray([]);
    $view->htmlElement('body')->setAttribute('class', '');
    $helper = new Omeka\View\Helper\ThemeSetting($fixture === 'legacy-home' ? ['home_layout' => false] : []);
    $helper->setView($view);
    $helpers->setService('themeSetting', $helper);
    $content = match ($fixture) {
        'generic-exhibit' => $view->render('omeka/site/page/show', ['site' => $site, 'page' => $page, 'content' => '<div class="media-embed"><div class="item resource"><div class="media-render"><a href="/item/288"><img src="/thumbnail.svg" alt=""></a></div><div class="caption"><p>Photograph without a linked item title</p></div></div></div>', 'displayNavigation' => true, 'pageBodyClass' => 'page']),
        'exhibit' => $view->render('omeka/site/page/show', ['site' => $site, 'page' => $exhibit, 'content' => $exhibitContent, 'displayNavigation' => true, 'pageBodyClass' => 'page']),
        'page' => $view->render('omeka/site/page/show', ['site' => $site, 'page' => $page, 'content' => $gallery, 'displayNavigation' => true, 'pageBodyClass' => 'page']),
        'home', 'legacy-home' => $view->render('omeka/site/page/show', ['site' => $site, 'page' => $home, 'content' => $homeContent, 'displayNavigation' => false, 'pageBodyClass' => 'page']),
        'arabic' => $view->render('omeka/site/item/show', ['item' => $arabic]),
        'rtl' => $view->render('omeka/site/item/show', ['item' => $rtl]),
        'results', 'empty-results' => $view->render('omeka/site/index/search', ['query' => 'archive', 'results' => $fixture === 'results' ? ['items' => ['resources' => [$item, $rtl], 'total' => 24]] : []]),
        'browse' => $view->render('omeka/site/item/browse', ['items' => [$item, $withoutThumbnail]]),
        'item' => $view->render('omeka/site/item/show', ['item' => $item]),
        'search' => '<h1>Advanced search</h1><form id="advanced-search">' . $view->render('common/advanced-search/properties', ['query' => []]) . '</form>',
    };
    if ($fixture === 'search') {
        $view->headLink()->prependStylesheet($view->assetUrl('css/advanced-search.css', 'Omeka'));
        $view->headScript()->appendFile($view->assetUrl('js/advanced-search.js', 'Omeka'));
        $view->headScript()->appendScript('jQuery(function(){ Omeka.prepareSearchForm(jQuery("#advanced-search")); });');
    }
    file_put_contents("$directory/$fixture.html", $view->render('layout/layout', ['site' => $site, 'content' => $content]));
}
echo "Rendered browser fixtures.\n";
