<?php

namespace I18nBundle\Event;

use Pimcore\Model\Document;
use Pimcore\Model\Staticroute;
use Symfony\Contracts\EventDispatcher\Event;
use Symfony\Component\HttpFoundation;

class AlternateStaticRouteEvent extends Event
{
    protected array $i18nList = [];
    protected Document $currentDocument;
    protected ?string $currentLanguage;
    protected ?string $currentCountry;
    protected ?Staticroute $currentStaticRoute;
    protected HttpFoundation\ParameterBag $requestAttributes;
    protected array $routes = [];

    public function __construct(array $params)
    {
        $this->i18nList = $params['i18nList'];
        $this->currentDocument = $params['currentDocument'];
        $this->currentLanguage = $params['currentLanguage'];
        $this->currentCountry = $params['currentCountry'];
        $this->currentStaticRoute = $params['currentStaticRoute'];
        $this->requestAttributes = $params['requestAttributes'];
    }

    public function setRoutes(array $routes): void
    {
        $this->routes = $routes;
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }

    public function getI18nList(): array
    {
        return $this->i18nList;
    }

    public function getCurrentDocument(): Document
    {
        return $this->currentDocument;
    }

    public function getCurrentLanguage(): ?string
    {
        return $this->currentLanguage;
    }

    public function getCurrentCountry(): ?string
    {
        return $this->currentCountry;
    }

    public function getCurrentStaticRoute(): ?Staticroute
    {
        return $this->currentStaticRoute;
    }

    public function getRequestAttributes(): HttpFoundation\ParameterBag
    {
        return $this->requestAttributes;
    }
}
