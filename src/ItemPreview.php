<?php
namespace IjaTheme;

use DOMDocument;
use DOMXPath;

/** Detect potential preview content without discarding module hook output. */
final class ItemPreview
{
    public static function hasContent(string $html): bool
    {
        if (trim($html) === '') return false;
        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        try {
            $loaded = $document->loadHTML('<!doctype html><html><head><meta charset="UTF-8"></head><body>' . $html . '</body></html>', LIBXML_NONET);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        if (!$loaded) return false;
        $xpath = new DOMXPath($document);
        foreach ($xpath->query('//script | //style | //template | //noscript | //meta | //link | //*[@hidden] | //input[translate(@type, "HIDDEN", "hidden")="hidden"] | //comment()') as $node) {
            $node->parentNode?->removeChild($node);
        }
        $body = $document->getElementsByTagName('body')->item(0);
        // Empty elements can be viewer mounts populated by JavaScript later.
        return $body && (trim($body->textContent) !== '' || $body->getElementsByTagName('*')->length > 0);
    }
}
