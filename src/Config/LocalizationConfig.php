<?php

declare(strict_types=1);

namespace Tipi\Localization\Config;

use Tipi\Localization\Enums\StorageDriver;
use Tipi\Localization\Exceptions\InvalidLocaleConfigurationException;
use Tipi\Localization\Models\Locale;
use Tipi\Localization\Models\LocaleContext;
use Tipi\Localization\Policies\LocaleContextPolicy;
use Tipi\Localization\Policies\LocalePolicy;
use Tipi\Localization\Support\ContextName;
use Tipi\Localization\Support\CountryCode;
use Tipi\Localization\Support\LocaleCode;
use Tipi\Support\Enums\TextDirection;

/**
 * Validated, normalized view of config/localization.php.
 *
 * @phpstan-type LocaleConfig array{code: string, name: string, native_name: string, native_abbreviation: ?string, country_code: ?string, text_direction: TextDirection}
 * @phpstan-type ContextConfig array{locales: list<string>|null, default: ?string}
 */
final readonly class LocalizationConfig
{
    /**
     * @param  list<LocaleConfig>  $locales
     * @param  array<string, ContextConfig>  $contexts
     * @param  list<string>  $switchRouteMiddleware
     * @param  class-string<Locale>  $localeModel
     * @param  class-string<LocaleContext>  $contextModel
     * @param  class-string  $localePolicy
     * @param  class-string  $contextPolicy
     */
    public function __construct(
        public StorageDriver $localesDriver,
        public array $locales,
        public ?string $defaultLocale,
        public StorageDriver $contextsDriver,
        public string $defaultContext,
        public array $contexts,
        public bool $hideDefaultLocale,
        public bool $negotiateRootLocale,
        public string $rootRoute,
        public bool $switchRouteEnabled,
        public string $switchRouteUri,
        public string $switchRouteName,
        public array $switchRouteMiddleware,
        public string $preferenceCookie,
        public int $preferenceMinutes,
        public string $localeModel,
        public string $contextModel,
        public string $localePolicy,
        public string $contextPolicy,
    ) {}

    /**
     * @param  array<string, mixed>  $config
     */
    public static function fromArray(array $config): self
    {
        $localesDriver = self::driver($config, 'locales_driver');

        $locales = $localesDriver === StorageDriver::Config
            ? self::locales($config['locales'] ?? null)
            : [];

        $defaultLocale = $localesDriver === StorageDriver::Config
            ? self::defaultLocale($config['default_locale'] ?? null, $locales)
            : null;

        $defaultContext = $config['default_context'] ?? 'web';

        if (! is_string($defaultContext) || ! ContextName::isValid($defaultContext)) {
            throw new InvalidLocaleConfigurationException(
                '[default_context] must be a valid context name (lowercase letters, digits, "-" and "_").',
            );
        }

        $switch = $config['switch_route'] ?? [];

        if (! is_array($switch)) {
            throw new InvalidLocaleConfigurationException('[switch_route] must be an array.');
        }

        $switchUri = self::string($switch, 'uri', 'switch_route.uri', 'locale/{locale}');

        if (! str_contains($switchUri, '{locale}')) {
            throw new InvalidLocaleConfigurationException('[switch_route.uri] must contain the {locale} parameter.');
        }

        $preference = $config['preference'] ?? [];

        if (! is_array($preference)) {
            throw new InvalidLocaleConfigurationException('[preference] must be an array.');
        }

        $minutes = $preference['minutes'] ?? 60 * 24 * 365;

        if (! is_int($minutes) || $minutes < 1) {
            throw new InvalidLocaleConfigurationException('[preference.minutes] must be a positive integer.');
        }

        $middleware = $switch['middleware'] ?? ['web'];

        if (! is_array($middleware) || ! array_is_list($middleware)) {
            throw new InvalidLocaleConfigurationException('[switch_route.middleware] must be a list.');
        }

        $models = is_array($config['models'] ?? null) ? $config['models'] : [];
        $policies = is_array($config['policies'] ?? null) ? $config['policies'] : [];

        return new self(
            localesDriver: $localesDriver,
            locales: $locales,
            defaultLocale: $defaultLocale,
            contextsDriver: self::driver($config, 'contexts_driver'),
            defaultContext: $defaultContext,
            contexts: self::contexts($config['contexts'] ?? [], $defaultContext),
            hideDefaultLocale: self::bool($config, 'hide_default_locale', true),
            negotiateRootLocale: self::bool($config, 'negotiate_root_locale', false),
            rootRoute: self::string($config, 'root_route', 'root_route', 'home'),
            switchRouteEnabled: self::bool($switch, 'enabled', true),
            switchRouteUri: $switchUri,
            switchRouteName: self::string($switch, 'name', 'switch_route.name', 'localization.switch'),
            switchRouteMiddleware: $middleware,
            preferenceCookie: self::string($preference, 'cookie', 'preference.cookie', 'locale'),
            preferenceMinutes: $minutes,
            localeModel: self::model($models, 'locale', Locale::class),
            contextModel: self::model($models, 'context', LocaleContext::class),
            localePolicy: self::existingClass($policies, 'locale', LocalePolicy::class),
            contextPolicy: self::existingClass($policies, 'context', LocaleContextPolicy::class),
        );
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private static function driver(array $config, string $key): StorageDriver
    {
        $value = $config[$key] ?? StorageDriver::Config->value;

        $driver = is_string($value) ? StorageDriver::tryFrom($value) : null;

        if ($driver === null) {
            throw new InvalidLocaleConfigurationException(
                "[{$key}] must be \"config\" or \"database\", got [".get_debug_type($value).(is_scalar($value) ? " {$value}" : '').'].',
            );
        }

        return $driver;
    }

    /**
     * @return list<LocaleConfig>
     */
    private static function locales(mixed $locales): array
    {
        if (! is_array($locales) || $locales === []) {
            throw new InvalidLocaleConfigurationException(
                '[locales] must list at least one locale when the "config" locales driver is used.',
            );
        }

        $normalized = [];

        foreach (array_values($locales) as $index => $locale) {
            if (! is_array($locale)) {
                throw new InvalidLocaleConfigurationException("Locale at index {$index} must be an array.");
            }

            foreach (['code', 'name', 'native_name'] as $key) {
                if (! isset($locale[$key]) || ! is_string($locale[$key]) || trim($locale[$key]) === '') {
                    throw new InvalidLocaleConfigurationException(
                        "Locale at index {$index} must contain a non-empty [{$key}] string.",
                    );
                }
            }

            $code = $locale['code'];

            if (! LocaleCode::isValid($code)) {
                throw new InvalidLocaleConfigurationException("Locale code [{$code}] is invalid.");
            }

            if (isset($normalized[$code])) {
                throw new InvalidLocaleConfigurationException("Locale [{$code}] is defined more than once.");
            }

            $abbreviation = $locale['native_abbreviation'] ?? null;

            if ($abbreviation !== null && (! is_string($abbreviation) || mb_strlen($abbreviation) > 3)) {
                throw new InvalidLocaleConfigurationException(
                    "Native abbreviation for locale [{$code}] must be a string of at most 3 characters.",
                );
            }

            $country = $locale['country_code'] ?? null;

            if ($country !== null && (! is_string($country) || ! CountryCode::isValid($country))) {
                throw new InvalidLocaleConfigurationException(
                    "Country code for locale [{$code}] must be a two-letter uppercase country code.",
                );
            }

            $direction = $locale['text_direction'] ?? TextDirection::Ltr->value;
            $direction = is_string($direction) ? TextDirection::tryFrom($direction) : null;

            if ($direction === null) {
                throw new InvalidLocaleConfigurationException(
                    "Text direction for locale [{$code}] must be [ltr] or [rtl].",
                );
            }

            $normalized[$code] = [
                'code' => $code,
                'name' => $locale['name'],
                'native_name' => $locale['native_name'],
                'native_abbreviation' => $abbreviation,
                'country_code' => $country,
                'text_direction' => $direction,
            ];
        }

        return array_values($normalized);
    }

    /**
     * @param  list<LocaleConfig>  $locales
     */
    private static function defaultLocale(mixed $default, array $locales): string
    {
        if (! is_string($default) || $default === '') {
            throw new InvalidLocaleConfigurationException(
                '[default_locale] must be set when the "config" locales driver is used.',
            );
        }

        if (! in_array($default, array_column($locales, 'code'), true)) {
            throw new InvalidLocaleConfigurationException(
                "[default_locale] [{$default}] is not one of the configured locales.",
            );
        }

        return $default;
    }

    /**
     * @return array<string, ContextConfig>
     */
    private static function contexts(mixed $contexts, string $defaultContext): array
    {
        if (! is_array($contexts)) {
            throw new InvalidLocaleConfigurationException('[contexts] must be an array keyed by context name.');
        }

        $normalized = [];

        foreach ($contexts as $name => $context) {
            if (! is_string($name) || ! ContextName::isValid($name)) {
                throw new InvalidLocaleConfigurationException(
                    'Context names must be lowercase letters, digits, "-" and "_" (max '.ContextName::MAX_LENGTH.' characters), got ['.$name.'].',
                );
            }

            if (! is_array($context)) {
                throw new InvalidLocaleConfigurationException("Context [{$name}] must be an array.");
            }

            $locales = $context['locales'] ?? '*';

            if ($locales !== '*') {
                if (! is_array($locales) || $locales === [] || ! array_is_list($locales)) {
                    throw new InvalidLocaleConfigurationException(
                        "Context [{$name}] [locales] must be \"*\" or a non-empty list of locale codes.",
                    );
                }

                foreach ($locales as $code) {
                    if (! is_string($code)) {
                        throw new InvalidLocaleConfigurationException(
                            "Context [{$name}] [locales] must only contain locale code strings.",
                        );
                    }
                }

                $locales = array_values(array_unique($locales));
            }

            $default = $context['default'] ?? null;

            if ($default !== null && ! is_string($default)) {
                throw new InvalidLocaleConfigurationException("Context [{$name}] [default] must be a locale code or null.");
            }

            $normalized[$name] = [
                'locales' => $locales === '*' ? null : $locales,
                'default' => $default,
            ];
        }

        $normalized[$defaultContext] ??= ['locales' => null, 'default' => null];

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private static function bool(array $config, string $key, bool $default): bool
    {
        $value = $config[$key] ?? $default;

        if (! is_bool($value)) {
            throw new InvalidLocaleConfigurationException("[{$key}] must be a boolean.");
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private static function string(array $config, string $key, string $path, string $default): string
    {
        $value = $config[$key] ?? $default;

        if (! is_string($value) || trim($value) === '') {
            throw new InvalidLocaleConfigurationException("[{$path}] must be a non-empty string.");
        }

        return $value;
    }

    /**
     * @template T of object
     *
     * @param  array<string, mixed>  $models
     * @param  class-string<T>  $base
     * @return class-string<T>
     */
    private static function model(array $models, string $key, string $base): string
    {
        $model = $models[$key] ?? $base;

        if (! is_string($model) || ! is_a($model, $base, true)) {
            throw new InvalidLocaleConfigurationException(
                "[models.{$key}] must be a class extending [{$base}].",
            );
        }

        return $model;
    }

    /**
     * @param  array<string, mixed>  $policies
     * @return class-string
     */
    private static function existingClass(array $policies, string $key, string $default): string
    {
        $class = $policies[$key] ?? $default;

        if (! is_string($class) || ! class_exists($class)) {
            throw new InvalidLocaleConfigurationException("[policies.{$key}] must be an existing class.");
        }

        return $class;
    }
}
