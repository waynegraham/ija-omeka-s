<?php
namespace IjaTheme;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;

/** Lossless, deliberately narrow presentation adapter for the migrated exhibit. */
final class RecoveryExhibit
{
    public static function prepare(string $content): ?array
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        try {
            $loaded = $document->loadHTML('<!doctype html><html><head><meta charset="UTF-8"></head><body><div id="exhibit-source">' . $content . '</div></body></html>', LIBXML_NONET);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        if (!$loaded) return null;
        $xpath = new DOMXPath($document);
        $root = $document->getElementById('exhibit-source');
        if (!$root) return null;
        $containers = $xpath->query('./*[contains(concat(" ", normalize-space(@class), " "), " blocks-inner ")]', $root);
        $container = $containers->length === 1 ? $containers->item(0) : $root;
        if ($container !== $root) {
            foreach ($root->childNodes as $sibling) {
                if ($sibling !== $container && ($sibling instanceof DOMElement || trim($sibling->textContent) !== '')) return null;
            }
        }
        $narratives = [];
        $photos = [];
        $documents = [];
        $titles = $mediaBlocks = $resourceBlocks = 0;
        $shape = [];
        foreach ($container->childNodes as $block) {
            if (!$block instanceof DOMElement) continue;
            if (!self::hasClass($block, 'block')) return null;
            if (self::hasClass($block, 'block-pageTitle')) {
                $titles++; $shape[] = 'title';
                $headings = $xpath->query('./h1|./h2', $block);
                if ($headings->length !== 1 || $xpath->query('./*', $block)->length !== 1 || self::textKey($block->textContent) !== self::textKey($headings->item(0)->textContent)) return null;
            } elseif (self::hasClass($block, 'related_materials')) {
                $resourceBlocks++; $shape[] = 'resources';
                if ($xpath->query('.//p|.//img|.//iframe|.//svg|.//object|.//video|.//audio', $block)->length) return null;
                $headings = $xpath->query('./h2', $block);
                if ($headings->length !== 1) return null;
                $consumed = $headings->item(0)->textContent;
                foreach ($xpath->query('.//a[@href]', $block) as $link) {
                    $consumed .= $link->textContent;
                    $href = $link->getAttribute('href');
                    $filename = basename((string) parse_url($href, PHP_URL_PATH));
                    $itemId = [
                        'fdb14e11b58e12ff466258a707fb225beb201e4d.pdf' => 269,
                        '695a7b637254b7dc8a081bd9b1eb311b19f020a0.pdf' => 271,
                    ][$filename] ?? null;
                    $documents[] = ['href' => $href, 'title' => trim($link->textContent), 'itemId' => $itemId, 'isPdf' => strtolower(pathinfo($filename, PATHINFO_EXTENSION)) === 'pdf'];
                }
                if (self::textKey($block->textContent) !== self::textKey($consumed)) return null;
            } elseif (self::hasClass($block, 'block-html')) {
                $shape[] = 'html';
                $narratives[] = self::innerHtml($block);
            } elseif (self::hasClass($block, 'block-media')) {
                $mediaBlocks++; $shape[] = 'media';
                $embeds = $xpath->query('./*[contains(concat(" ", normalize-space(@class), " "), " media-embed ")]', $block);
                if ($embeds->length !== 1) return null;
                $embed = $embeds->item(0);
                foreach ($block->childNodes as $child) {
                    if ($child !== $embed && ($child instanceof DOMElement || trim($child->textContent) !== '')) return null;
                }
                foreach ($embed->childNodes as $child) {
                    if ($child instanceof DOMElement && !self::hasClass($child, 'resource')) return null;
                    if (!$child instanceof DOMElement && trim($child->textContent) !== '') return null;
                }
                foreach ($xpath->query('./*[contains(concat(" ", normalize-space(@class), " "), " resource ")]', $embed) as $resource) {
                    foreach ($resource->childNodes as $child) {
                        if ($child instanceof DOMElement && !in_array($child->tagName, ['a', 'h3'], true) && !self::hasClass($child, 'caption') && !self::hasClass($child, 'media-render')) return null;
                    }
                    $image = $xpath->query('.//img[@src]', $resource)->item(0);
                    $link = $xpath->query('.//a[@href]', $resource)->item(0);
                    if ($xpath->query('.//img', $resource)->length !== 1) return null;
                    if (!$image || !$link || !preg_match('~/item/(\d+)(?:[/?#]|$)~', $link->getAttribute('href'), $match)) return null;
                    // Unsupported media must keep its original renderer via fallback.
                    if ($xpath->query('.//iframe|.//video|.//audio', $resource)->length) return null;
                    $captions = $xpath->query('./h3|./*[contains(concat(" ", normalize-space(@class), " "), " caption ")]', $resource);
                    $captionHtml = $captionText = '';
                    foreach ($captions as $caption) {
                        $captionHtml .= self::innerHtml($caption);
                        $captionText .= ' ' . $caption->textContent;
                    }
                    if (self::textKey($resource->textContent) !== self::textKey($captionText)) return null;
                    $title = trim($captionText ?: $image->getAttribute('alt'));
                    if ($title === '') $title = 'Photograph';
                    $src = $image->getAttribute('src');
                    $photos[] = [
                        'id' => (int) $match[1], 'recordUrl' => $link->getAttribute('href'),
                        'src' => str_replace('/files/square/', '/files/large/', $src),
                        'thumbnail' => str_replace('/files/square/', '/files/medium/', $src),
                        'title' => $title, 'captionHtml' => $captionHtml,
                    ];
                }
            } else {
                return null;
            }
        }
        // Additional/reordered editorial structures must not silently disappear.
        if ($titles !== 1 || $resourceBlocks !== 1 || count($narratives) !== 4 || $mediaBlocks !== 3 || !$photos || !$documents) return null;
        if ($shape !== ['title', 'resources', 'media', 'html', 'media', 'html', 'html', 'html', 'media']) return null;
        foreach (['~On May 6, 2003~i', '~Seeking guidance~i', '~Given limited treatment options~i', '~Phase 1 Drying the Collection~i'] as $index => $marker) {
            if (!preg_match($marker, strip_tags($narratives[$index]))) return null;
        }
        $byId = [];
        foreach ($photos as $photo) $byId[$photo['id']] ??= $photo;
        $sections = [];
        foreach ([
            ['discovery', 'Discovery in the flooded basement', [288, 286]],
            ['stabilization', 'Emergency recovery and stabilization', [298, 312]],
            ['transport', 'Assessment and transport', [318, 332]],
            ['drying', 'Drying the collection', [336, 338]],
        ] as $index => [$id, $title, $ids]) {
            // The original phase heading is represented by the section heading.
            $html = $narratives[$index];
            if ($index === 3) {
                $html = preg_replace('~^\s*<h3>Phase 1 Drying the Collection</h3>\s*~i', '', $html);
                $title = 'Phase 1: Drying the collection';
            }
            $selected = [];
            foreach ($ids as $photoId) if (isset($byId[$photoId])) $selected[] = $byId[$photoId];
            $sections[] = compact('id', 'title', 'html') + ['photos' => $selected];
        }
        return compact('sections', 'photos', 'documents');
    }

    private static function textKey(string $text): string
    {
        return preg_replace('/\s+/u', '', $text);
    }

    private static function hasClass(DOMElement $node, string $class): bool
    {
        return in_array($class, preg_split('/\s+/', trim($node->getAttribute('class'))), true);
    }

    private static function innerHtml(DOMNode $node): string
    {
        $html = '';
        foreach ($node->childNodes as $child) $html .= $node->ownerDocument->saveHTML($child);
        return $html;
    }
}
