<?php

namespace I18nBundle\Exception;

final class ZoneDataAccessViolationException extends \Exception
{
    public function __construct()
    {
        parent::__construct('context is not defined');
    }
}
