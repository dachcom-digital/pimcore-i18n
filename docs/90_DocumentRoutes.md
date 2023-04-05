# Document Routes

## Generating Routes in current request
To create document paths/urls in **current request** via Twig or PHP API
you may want to use the `_i18n` parameter builder:

### Twig
```twig
{# relative #}
{{ dump( i18n_entity_route(pimcore_document(46), {}, false) ) }}

{# absolute #}
{{ dump( i18n_entity_route(pimcore_document(46), {}, true) ) }}
```

### PHP
```php
use I18nBundle\Builder\RouteParameterBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

public function myAction(Request $request) 
{
    $document = \Pimcore\Model\Document::getById(20);
    
    $parameters = RouteParameterBuilder::buildForEntity($document);

    return $this->urlGenerator->generate('', $parameters, UrlGeneratorInterface::ABSOLUTE_URL);
}
```

## Generating Routes in CLI
To create document paths/urls in **headless** context:

### PHP
```php
use I18nBundle\Builder\RouteParameterBuilder;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

protected function execute(InputInterface $input, OutputInterface $output): int
{
    $document = \Pimcore\Model\Document::getById(20);
    
    $parameters = RouteParameterBuilder::buildForEntity($document);

    return $this->urlGenerator->generate('', $parameters, UrlGeneratorInterface::ABSOLUTE_URL);
}
```