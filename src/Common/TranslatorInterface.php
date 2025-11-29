<?php

declare(strict_types=1);

namespace KetPHP\Translator\Common;

interface TranslatorInterface
{

    public function translate(string $key, ?string $default = null, mixed ...$parameters): string;

    public function translations(): array;
}