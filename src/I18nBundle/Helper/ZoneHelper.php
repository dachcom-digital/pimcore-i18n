<?php

namespace I18nBundle\Helper;

use I18nBundle\Definitions;

class ZoneHelper
{
    /**
     * @param array  $zoneDomains
     * @param string $languageIso
     * @param string $fallBackLanguageIso
     * @param string $countryIso
     * @param string $fallBackCountryIso
     *
     * @return bool
     */
    public function findUrlInZoneTree(
        $zoneDomains,
        $languageIso,
        $fallBackLanguageIso,
        $countryIso = NULL,
        $fallBackCountryIso = Definitions::INTERNATIONAL_COUNTRY_NAMESPACE
    ) {
        if (!is_array($zoneDomains)) {
            return FALSE;
        }

        $indexId = FALSE;
        if (empty($countryIso)) {
            $indexId = array_search($languageIso, array_column($zoneDomains, 'languageIso'));
            if ($indexId === FALSE) {
                $indexId = array_search($fallBackLanguageIso, array_column($zoneDomains, 'languageIso'));
            }
        } else {

            $index = array_keys(array_filter($zoneDomains,
                function ($v) use ($languageIso, $countryIso) {
                    return $v['languageIso'] === $languageIso && $v['countryIso'] === $countryIso;
                }));

            if (empty($index)) {
                $index = array_keys(array_filter($zoneDomains,
                    function ($v) use ($fallBackLanguageIso, $countryIso) {
                        return $v['languageIso'] === $fallBackLanguageIso && $v['countryIso'] === $countryIso;
                    }));
            }

            if (empty($index)) {
                $index = array_keys(array_filter($zoneDomains,
                    function ($v) use ($languageIso, $fallBackCountryIso) {
                        return $v['languageIso'] === $languageIso && $v['countryIso'] === $fallBackCountryIso;
                    }));
            }

            if (empty($index)) {
                $index = array_keys(array_filter($zoneDomains,
                    function ($v) use ($fallBackLanguageIso, $fallBackCountryIso) {
                        return $v['languageIso'] === $fallBackLanguageIso && $v['countryIso'] === $fallBackCountryIso;
                    }));
            }

            if (isset($index[0])) {
                $indexId = $index[0];
            }
        }

        if ($indexId === FALSE) {
            return FALSE;
        }

        $docData = $zoneDomains[$indexId];

        return $docData['homeUrl'];
    }
}