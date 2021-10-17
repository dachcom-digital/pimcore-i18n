<?php

namespace I18nBundle\Adapter\Context;

use Pimcore\Model\Document;

interface ContextInterface
{
    public function setDocument(Document $document);

    public function getDocument(): Document;

    public function getCurrentLocale(): ?string;

    public function getCurrentLanguageIso(): ?string;

    public function getCurrentCountryIso(): ?string;

    public function getLinkedLanguages(bool $onlyShowRootLanguages = true): array;

    public function getLanguageNameByIsoCode(string $languageIso, ?string $locale = null, ?string $region = null): ?string;

    public function getCurrentContextInfo(string $slot = null): mixed;
}
