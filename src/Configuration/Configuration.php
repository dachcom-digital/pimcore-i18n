<?php

namespace I18nBundle\Configuration;

class Configuration
{
    protected array $config;

    public function setConfig(array $config = []): void
    {
        $this->config = $config;
    }

    public function getConfigNode(): array
    {
        return $this->config;
    }

    public function getConfig(string $slot): mixed
    {
        return $this->config[$slot];
    }
}
