<?php

declare(strict_types=1);

namespace KetPHP\Translator\Loader;

use KetPHP\Translator\Common\TranslationLoaderInterface;
use KetPHP\Translator\Exception\FileLoaderException;

final class PhpTranslationLoader implements TranslationLoaderInterface
{

    /**
     * @throws FileLoaderException
     */
    public function load(string $filename): array
    {
        if (str_ends_with($filename, '.php') === false) {
            throw new FileLoaderException('Not a supported resource format.');
        }
        if (is_file($filename) === false) {
            throw new FileLoaderException(sprintf('File not found: %s.', $filename));
        }

        $data = require $filename;

        if (is_array($data) === false) {
            throw new FileLoaderException(sprintf('Invalid PHP translation file content: %s.', $resource));
        }

        return $data;
    }
}