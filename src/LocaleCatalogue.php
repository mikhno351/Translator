<?php

declare(strict_types=1);

namespace KetPHP\Translator;

use KetPHP\Translator\Common\TranslationLoaderInterface;

final class LocaleCatalogue
{

    public function __construct(private readonly string $filename, private readonly TranslationLoaderInterface $loader)
    {
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function getLoader(): TranslationLoaderInterface
    {
        return $this->loader;
    }
}