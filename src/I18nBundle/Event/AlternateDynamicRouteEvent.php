<?php

namespace I18nBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

class AlternateDynamicRouteEvent extends Event
{
    protected string $type;
    protected array $i18nList = [];
    protected ?string $currentLocale;
    protected array $attributes;
    protected array $routes = [];

    public function __construct(string $type, array $params)
    {
        $this->type = $type;
        $this->i18nList = $params['i18nList'];
        $this->currentLocale = $params['currentLocale'];
        $this->attributes = $params['attributes'];
    }

    public function setRoutes(array $routes): void
    {
        $this->routes = $routes;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }

    public function getI18nList(): array
    {
        return $this->i18nList;
    }

    public function getCurrentLocale(): ?string
    {
        return $this->currentLocale;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }
}
