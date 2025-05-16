<?php

declare(strict_types=1);
/**
 * Copyright 2022-2025 FOSSBilling
 * Copyright 2011-2021 BoxBilling, Inc.
 * SPDX-License-Identifier: Apache-2.0.
 *
 * @copyright FOSSBilling (https://www.fossbilling.org)
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 */

namespace FOSSBilling;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\HttpFoundation\Request;

class i18n
{
    /**
     * Get locale for current request based on the following order:
     *
     * 1. Cookie.
     * 2. Browser's Accept-Language header.
     * 3. Default locale from config.
     *
     * @return string IETF BCP 47 language tag (e.g., `en_US`, `fr_FR`, etc.)
     */
    public static function getCurrentLocale(): string
    {
        $locale = null;
        $request = Request::createFromGlobals();

        if ($request->cookies->has('locale') && in_array($request->cookies->get('locale'), self::getInstalledLocales(true))) {
            $locale = $request->cookies->get('locale');
        } elseif ($request->getPreferredLanguage(self::getInstalledLocales(true))) {
            $locale = $request->getPreferredLanguage(self::getInstalledLocales(true));

            if (!headers_sent()) {
                setcookie('locale', $locale, ['expires' => strtotime('+1 month'), 'path' => '/']);
            }
        } else {
            $locale = Config::getProperty('i18n.locale', 'en_US');
        }

        return $locale;
    }

    /**
     * Retrieve list of installed locales.
     *
     * @param bool|null $status True to get the list of enabled locales. False returns the list of disabled locales. Null returns all locales.
     *
     * @return array Installed locales (IETF BCP 47 language tag) and their status. Example: `['en_US' => true, 'fr_FR' => false]`
     */
    public static function getInstalledLocales(bool $status): array
    {
        $finder = new Finder();

        $enabledLocales = [];
        if ($status === true || is_null($status)) {
            $enabledLocales = iterator_to_array($finder->directories()->in(PATH_LANGS)->depth('== 0')->notPath('/^[a-z]{2}_[A-Z]{2}.disabled/')->sortByName());
            $enabledLocalesArray = array_fill_keys($enabledLocales, true);
        }

        $disabledLocales = [];
        if ($status === false || is_null($status)) {
            $disabledLocales = iterator_to_array($finder->directories()->in(PATH_LANGS)->depth('== 0')->path('/^[a-z]{2}_[A-Z]{2}.disabled/')->sortByName());
            $disabledLocalesArray = array_fill_keys($disabledLocales, false);
        }

        $installedLocales = array_merge($enabledLocalesArray, $disabledLocalesArray);

        if (empty($installedLocales)) {
            return ['en_US' => true];
        }

        return $installedLocales;
    }

    /**
     * Enables or disables a locale depending on it's current status.
     *
     * @param string $locale The locale code (IETF BCP 47 language tag) to toggle (Example: `en_US`).
     *
     * @return bool To indicate if it was successful.
     *
     * @throws InformationException
     */
    public static function toggleLocale(string $locale): bool
    {
        $filesystem = new Filesystem();

        $localePath = Path::normalize(PATH_LANGS . "/{$locale}");
        if (!$filesystem->exists($localePath)) {
            throw new InformationException('Unable to enable / disable the locale as it is not present in the locale folder.');
        }

        $disableFile = Path::normalize("{$localePath}/.disabled");

        // Reverse the status of the locale
        if ($filesystem->exists($disableFile)) {
            try {
                $filesystem->remove($disableFile);
            } catch (IOException $e) {
                throw new InformationException('Unable to enable / disable the locale due to an error: ' . $e->getMessage());
            }

            return true;
        } else {
            try {
                $filesystem->dumpFile($disableFile, '');
            } catch (IOException $e) {
                throw new InformationException('Unable to enable / disable the locale due to an error: ' . $e->getMessage());
            }

            return $filesystem->exists($disableFile);
        }
    }

    /**
     * Returns how complete a locale is.
     * Will return 0 if the `completion.php` doesn't exist or if it doesn't include the specified locale.
     *
     * @param string $locale The locale IETF BCP 47 language tag (e.g., `en_US`, `fr_FR`, etc.).
     *
     * @return int Percentage complete for the specified locale.
     */
    public static function getLocaleCompletionPercent(string $locale): int
    {
        $filesystem = new Filesystem();

        if ($locale === 'en_US') {
            return 100;
        }

        $completionFile = Path::normalize(PATH_LANGS . '/completion.php');
        if (!$filesystem->exists($completionFile)) {
            return 0;
        }

        $completion = include $completionFile;

        return intval($completion[$locale] ?? 0);
    }
}
