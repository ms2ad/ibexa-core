<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Core\MVC\Symfony\Locale;

/**
 * Interface for locale converters.
 * Ibexa uses <ISO639-2/B>-<ISO3166-Alpha2> locale format (mostly, some supported locales being out of this format, e.g. cro-HR).
 * Symfony uses the standard POSIX locale format (<ISO639-1>_<ISO3166-Alpha2>), which is supported by Intl PHP extension.
 *
 * Locale converters are meant to convert in those 2 formats back and forth.
 */
interface LocaleConverterInterface
{
    /**
     * Converts a locale in Ibexa internal format to POSIX format.
     * Returns null if conversion cannot be made.
     *
     * @param string $ezpLocale
     *
     * @return string|null
     */
    public function convertToPOSIX($ezpLocale);

    /**
     * Converts a locale in POSIX format to Ibexa internal format.
     * Returns null if conversion cannot be made.
     *
     * @deprecated 4.5.2 To be removed in 5.0. Use {@see convertToRepository()} instead.
     *
     * @param string $posixLocale
     *
     * @return string|null
     */
    public function convertToEz($posixLocale);

    /**
     * Converts a locale in POSIX format to Repository internal format.
     * Returns null if conversion cannot be made.
     */
    public function convertToRepository(string $posixLocale): ?string;
}

class_alias(LocaleConverterInterface::class, 'eZ\Publish\Core\MVC\Symfony\Locale\LocaleConverterInterface');
