<?php

namespace I18nBundle\Exception;

final class RouteItemException extends \Exception
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}
