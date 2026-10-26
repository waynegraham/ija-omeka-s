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
$helpers->setService('trigger', new FixtureHelper(static fn ($name, $args) => $args));
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
foreach (['browse_layout', 'truncate_body_property'] as $name) {
    $form->add($config['config']['elements'][$name]);
    verify($form->get($name) instanceof Laminas\Form\Element\Select, 'Theme setting must resolve to a Laminas select');
}
verify(Composer\Semver\Semver::satisfies('4.0.0', $config['info']['omeka_version_constraint']), 'Omeka S 4.0 cannot select this theme');
verify(Composer\Semver\Semver::satisfies('4.2.1', $config['info']['omeka_version_constraint']), 'Omeka S 4.2 cannot select this theme');
echo "PASS: Theme metadata and configuration work with Omeka 4 and Laminas\n";
