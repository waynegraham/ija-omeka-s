<?php
namespace IjaTheme;

/** Partition filtered catalogue data without dropping or relabelling properties. */
final class ItemMetadata
{
    public static function group(array $values, bool $filterLocale = false, string $language = ''): array
    {
        $groups = [
            'description' => ['title' => 'Description and identification', 'open' => true, 'values' => []],
            'publication' => ['title' => 'Publication and language', 'open' => true, 'values' => []],
            'physical' => ['title' => 'Physical characteristics', 'open' => false, 'values' => []],
            'preservation' => ['title' => 'Preservation and treatment', 'open' => false, 'values' => []],
            'rights' => ['title' => 'Access and reuse', 'open' => true, 'values' => []],
        ];
        foreach ($values as $term => $data) {
            $data['values'] = array_filter($data['values'], static function ($value) use ($filterLocale, $language) {
                $lang = (string) $value->lang();
                return !$filterLocale || $lang === '' || strcasecmp($lang, $language) === 0;
            });
            if (!$data['values']) continue;
            $local = strtolower(substr($term, strpos($term, ':') + 1));
            $group = 'description';
            if (in_array($term, ['dcterms:rights', 'dcterms:license', 'dcterms:accessRights', 'dcterms:rightsHolder'], true)) {
                $group = 'rights';
            } elseif (preg_match('/mold|dirty|mended|rehoused|flattening|treatment|conservation|preservation/', $local)) {
                $group = 'preservation';
            } elseif (preg_match('/^(depth|height|width|size|thickness|attachments)$/', $local)
                || in_array($local, ['book_format', 'cover_material', 'archival_materials_format', 'format_of_archival_materials', 'binding', 'binding_type'], true)
                || in_array($term, ['dcterms:extent', 'dcterms:medium', 'dcterms:format'], true)) {
                $group = 'physical';
            } elseif (preg_match('/date|publication|publisher|author|creator|contributor|language|oclc/', $local)) {
                $group = 'publication';
            }
            $groups[$group]['values'][$term] = $data;
        }
        return array_filter($groups, static fn ($group) => (bool) $group['values']);
    }
}
