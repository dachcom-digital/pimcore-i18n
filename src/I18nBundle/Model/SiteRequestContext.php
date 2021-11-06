<?php

namespace I18nBundle\Model;

class SiteRequestContext
{

    protected string $scheme;
    protected int $httpPort;
    protected int $httpsPort;
    protected string $domainUrl;
    protected string $host;
    protected string $none3wHost;

    public function __construct(
        string $scheme,
        int $httpPort,
        int $httpsPort,
        string $domainUrl,
        string $host,
        string $none3wHost,
    ) {
        $this->scheme = $scheme;
        $this->httpPort = $httpPort;
        $this->httpsPort = $httpsPort;
        $this->domainUrl = $domainUrl;
        $this->host = $host;
        $this->none3wHost = $none3wHost;
    }

    public function getScheme(): string
    {
        return $this->scheme;
    }

    public function getHttpPort(): int
    {
        return $this->httpPort;
    }

    public function getHttpsPort(): int
    {
        return $this->httpsPort;
    }

    public function getDomainUrl(): string
    {
        return $this->domainUrl;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getNone3wHost(): string
    {
        return $this->none3wHost;
    }
}
