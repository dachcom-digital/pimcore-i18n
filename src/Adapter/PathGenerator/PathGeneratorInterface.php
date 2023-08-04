<?php

namespace I18nBundle\Adapter\PathGenerator;

use I18nBundle\Context\I18nContextInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

interface PathGeneratorInterface
{
    public function configureOptions(OptionsResolver $options): void;

    public function getUrls(I18nContextInterface $i18nContext, bool $onlyShowRootLanguages = false): array;
}
