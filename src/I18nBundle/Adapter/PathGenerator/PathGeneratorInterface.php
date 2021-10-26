<?php

namespace I18nBundle\Adapter\PathGenerator;

use I18nBundle\Model\I18nZoneInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

interface PathGeneratorInterface
{
    public function configureOptions(OptionsResolver $options): void;

    public function setOptions(array $options): void;

    public function setZone(I18nZoneInterface $zone): void;

    public function getUrls(bool $onlyShowRootLanguages = false): array;
}
