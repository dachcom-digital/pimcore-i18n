<?php

namespace I18nBundle\Exception;

final class ContextNotDefinedException extends \Exception
{
    public function __construct()
    {
        parent::__construct('context is not defined');
    }
}
