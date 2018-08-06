<?php

namespace I18nBundle\Event;

use Pimcore\Model\Document;
use Symfony\Component\EventDispatcher\Event;

class AlternateStaticRouteEvent extends Event
{
    protected $i18nList = [];

    protected $currentDocument = null;

    protected $currentLanguage = null;

    protected $currentCountry = null;

    protected $currentStaticRoute = null;

    protected $requestAttributes = null;

    protected $routes = [];

    /**
     * AlternateStaticRouteEvent constructor.
     *
     * @param $params
     */
    public function __construct(array $params)
    {
        $this->i18nList = $params['i18nList'];
        $this->currentDocument = $params['currentDocument'];
        $this->currentLanguage = $params['currentLanguage'];
        $this->currentCountry = $params['currentCountry'];
        $this->currentStaticRoute = $params['currentStaticRoute'];
        $this->requestAttributes = $params['requestAttributes'];
    }

    /**
     * @param array $routes
     */
    public function setRoutes(array $routes)
    {
        $this->routes = $routes;
    }

    /**
     * @return array
     */
    public function getRoutes()
    {
        return $this->routes;
    }

    /**
     * @return array
     */
    public function getI18nList()
    {
        return $this->i18nList;
    }

    /**
     * @return Document
     */
    public function getCurrentDocument()
    {
        return $this->currentDocument;
    }

    /**
     * @return string
     */
    public function getCurrentLanguage()
    {
        return $this->currentLanguage;
    }

    /**
     * @return string
     */
    public function getCurrentCountry()
    {
        return $this->currentCountry;
    }

    /**
     * @return  \Pimcore\Model\Staticroute $route
     */
    public function getCurrentStaticRoute()
    {
        return $this->currentStaticRoute;
    }

    /**
     * @return \Symfony\Component\HttpFoundation\ParameterBag
     */
    public function getRequestAttributes()
    {
        return $this->requestAttributes;
    }
}