<?php

declare(strict_types=1);

namespace Tipi\Localization;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\AcceptHeader;
use Tipi\Support\Locale;

/**
 * Picks the locale for a visitor within a context:
 *
 * 1. the remembered (explicitly selected) preference;
 * 2. the best Accept-Language match among the context's locales;
 * 3. the context default.
 */
final readonly class LocaleNegotiator
{
    public function __construct(
        private LocalePreferences $preferences,
    ) {}

    public function negotiate(Request $request, LocaleContext $context): Locale
    {
        return $this->preferences->get($context, $request)
            ?? $this->fromAcceptLanguage($request, $context)
            ?? $context->default();
    }

    /**
     * The best Accept-Language match, or null when nothing matches.
     *
     * For each language range (highest quality first): an exact
     * case-insensitive match, then the range with trailing subtags removed
     * ("zh-Hant-TW" -> "zh-Hant" -> "zh"), then any supported code with the
     * same primary language ("en" -> "en-US").
     */
    public function fromAcceptLanguage(Request $request, LocaleContext $context): ?Locale
    {
        $header = $request->headers->get('Accept-Language');

        if ($header === null || trim($header) === '') {
            return null;
        }

        $codes = [];

        foreach ($context->codes() as $code) {
            $codes[strtolower($code)] = $code;
        }

        foreach (AcceptHeader::fromString($header)->all() as $item) {
            if ($item->getQuality() <= 0) {
                continue;
            }

            $range = strtolower(str_replace('_', '-', trim($item->getValue())));

            if ($range === '' || $range === '*') {
                continue;
            }

            $parts = explode('-', $range);

            for ($length = count($parts); $length > 0; $length--) {
                $candidate = implode('-', array_slice($parts, 0, $length));

                if (isset($codes[$candidate])) {
                    return $context->find($codes[$candidate]);
                }
            }

            foreach ($codes as $lower => $code) {
                if (explode('-', $lower)[0] === $parts[0]) {
                    return $context->find($code);
                }
            }
        }

        return null;
    }
}
