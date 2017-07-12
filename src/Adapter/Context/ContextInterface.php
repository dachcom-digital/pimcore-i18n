<?php

namespace I18nBundle\Adapter\Context;

interface ContextInterface
{
    public function getLinkedLanguages($onlyShowRootLanguages = TRUE, $strictMode = FALSE);
}