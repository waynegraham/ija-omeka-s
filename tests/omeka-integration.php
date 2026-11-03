<?php
/** Run against an installed Omeka S: php tests/omeka-integration.php /path/to/omeka-s */
$omekaPath = $argv[1] ?? dirname(__DIR__, 3);
require $omekaPath . '/vendor/autoload.php';

error_reporting(E_ALL);
set_error_handler(static function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

use Laminas\View\Helper\AbstractHelper;
use Laminas\View\Renderer\PhpRenderer;
use Laminas\View\Resolver\TemplatePathStack;

// Substitute request and database-backed settings; render with real core helpers.
final class FixtureHelper extends AbstractHelper
{
    public function __construct(private Closure $callback) {}
    public function __invoke(...$args) { return ($this->callback)(...$args); }
}

function verify(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$query = ['fulltext_search' => 'archive', 'sort_by' => 'title', 'sort_order' => 'asc', 'sort_by_default' => 1, 'sort_order_default' => 1];
$view = new PhpRenderer;
$view->setResolver(new TemplatePathStack(['script_paths' => [dirname(__DIR__) . '/view']]));
$helpers = $view->getHelperPluginManager();
$helpers->setService('translate', new FixtureHelper(static fn ($text) => $text));
$helpers->setService('status', new FixtureHelper(static fn () => new class {
    public function isAdminRequest(): bool { return false; }
    public function isSiteRequest(): bool { return true; }
}));
$helpers->setService('params', new FixtureHelper(static fn () => new class($query) {
    public function __construct(private array $query) {}
    public function fromQuery($key = null, $default = null) { return $key === null ? $this->query : ($this->query[$key] ?? $default); }
}));
$helpers->setService('trigger', new FixtureHelper(static fn ($name, $args = []) => $args));
$helpers->setService('queryToHiddenInputs', new Omeka\View\Helper\QueryToHiddenInputs);
$helpers->setService('formSelect', new Laminas\Form\View\Helper\FormSelect);

$browseService = new class extends Omeka\Stdlib\Browse {
    public function __construct() {}
    public function getSortConfig(string $context, string $resourceType): array
    {
        return ['title' => 'Title', 'created' => 'Created'];
    }
};
$helpers->setService('browse', new Omeka\View\Helper\Browse(
    $browseService,
    new Laminas\Form\FormElementManager(new Laminas\ServiceManager\ServiceManager)
));
foreach (['translate', 'status', 'params', 'trigger', 'queryToHiddenInputs', 'formSelect', 'browse'] as $name) {
    $helpers->get($name)->setView($view);
}

$html = $view->browse()->renderSortSelector(['title' => 'Title', 'dcterms:date' => 'Date']);
verify(str_contains($html, 'value="" selected'), 'Full-text relevance should be selected for default sorting');
verify(str_contains($html, 'value="dcterms:date"'), 'IJA date sorting should be available');
verify(str_contains($html, 'name="fulltext_search" value="archive"'), 'Search query should survive sorting');
verify(!str_contains($html, 'name="sort_by_default"'), 'Submitting explicit sorting must clear default flags');
echo "PASS: Omeka Browse helper renders IJA sorting and full-text relevance\n";

foreach (['item', 'itemSet', 'media'] as $resourceType) {
    $html = $view->render('common/advanced-search/sort', ['query' => [], 'resourceType' => $resourceType]);
    verify(str_contains($html, 'value="created"'), 'Configured sort is missing from advanced search');
    verify(str_contains(html_entity_decode($html, ENT_QUOTES, 'UTF-8'), 'form-select mb-3'), 'Bootstrap form styles are missing');
    $html = $view->render('common/advanced-search/sort', ['query' => ['sort_by' => 'created', 'sort_order' => 'desc'], 'resourceType' => $resourceType]);
    verify(str_contains($html, 'value="created" selected="selected"'), 'Explicit advanced sort is not selected');
    $html = $view->render('common/advanced-search/sort', ['query' => ['sort_by' => 'created', 'sort_by_default' => 1], 'resourceType' => $resourceType]);
    verify(!str_contains($html, 'value="created" selected="selected"'), 'Default sort must not appear as an explicit advanced-search selection');
}
echo "PASS: Advanced sorting renders through Laminas for items, item sets and media\n";

$config = (new Laminas\Config\Reader\Ini)->fromFile(dirname(__DIR__) . '/config/theme.ini');
$form = new Laminas\Form\Form;
$form->getFormFactory()->setFormElementManager(new Laminas\Form\FormElementManager(new Laminas\ServiceManager\ServiceManager));
foreach (['truncate_body_property'] as $name) {
    $form->add($config['config']['elements'][$name]);
    verify($form->get($name) instanceof Laminas\Form\Element\Select, 'Theme setting must resolve to a Laminas select');
}
foreach (['home_layout', 'home_heading', 'home_intro', 'home_featured_items', 'home_image_caption'] as $name) {
    $form->add($config['config']['elements'][$name]);
}
verify($form->get('home_layout') instanceof Laminas\Form\Element\Checkbox, 'Homepage opt-out must be a checkbox');
$form->get('home_layout')->setValue(0);
verify(!$form->get('home_layout')->isChecked(), 'Homepage opt-out cannot be unchecked');
verify(Composer\Semver\Semver::satisfies('4.0.0', $config['info']['omeka_version_constraint']), 'Omeka S 4.0 cannot select this theme');
verify(Composer\Semver\Semver::satisfies('4.2.1', $config['info']['omeka_version_constraint']), 'Omeka S 4.2 cannot select this theme');
echo "PASS: Theme metadata and configuration work with Omeka 4 and Laminas\n";

// Keep rendering and fallback resolution real; substitute database-backed settings.
$globalSettings = new class extends Omeka\Settings\Settings {
    public array $values = [];
    public function __construct() {}
    public function get($id, $default = null) { return $this->values[$id] ?? $default; }
};
$siteSettings = new class extends Omeka\Settings\SiteSettings {
    public array $values = [];
    public function __construct() {}
    public function get($id, $default = null, $targetId = null) { return $this->values[$id] ?? $default; }
};
$userSettings = new class extends Omeka\Settings\UserSettings {
    public function __construct() {}
};
$helpers->setService('fallbackSetting', new Omeka\View\Helper\FallbackSetting(
    new Omeka\Settings\FallbackSettings($globalSettings, $siteSettings, $userSettings)
));
$helpers->setService('setting', new FixtureHelper(static fn ($id, $default = null) => $globalSettings->get($id, $default)));
$helpers->setService('siteSetting', new FixtureHelper(static fn ($id, $default = null) => $siteSettings->get($id, $default)));
$helpers->setService('lang', new FixtureHelper(static fn () => 'en'));
$helpers->setService('pageTitle', new FixtureHelper(static fn (...$args) => 'Page title'));
$helpers->setService('htmlElement', new Omeka\View\Helper\HtmlElement);
$helpers->setService('hyperlink', new Omeka\View\Helper\Hyperlink);
$navigation = new Laminas\View\Helper\Navigation;
$navigation->setPluginManager(new Laminas\View\Helper\Navigation\PluginManager(new Laminas\ServiceManager\ServiceManager));
$helpers->setService('navigation', $navigation);
$helpers->setService('thumbnail', new FixtureHelper(static fn (...$args) => '<img src="thumbnail.jpg">'));
$helpers->setService('themeSetting', new Omeka\View\Helper\ThemeSetting([]));
$helpers->setService('themeSettingAssetUrl', new FixtureHelper(static fn (...$args) => null));
$helpers->setService('searchFilters', new FixtureHelper(static fn () => ''));
$helpers->setService('pagination', new FixtureHelper(static fn () => ''));
$helpers->setService('url', new FixtureHelper(static fn (...$args) => '/items/search'));
$helpers->setService('assetUrl', new FixtureHelper(static fn ($file, $module = null) => ($module === 'Omeka' ? '/application/asset/' : '/theme/asset/') . $file));
$helpers->setService('propertySelect', new FixtureHelper(static fn (...$args) => '<select></select>'));
foreach (['fallbackSetting', 'setting', 'siteSetting', 'lang', 'pageTitle', 'htmlElement', 'hyperlink', 'navigation', 'thumbnail', 'themeSetting', 'themeSettingAssetUrl', 'searchFilters', 'pagination', 'url', 'assetUrl', 'propertySelect'] as $name) {
    $helpers->get($name)->setView($view);
}

$renderingTests = [
    'Advanced search and metadata use SVG icons with accessible controls' => static function () use ($view, $siteSettings) {
        $siteSettings->values = ['show_value_annotations' => true];
        $html = $view->render('common/advanced-search/properties', ['query' => []]);
        verify(str_contains($html, '#plus"') && str_contains($html, '#minus"'), 'Advanced search SVGs are missing');
        verify(str_contains($html, 'aria-label="Add new value"') && str_contains($html, 'aria-label="Remove value"'), 'Search control labels are missing');
        $property = new class { public function label() { return 'Title'; } };
        $value = new class {
            public function type() { return 'literal'; }
            public function lang() { return null; }
            public function valueAnnotation() { return new class { public function displayValues() { return 'Annotation text'; } }; }
            public function asHtml() { return 'Private test title'; }
            public function isPublic() { return false; }
        };
        $html = $view->render('common/resource-values', ['values' => [
            'dcterms:title' => ['property' => $property, 'alternate_label' => null, 'values' => [$value]],
        ]]);
        verify(str_contains($html, '#private"') && str_contains($html, '#annotation"'), 'Metadata SVGs are missing');
        verify(str_contains($html, 'aria-label="Private"') && str_contains($html, 'Value annotation'), 'Metadata icon labels are missing');
        verify(str_contains($html, 'class="has-annotation"') && str_contains($html, 'class="value-annotation"'), 'Annotation toggle hooks were lost');
    },
    'Annotations honor all display modes and filtered properties leave no empty rows' => static function () use ($view, $siteSettings) {
        $property = new class { public function label() { return 'Hidden French property'; } };
        $value = new class {
            public function type() { return 'literal'; }
            public function lang() { return 'fr'; }
            public function valueAnnotation() { return new class { public function displayValues() { return 'Annotation text'; } }; }
            public function asHtml() { return 'French value'; }
            public function isPublic() { return true; }
        };
        $values = ['test:term' => ['property' => $property, 'values' => [$value]]];
        foreach (['collapsed', 'expanded', ''] as $mode) {
            $siteSettings->values = ['show_value_annotations' => $mode];
            $html = $view->render('common/resource-values', ['values' => $values]);
            verify(str_contains($html, '<details') === ($mode !== ''), 'Annotation visibility does not follow setting');
            verify(str_contains($html, 'class="value-annotation" open') === ($mode === 'expanded'), 'Annotation initial state does not follow setting');
            verify(!str_contains($html, 'class="expand"'), 'Bootstrap-conflicting annotation toggle remains');
        }
        $siteSettings->values = ['filter_locale_values' => true];
        $html = $view->render('common/resource-values', ['values' => $values]);
        verify(!str_contains($html, '<dt') && !str_contains($html, '<dd'), 'Filtered metadata leaves an empty property row');
    },
    'Navigation escapes attributes and provides unique dropdown relationships' => static function () use ($view) {
        $container = new Laminas\Navigation\Navigation([
            ['label' => 'First', 'uri' => '/first', 'pages' => [['label' => '<First>', 'uri' => '/a?x="quoted"']]],
            ['label' => 'Second', 'uri' => '/second', 'pages' => [['label' => 'Second child', 'uri' => '/b']]],
        ]);
        $html = $view->render('common/bootstrap-navigation', ['container' => $container]);
        verify(substr_count($html, '<button') >= 2, 'Dropdowns need button controls');
        preg_match_all('/id="([^" ]+)"/', $html, $matches);
        verify(count($matches[1]) === count(array_unique($matches[1])), 'Duplicate navigation IDs');
        verify(str_contains($html, 'aria-labelledby=') && !str_contains($html, 'aria-controlledby='), 'Invalid dropdown ARIA');
        verify(str_contains($html, '&lt;First&gt;') && !str_contains($html, 'href="/a?x="quoted"'), 'Navigation output is not escaped');
    },
    'Search prioritizes records, escapes snippets and offers empty-state recovery' => static function () use ($view) {
        $resource = new class {
            public function title() { return 'Information page title'; }
            public function displayTitle(...$args) { return 'Record title'; }
            public function displayDescription(...$args) { return '<b>Record description</b>'; }
            public function link($text, ...$args) { return '<a>' . htmlspecialchars((string) $text, ENT_QUOTES) . '</a>'; }
            public function linkRaw($text, ...$args) { return '<a>' . $text . '</a>'; }
        };
        $html = $view->render('omeka/site/index/search', ['query' => 'book', 'results' => [
            'site_pages' => ['resources' => [$resource], 'total' => 1],
            'items' => ['resources' => [$resource], 'total' => 12],
        ]]);
        verify(strpos($html, 'Record title') < strpos($html, 'Information page title'), 'Collection records should precede pages');
        verify(str_contains($html, '&lt;b&gt;Record description&lt;/b&gt;'), 'Search descriptions missing or unescaped');
        verify(str_contains($html, 'dir="auto"'), 'Search titles need automatic text direction');
        $html = $view->render('omeka/site/index/search', ['query' => 'book', 'results' => ['items' => ['resources' => [], 'total' => 0]]]);
        verify(str_contains($html, 'Try fewer words') && str_contains($html, 'Browse the collection'), 'Empty search has no recovery actions');
    },
    'Designed homepage uses site-scoped ordered highlights and can restore stored blocks' => static function () use ($view, $helpers, $siteSettings) {
        $siteSettings->values = ['show_page_pagination' => false];
        $settings = ['home_featured_items' => '2, 99, invalid, 1, 2, -4'];
        $helpers->setAllowOverride(true);
        $helper = new Omeka\View\Helper\ThemeSetting($settings);
        $helper->setView($view);
        $helpers->setService('themeSetting', $helper);
        $requests = [];
        $api = new class($requests) {
            public function __construct(public array &$requests) {}
            public function search($resource, $query, $options = []) {
                $this->requests[] = [$resource, $query];
                $items = [];
                foreach ([1, 2] as $id) $items[] = new class($id) {
                    public function __construct(private int $id) {}
                    public function id() { return $this->id; }
                    public function displayTitle(...$args) { return 'Featured record ' . $this->id; }
                    public function value(...$args) { return null; }
                    public function link($text, ...$args) { return '<a>' . htmlspecialchars((string) $text, ENT_QUOTES) . '</a>'; }
                    public function linkRaw($html, ...$args) { return '<a>' . $html . '</a>'; }
                };
                return new class($items) { public function __construct(private array $items) {} public function getContent() { return $this->items; } };
            }
        };
        $apiHelper = new FixtureHelper(static fn () => $api);
        $apiHelper->setView($view);
        $helpers->setService('api', $apiHelper);
        $nav = $view->navigation(new Laminas\Navigation\Navigation);
        $site = new class($nav) {
            public function __construct(private object $nav) {}
            public function id() { return 7; }
            public function publicNav() { return $this->nav; }
        };
        $page = new class { public function slug() { return 'home'; } public function title() { return 'Home'; } };
        $variables = ['site' => $site, 'page' => $page, 'content' => 'Stored editorial blocks', 'displayNavigation' => false, 'pageBodyClass' => 'page'];
        $urlHelper = new FixtureHelper(static fn ($route = null, $params = [], ...$args) => $route === 'site/resource-id' ? '/items/' . $params['id'] : '/items/search');
        $urlHelper->setView($view); $helpers->setService('url', $urlHelper);
        $html = $view->render('omeka/site/page/show', $variables);
        verify(str_contains($html, 'personal-letters-3339-page-5.jpg'), 'Hero does not use the verified fifth page');
        verify(str_contains($html, 'Personal Letters') && str_contains($html, 'IJA 3339') && str_contains($html, 'Page 5'), 'Hero lacks its record/page attribution');
        verify(substr_count(html_entity_decode($html, ENT_QUOTES, 'UTF-8'), 'href="/items/16426"') === 2, 'Hero image and caption must both link to the item record');
        verify(str_contains($html, 'class="archive-hero"'), 'Approved split hero is missing');
        verify(str_contains($html, 'id="home-search"') && str_contains($html, 'name="fulltext_search"'), 'Hero search is not functional');
        verify(!str_contains($html, 'Stored editorial blocks'), 'Legacy homepage duplicates the new layout');
        verify($requests[0][1]['site_id'] === 7 && $requests[0][1]['id'] === [2, 99, 1] && $requests[0][1]['is_public'] === true, 'Featured query ignores site scope or validated IDs');
        verify(strpos($html, 'Featured record 2') < strpos($html, 'Featured record 1'), 'Curator ordering was lost');
        verify(substr_count($html, '<article class="archive-object">') === 2 && !str_contains($html, 'Featured record 99'), 'Missing or inaccessible records must be omitted');
        $imageHelper = new FixtureHelper(static fn ($id) => $id === 'home_image' ? '/uploaded/hero.jpg' : null);
        $imageHelper->setView($view); $helpers->setService('themeSettingAssetUrl', $imageHelper);
        $helper = new Omeka\View\Helper\ThemeSetting(['home_featured_items' => '', 'home_heading' => '<Custom heading>', 'home_intro' => '<Custom introduction>', 'home_image_caption' => '<Custom caption> מחזור']);
        $helper->setView($view); $helpers->setService('themeSetting', $helper);
        $customHtml = $view->render('omeka/site/page/show', $variables);
        verify(str_contains(html_entity_decode($customHtml, ENT_QUOTES, 'UTF-8'), 'src="/uploaded/hero.jpg"'), 'Custom hero image is ignored');
        verify(!str_contains($customHtml, 'archive-hero-image'), 'An unrelated uploaded image must not inherit the built-in record link');
        $helper = new Omeka\View\Helper\ThemeSetting(['home_featured_items' => '', 'home_image_item_id' => '1022']);
        $helper->setView($view); $helpers->setService('themeSetting', $helper);
        $linkedHtml = $view->render('omeka/site/page/show', $variables);
        verify(substr_count(html_entity_decode($linkedHtml, ENT_QUOTES, 'UTF-8'), 'href="/items/1022"') === 2, 'Uploaded image record override is ignored');
        verify(str_contains($customHtml, '&lt;Custom heading&gt;') && str_contains($customHtml, '&lt;Custom introduction&gt;') && str_contains($customHtml, '&lt;Custom caption&gt;'), 'Custom homepage text must be escaped');
        $imageHelper = new FixtureHelper(static fn (...$args) => null);
        $imageHelper->setView($view); $helpers->setService('themeSettingAssetUrl', $imageHelper);
        $helper = new Omeka\View\Helper\ThemeSetting(['home_layout' => false]);
        $helper->setView($view); $helpers->setService('themeSetting', $helper);
        $html = $view->render('omeka/site/page/show', $variables);
        verify(str_contains($html, 'Stored editorial blocks') && !str_contains($html, 'class="archive-hero"'), 'Homepage opt-out does not restore stored content');
        $helper = new Omeka\View\Helper\ThemeSetting(['home_featured_items' => '']);
        $helper->setView($view); $helpers->setService('themeSetting', $helper);
        $html = $view->render('omeka/site/page/show', $variables);
        verify(!str_contains($html, 'archive-highlights'), 'Empty featured selection should omit the section');
        verify(count($requests) === 1, 'Empty/disabled highlights still query the API');
        $helpers->setService('themeSetting', new Omeka\View\Helper\ThemeSetting([]));
        $helpers->get('themeSetting')->setView($view);
    },
    'Browse cards have valid description containers and preserve module hooks' => static function () use ($view, $helpers, $siteSettings) {
        $siteSettings->values = [];
        $events = [];
        $helpers->setAllowOverride(true);
        $trigger = new FixtureHelper(static function ($name, $args = []) use (&$events) {
            $events[] = $name;
            return $args;
        });
        $trigger->setView($view);
        $helpers->setService('trigger', $trigger);
        $item = new class {
            public function value($term) { return null; }
            public function displayTitle() { return 'Browse title'; }
            public function displayDescription() { return 'Browse description'; }
            public function link($content, ...$args) { return '<a>' . $content . '</a>'; }
            public function linkRaw($content, ...$args) { return '<a>' . $content . '</a>'; }
        };
        $html = $view->render('omeka/site/item/browse', ['items' => [$item]]);
        verify(str_contains($html, 'Browse description'), 'Browse description is missing');
        verify(!preg_match('/<p\b[^>]*>\s*<div\b/', $html), 'A paragraph contains a block-level description div');
        verify(in_array('view.browse.before', $events, true) && in_array('view.browse.after', $events, true), 'Browse module hooks are missing');
    },
    'Site property-label settings override global settings and fall back when absent' => static function () use ($view, $globalSettings, $siteSettings) {
        $property = new class {
            public function label() { return 'Title'; }
            public function term() { return 'dcterms:title'; }
            public function vocabulary() { return new class { public function label() { return 'Dublin Core'; } }; }
        };
        $value = new class {
            public function type() { return 'literal'; }
            public function lang() { return ''; }
            public function valueAnnotation() { return null; }
            public function asHtml() { return 'Title value'; }
            public function isPublic() { return true; }
        };
        $variables = ['values' => ['dcterms:title' => ['property' => $property, 'alternate_label' => null, 'values' => [$value]]]];
        foreach ([
            ['none', 'term', '(dcterms:title)'],
            ['none', 'vocab', '(Dublin Core)'],
            ['term', null, '(dcterms:title)'],
            ['term', 'none', null],
        ] as [$global, $site, $expected]) {
            $globalSettings->values = ['property_label_information' => $global];
            $siteSettings->values = ['property_label_information' => $site];
            $html = $view->render('common/resource-values', $variables);
            verify($expected === null ? !str_contains($html, 'field-term') : str_contains($html, $expected), 'Wrong property-label output for global=' . $global . ', site=' . ($site ?? 'unset'));
        }
    },
    'Browse preview renders only the selected resource components' => static function () use ($view, $siteSettings) {
        $siteSettings->values = [];
        $resource = new class {
            public function displayTitle() { return 'Preview title'; }
            public function displayDescription() { return 'Preview description'; }
            public function linkRaw($content, ...$args) { return '<a>' . $content . '</a>'; }
            public function link($content, ...$args) { return '<a>' . $content . '</a>'; }
        };
        foreach ([
            [['resource-heading'], false, true, false],
            [['thumbnail'], true, false, false],
            [['resource-body'], false, false, true],
            [['thumbnail', 'resource-heading', 'resource-body'], true, true, true],
        ] as [$components, $thumbnail, $heading, $body]) {
            $html = $view->render('common/block-layout/browse-preview', [
                'components' => $components, 'heading' => null, 'resources' => [$resource],
                'resourceType' => 'item', 'linkText' => null,
            ]);
            verify(str_contains($html, '<img') === $thumbnail, 'Preview thumbnail selection ignored');
            verify(str_contains($html, 'Preview title') === $heading, 'Preview heading selection ignored');
            verify(str_contains($html, 'Preview description') === $body, 'Preview body selection ignored');
        }
    },
    'Browse and preview cards respect the locale filter setting' => static function () use ($view, $siteSettings) {
        $item = new class {
            public function value($term) { return null; }
            public function displayTitle($default = null, $languages = null) { return $languages === ['en', ''] ? 'English title' : 'French title'; }
            public function displayDescription($default = null, $languages = null) { return $languages === ['en', ''] ? 'English description' : 'French description'; }
            public function link($content, ...$args) { return '<a>' . $content . '</a>'; }
            public function linkRaw($content, ...$args) { return '<a>' . $content . '</a>'; }
        };
        foreach ([false, true] as $filterLocale) {
            $siteSettings->values = ['filter_locale_values' => $filterLocale];
            foreach (['omeka/site/item/browse', 'common/block-layout/browse-preview'] as $template) {
                $html = $view->render($template, [
                    'items' => [$item], 'resources' => [$item], 'resourceType' => 'item',
                    'components' => ['resource-heading', 'resource-body'], 'heading' => null, 'linkText' => null,
                ]);
                verify(str_contains($html, $filterLocale ? 'English title' : 'French title'), 'Card title ignores locale filtering');
                verify(str_contains($html, $filterLocale ? 'English description' : 'French description'), 'Card description ignores locale filtering');
            }
        }
    },
    'Page subnavigation honors hidden, shown, leaf and navigation-disabled modes' => static function () use ($view, $siteSettings) {
        $page = new class {
            public function title() { return 'Example'; }
            public function slug() { return 'example'; }
        };
        foreach ([
            ['hide', true, true, false],
            ['show', true, true, true],
            [null, true, true, true],
            [null, false, true, false],
            ['show', true, false, false],
        ] as [$mode, $hasChildren, $displayNavigation, $expected]) {
            $siteSettings->values = ['subnav_display' => $mode, 'show_page_pagination' => false];
            $container = new Laminas\Navigation\Navigation([[
                'label' => 'Example', 'uri' => '/example', 'active' => true,
                'pages' => $hasChildren ? [['label' => 'Child', 'uri' => '/child']] : [],
            ]]);
            $nav = $view->navigation($container);
            $site = new class($nav) {
                public function __construct(private object $nav) {}
                public function publicNav() { return $this->nav; }
            };
            $html = $view->render('omeka/site/page/show', [
                'site' => $site, 'page' => $page, 'content' => 'Page content',
                'displayNavigation' => $displayNavigation,
                'pageBodyClass' => 'page custom-layout site-page-example',
            ]);
            verify(str_contains($html, 'class="sub-menu"') === $expected, 'Wrong subnavigation visibility for mode=' . ($mode ?? 'default'));
            verify(str_contains($html, 'Page content'), 'Page content was lost');
            verify(str_contains((string) $view->htmlElement('body'), 'custom-layout'), 'Configured page body class was lost');
            verify(str_contains(html_entity_decode((string) $view->headLink(), ENT_QUOTES, 'UTF-8'), '/application/asset/css/page-blocks.css'), 'Core page-block stylesheet was not enqueued');
        }
    },
];

$failed = 0;
foreach ($renderingTests as $name => $test) {
    try {
        $test();
        echo "PASS: $name\n";
    } catch (Throwable $error) {
        $failed++;
        echo "FAIL: $name: {$error->getMessage()}\n";
    }
}
if ($failed || realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    exit($failed ? 1 : 0);
}
