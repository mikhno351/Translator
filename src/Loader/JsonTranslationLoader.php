<?php

namespace KetPHP\Translator\Loader;

use KetPHP\Translator\Common\TranslationLoaderInterface;
use KetPHP\Translator\Exception\FileLoaderException;

final class JsonTranslationLoader implements TranslationLoaderInterface
{

    /**
     * @throws FileLoaderException
     */
    public function load(string $filename): array
    {
        if (str_ends_with($filename, '.json') === false) {
            throw new FileLoaderException('Not a supported resource format.');
        }
        if (is_file($filename) === false) {
            throw new FileLoaderException(sprintf('File not found: %s.', $filename));
        }

        $contents = file_get_contents($filename, true);

        if ($contents === false) {
            throw new FileLoaderException(sprintf('Unable to read file: %s.', $filename));
        }

        $data = json_decode($contents, true);

        if (is_array($data) === false) {
            throw new FileLoaderException(sprintf('Invalid JSON translation file content: %s.', $filename));
        }

        return $data;
    }
}