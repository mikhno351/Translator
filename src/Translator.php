<?php

declare(strict_types=1);

namespace KetPHP\Translator;

use KetPHP\Translator\Common\TranslationLoaderInterface;
use KetPHP\Translator\Common\TranslatorInterface;
use Symfony\Component\Filesystem\Path;
use Throwable;
use ErrorException;

/**
 * Translator implementation for handling multiple language translations.
 *
 * Supports loading translation files from different locales, merging fallback translations,
 * and replacing placeholders in translation strings with both positional and named parameters.
 */
final class Translator implements TranslatorInterface
{

    /**
     * @var array<string, LocaleCatalogue> Array of locale catalogues keyed by locale code
     */
    protected array $locales = [];

    /**
     * @var array<string, mixed> Cache of loaded translations
     */
    protected array $translations = [];

    /**
     * Constructor
     *
     * @param string $rootDirectory Root directory for relative translation file paths
     * @param string $localeDefault Default locale code to use as fallback
     * @param string|null $locale Current locale code (uses `$localeDefault` if null)
     */
    public function __construct(
        protected string  $rootDirectory,
        protected string  $localeDefault,
        protected ?string $locale = null
    )
    {
        $this->rootDirectory = Path::normalize($this->rootDirectory);

        if ($this->locale === null) {
            $this->locale = $this->localeDefault;
        }
    }

    /**
     * Add a locale catalogue with translations
     *
     * @param string $locale Locale code (e.g., 'en', 'fr')
     * @param TranslationLoaderInterface $loader Loader for translation files
     * @param string $filename Path to translation file (absolute or relative to rootDirectory)
     * @return void
     */
    public function addLocale(string $locale, TranslationLoaderInterface $loader, string $filename): void
    {
        $this->locales[$locale] = new LocaleCatalogue(Path::isAbsolute($filename) ? $filename : Path::join($this->rootDirectory, $filename), $loader);
    }

    /**
     * Get all loaded translations
     *
     * @return array<string, mixed> Array of translation keys and values
     */
    public function translations(): array
    {
        if (count($this->translations) === 0) {
            $this->buildTranslations();
        }

        return $this->translations;
    }

    /**
     * Translate a key with optional parameters and default value
     *
     * @param string $key Translation key (dot notation supported)
     * @param string|null $default Default value if key not found (uses key if null)
     * @param mixed ...$parameters Parameters for placeholder replacement
     * @return string Translated string with replaced placeholders
     */
    public function translate(string $key, ?string $default = null, mixed ...$parameters): string
    {
        if (count($this->translations) === 0) {
            $this->buildTranslations();
        }

        $value = $this->getByDot($this->translations, $key);

        if (is_null($value) === true) {
            $value = $default ?? $key;
        }
        if (count($parameters) === 0) {
            return (string)$value;
        }

        $first = $parameters[0] ?? null;
        $this->isAssocParams($parameters);

        $paramsForReplace = $this->castParamsToString($parameters);

        if (count($parameters) === 1 && is_array($first) === true && $this->isAssocArray($first) === true) {
            $assoc = array_map('strval', $first);
            return $this->applyNamedPlaceholders((string)$value, $assoc);
        }

        try {
            return $this->safeVSprintf((string)$value, $paramsForReplace);
        } catch (Throwable) {
            try {
                return $this->fallbackPositionalReplace((string)$value, $paramsForReplace);
            } catch (Throwable) {
                return (string)$value;
            }
        }
    }

    private function buildTranslations(): void
    {
        $this->translations = [];

        $def = $this->localeDefault;
        if (isset($this->locales[$def]) === true) {
            $this->mergeCatalogue($this->locales[$def]);
        }
        if ($this->locale !== $def && isset($this->locales[$this->locale]) === true) {
            $this->mergeCatalogue($this->locales[$this->locale]);
        }
    }

    private function mergeCatalogue(LocaleCatalogue $catalogue): void
    {
        try {
            $loaded = $catalogue->getLoader()->load($catalogue->getFileName());

            $this->translations = array_replace_recursive($this->translations, $loaded);
        } catch (Throwable $e) {
            error_log(sprintf('Translation loader failed for %s: %s', $catalogue->getFileName(), $e->getMessage()));
        }
    }

    private function getByDot(array $array, string $key): mixed
    {
        if ($key === '') {
            return null;
        }

        if (array_key_exists($key, $array) === true) {
            return $array[$key];
        }

        $segments = explode('.', $key);
        $ref = $array;

        foreach ($segments as $seg) {
            if (is_array($ref) === false || array_key_exists($seg, $ref) === false) {
                return null;
            }
            $ref = $ref[$seg];
        }

        return $ref;
    }

    private function isAssocParams(array $parameters): bool
    {
        if (count($parameters) !== 1) {
            return false;
        }
        $first = @$parameters[0];
        return is_array($first) === true && $this->isAssocArray($first) === true;
    }

    private function isAssocArray(array $arr): bool
    {
        if ($arr === []) {
            return false;
        }
        return array_keys($arr) !== range(0, count($arr) - 1);
    }

    private function castParamsToString(array $params): array
    {
        $result = [];
        foreach ($params as $p) {
            if (is_array($p) === true) {
                if ($this->isAssocArray($p) === true) {
                    $assoc = array_map(function ($v) {
                        return (string)$v;
                    }, $p);
                    $result[] = $assoc;
                    continue;
                }
                $result[] = implode(', ', array_map('strval', $p));
                continue;
            }
            $result[] = (string)$p;
        }
        return $result;
    }

    private function safeVSprintf(string $format, array $params): string
    {
        if (isset($params[0]) === true && is_array($params[0]) === true && $this->isAssocArray($params[0]) === true) {
            return $this->applyNamedPlaceholders($format, $params[0]);
        }

        $flat = array_map(fn($v) => is_array($v) ? implode(', ', array_map('strval', $v)) : (string)$v, $params);

        set_error_handler(function ($severity, $message) {
            throw new ErrorException($message, 0, $severity);
        });

        try {
            return vsprintf($format, $flat);
        } finally {
            restore_error_handler();
        }
    }

    private function fallbackPositionalReplace(string $format, array $params): string
    {
        $out = $format;
        $flat = array_map('strval', $params);
        foreach ($flat as $val) {
            $pos = strpos($out, '%s');
            if ($pos === false) {
                $out .= ' ' . $val;
            } else {
                $out = substr_replace($out, $val, $pos, 2);
            }
        }
        return $out;
    }

    private function applyNamedPlaceholders(string $format, array $assoc): string
    {
        $replacements = [];
        foreach ($assoc as $key => $value) {
            $replacements['%' . $key . '%'] = (string)$value;
        }
        return strtr($format, $replacements);
    }
}