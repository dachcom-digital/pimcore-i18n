<?php

namespace I18nBundle\Adapter\PathGenerator;

use I18nBundle\Transformer\AlternateRouteItemTransformer;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractPathGenerator implements PathGeneratorInterface
{
    protected AlternateRouteItemTransformer $alternateRouteItemTransformer;

    public function setAlternateRouteItemTransformer(AlternateRouteItemTransformer $alternateRouteItemTransformer): void
    {
        $this->alternateRouteItemTransformer = $alternateRouteItemTransformer;
    }

    public function configureOptions(OptionsResolver $options): void
    {
        // overwritten by actual path generator, if required.
    }
}
