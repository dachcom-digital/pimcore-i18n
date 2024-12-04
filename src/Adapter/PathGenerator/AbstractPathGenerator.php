<?php

/*
 * This source file is available under two different licenses:
 *   - GNU General Public License version 3 (GPLv3)
 *   - DACHCOM Commercial License (DCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) DACHCOM.DIGITAL AG (https://www.dachcom-digital.com)
 * @license    GPLv3 and DCL
 */

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
