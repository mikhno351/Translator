# KetPHP Translator
A flexible and powerful translation library for PHP applications that supports multiple translation formats, locale fallbacks, and various placeholder replacement methods.  

![Packagist Version](https://img.shields.io/packagist/v/ket-php/translator)
![Packagist Downloads](https://img.shields.io/packagist/dt/ket-php/translator?logo=packagist&logoColor=white)
![Static Badge](https://img.shields.io/badge/PHP-8.1-777BB4?logo=php&logoColor=white)

## Features
- **Multiple Loader Support** - Use different loaders for various translation file formats
- **Locale Fallback** - Automatic fallback to default locale when translations are missing
- **Dot Notation** - Access nested translation keys using dot notation
- **Flexible Placeholders** - Support for both positional (%s) and named (%name%) placeholders
- **Error Resilient** - Graceful handling of missing translations and formatting errors
- **Absolute & Relative Paths** - Support for both absolute and relative translation file paths

## Installation
```composer
composer require ket-php/translator
```

## Usage

### Initialize the Translator

```php
use KetPHP\Translator\Loader\JsonTranslationLoader;
use KetPHP\Translator\Loader\PhpTranslationLoader;
use KetPHP\Translator\Translator;
use KetPHP\Translator\Locale;

$translator = new Translator(
    rootDirectory: '/path/to/translations',
    localeDefault: 'en', // another use \KetPHP\Translator\Locale::ENGLISH - en
    locale: 'ru' // current locale (optional, uses localeDefault if null)
);

// Add translations with use rootDirectory
$translator->addLocale(Locale::ENGLISH, new PhpTranslationLoader(), 'en.php');
$translator->addLocale(Locale::RUSSIAN, new JsonTranslationLoader(), 'ru.json');

// Add translations with absolute path
$translator->addLocale(Locale::BELARUSIAN, new JsonTranslationLoader(), '/absolute/path/to/be.json');

$allTranslations = $translator->translations();
// Returns merged array of current locale + default locale translations
```

### Add Translation Loaders
```php
use KetPHP\Translator\Common\TranslationLoaderInterface;

class YourTranslationLoader implements TranslationLoaderInterface
{
    public function load(string $filename): array
    {
        // Your code...
    }
}
```

### Create Translation Files
en.php:
```php
<?php return [
    'language_tag' => 'en-US'
    'user' => [
        'profile' => [
            'greeting' => 'Welcome, %s!',
            'welcome_back' => 'Welcome back, %name%!',
            'messages' => 'You have %count% new messages'
        ]
    ],
    'errors' => [
        'not_found' => 'The requested resource was not found'
    ]
];
```
ru.json
```json
{
  "language_tag": "ru-RU",
  "user": {
    "profile": {
      "greeting": "Добро пожаловать, %s!",
      "welcome_back": "С возвращением, %name%!",
      "messages": "У вас %count% новых сообщений"
    }
  }
}
```

### Using the Translator
```php
// Basic Translation
echo $translator->translate('welcome');
// Output: "Welcome to the application!" (if locale is 'en')

// With Dot Notation
echo $translator->translate('user.profile.greeting', 'Welcome!');
// Output: "Welcome, %s!" (fallback to key if no default provided)

// Positional Placeholders
echo $translator->translate('user.profile.greeting', 'Welcome!', 'John');
// Output: "Welcome, John!"

echo $translator->translate('user.profile.greeting', null, 'John', 'Doe');
// Uses fallback replacement: "Welcome, John! Doe"

// Named Placeholders
echo $translator->translate('user.profile.welcome_back', null, ['name' => 'Alice']);
// Output: "Welcome back, Alice!"

echo $translator->translate('user.profile.messages', null, ['count' => 5]);
// Output: "You have 5 new messages"

// Key exists only in default locale (en)
echo $translator->translate('errors.not_found');
// Output: "The requested resource was not found" (falls back to English)

// Non-existent key with default value
echo $translator->translate('nonexistent.key', 'Default value');
// Output: "Default value"

// Non-existent key without default
echo $translator->translate('another.missing.key');
// Output: "another.missing.key" (returns the key itself)
```