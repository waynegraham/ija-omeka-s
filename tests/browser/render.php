<?php
/** Render database-free fixtures through the real Omeka/Laminas helpers. */
$argv[1] = getenv('OMEKA_PATH') ?: dirname(__DIR__, 4);
require dirname(__DIR__) . '/omeka-integration.php';

$view->doctype('HTML5');
$helpers->setAllowOverride(true);
foreach ([
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
$property = new class { public function label() { return 'Title'; } };
$value = new class {
    public function type() { return 'literal'; }
    public function lang() { return 'en'; }
    public function valueAnnotation() { return new class { public function displayValues() { return '<p>Annotation text</p>'; } }; }
    public function asHtml() { return 'Catalogued title'; }
    public function isPublic() { return true; }
};
$metadata = $view->render('common/resource-values', ['values' => ['dcterms:title' => ['property' => $property, 'values' => [$value]]]]);
$item = new class($metadata) {
    public bool $hasThumbnail = true;
    public int $resourceId = 17159;
    public function id() { return $this->resourceId; }
    public string $title = '';

    public function __construct(private string $metadata) {}
    public function value($term, ...$options) { return match ($term) {
        'ija:record_type' => 'Books', 'ija:ija_number' => '123',
        'dcterms:alternative' => 'Alternative <title>',
        'dcterms:description' => 'A record from the archive.', default => null,
    }; }
    public function displayTitle(...$args) { return $this->title ?: 'Archive <title> ' . str_repeat('Long title ', 8); }
    public function displayDescription(...$args) { return 'A short description of this archive record.'; }
    public function media() { return []; }
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
$rtl = clone $item;
$rtl->title = 'מחזור לראש השנה — IJA 80';
$arabic = clone $item;
$arabic->title = 'وثائق من الأرشيف — IJA 238';
$homeContent = '<div class="block block-html"><div class="jumbotron breakout parallax parallax1"><h1>The Iraqi Mukhabarat<br>Archive of Jewish Materials</h1><p>Chronicling Iraqi Jewish Heritage</p></div></div><div class="block block-html"><h2>Preserving the Iraqi Jewish Archive</h2><p>' . str_repeat('The archive preserves a record of Jewish life in Iraq. ', 12) . '</p></div>';

$directory = __DIR__ . '/generated';
if (!is_dir($directory)) mkdir($directory, 0777, true);
foreach (['page', 'home', 'legacy-home', 'browse', 'item', 'rtl', 'arabic', 'results', 'empty-results', 'search'] as $fixture) {
    $view->headLink()->getContainer()->exchangeArray([]);
    $view->headScript()->getContainer()->exchangeArray([]);
    $view->htmlElement('body')->setAttribute('class', '');
    $helper = new Omeka\View\Helper\ThemeSetting($fixture === 'legacy-home' ? ['home_layout' => false] : []);
    $helper->setView($view);
    $helpers->setService('themeSetting', $helper);
    $content = match ($fixture) {
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
        $view->headScript()->appendFile($view->assetUrl('js/advanced-search.js', 'Omeka'));
        $view->headScript()->appendScript('jQuery(function(){ Omeka.prepareSearchForm(jQuery("#advanced-search")); });');
    }
    file_put_contents("$directory/$fixture.html", $view->render('layout/layout', ['site' => $site, 'content' => $content]));
}
echo "Rendered browser fixtures.\n";
