<?php

namespace I18nBundle\Adapter\PathGenerator;

use I18nBundle\Model\I18nZoneInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

interface PathGeneratorInterface
{
    public function configureOptions(OptionsResolver $options): void;

    public function getUrls(I18nZoneInterface $zone, bool $onlyShowRootLanguages = false): array;
}
