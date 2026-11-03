<?php
namespace IjaTheme;

final class ItemFiles
{
    public static function describe(iterable $media): array
    {
        $files = ['images' => [], 'pdfs' => [], 'other' => []];
        foreach ($media as $file) {
            $type = (string) $file->mediaType();
            $filename = (string) $file->filename();
            $sourceName = self::sourceName((string) $file->source());
            $isPdf = $type === 'application/pdf'
                || strtolower(pathinfo($filename, PATHINFO_EXTENSION)) === 'pdf'
                || strtolower(pathinfo($sourceName, PATHINFO_EXTENSION)) === 'pdf';
            $group = str_starts_with($type, 'image/') ? 'images' : ($isPdf ? 'pdfs' : 'other');
            $files[$group][] = ['url' => $file->originalUrl(), 'filename' => $sourceName ?: ((string) $file->displayTitle() ?: $filename), 'type' => $type];
        }
        return $files;
    }

    private static function sourceName(string $source): string
    {
        if ($source === '') return '';
        if (preg_match('~^[a-z][a-z0-9+.-]*://~i', $source)) {
            // Keep the name, never URL credentials, directories or query tokens.
            $path = (string) parse_url($source, PHP_URL_PATH);
            return basename(rawurldecode($path));
        }
        return basename(str_replace('\\', '/', $source));
    }
}

