<?php

namespace I18nBundle\Manager;

use I18nBundle\Builder\ZoneBuilder;
use I18nBundle\Configuration\Configuration;
use I18nBundle\Model\I18nZoneInterface;
use I18nBundle\Resolver\PimcoreDocumentResolverInterface;
use Pimcore\Db\Connection;
use Pimcore\Http\Request\Resolver\EditmodeResolver;
use Pimcore\Http\Request\Resolver\SiteResolver;
use Pimcore\Http\RequestHelper;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\ClassDefinition\LinkGeneratorInterface;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Document;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Site;
use Pimcore\Routing\DocumentRoute;
use Pimcore\Tool\Frontend;
use Symfony\Component\HttpFoundation\Request;

class ZoneManager
{
    protected ZoneBuilder $zoneBuilder;
    protected Connection $db;
    protected RequestHelper $requestHelper;
    protected SiteResolver $siteResolver;
    protected PimcoreDocumentResolverInterface $pimcoreDocumentResolver;
    protected Configuration $configuration;
    protected EditmodeResolver $editModeResolver;
    protected Document\Service $documentService;
    protected array $nearestDocumentTypes;

    public function __construct(
        ZoneBuilder $zoneBuilder,
        RequestHelper $requestHelper,
        SiteResolver $siteResolver,
        Configuration $configuration,
        EditmodeResolver $editModeResolver,
        Document\Service $documentService,
        PimcoreDocumentResolverInterface $pimcoreDocumentResolver
    ) {
        $this->zoneBuilder = $zoneBuilder;
        $this->requestHelper = $requestHelper;
        $this->siteResolver = $siteResolver;
        $this->configuration = $configuration;
        $this->editModeResolver = $editModeResolver;
        $this->documentService = $documentService;
        $this->pimcoreDocumentResolver = $pimcoreDocumentResolver;
        $this->nearestDocumentTypes = ['page', 'snippet', 'hardlink', 'link', 'folder'];
    }

    public function buildZoneByRequest(Request $baseRequest, ?Document $baseDocument): ?I18nZoneInterface
    {
        $site = null;
        $editMode = $this->editModeResolver->isEditmode($baseRequest);

        if ($editMode === false) {
            if ($this->siteResolver->isSiteRequest($baseRequest)) {
                $site = $this->siteResolver->getSite();
            }
        } else {
            // in back end we don't have any site request, we need to fetch it via document
            $site = \Pimcore\Tool\Frontend::getSiteForDocument($baseDocument);
        }

        $pimcoreRequestSource = $baseRequest->attributes->get('pimcore_request_source');
        $routeParams = $baseRequest->attributes->get('_route_params', []);

        $requestSource = null;

        $pathGeneratorOptions = [];

        if ($pimcoreRequestSource === 'staticroute') {
            $requestSource = 'static_route';
            $pathGeneratorOptions = [
                'attributes' => $baseRequest->attributes->all(),
            ];
        } elseif (isset($routeParams['_i18n']) && $routeParams['_i18n'] === true) {
            $requestSource = 'symfony';
            $pathGeneratorOptions = [
                'attributes' => $baseRequest->attributes->all(),
            ];
        } elseif (str_contains($pimcoreRequestSource, 'document_')) {
            $requestSource = 'document';
            $pathGeneratorOptions = [
                'document' => $baseDocument,
            ];
        }

        if($requestSource === null && $baseRequest->attributes->has('routeDocument')) {
            /** @var DocumentRoute $routeDocument */
            $routeDocument = $baseRequest->attributes->get('routeDocument');
            $requestSource = 'document';
            $pathGeneratorOptions = [
                'document' => $routeDocument->getDocument(),
            ];
        }

        if ($requestSource === null) {
            return null;
        }

        $options = [
            'site'                         => $site,
            'request_source'               => $requestSource,
            'base_locale'                  => $baseRequest->getLocale(),
            'path_generator_options'       => $pathGeneratorOptions,
            'edit_mode'                    => $editMode,
            'is_frontend_request_by_admin' => $this->requestHelper->isFrontendRequestByAdmin($baseRequest),
        ];

        return $this->zoneBuilder->buildZone($options);
    }

    public function buildZoneByEntity(ElementInterface $entity, string $requestedLocale, array $routeParams = [], ?string $forcedDomain = null): I18nZoneInterface
    {
        if ($entity instanceof Document) {
            $options = $this->buildDocumentZoneOptions($entity, $forcedDomain);
        } elseif ($entity instanceof AbstractObject) {
            $options = $this->buildObjectZoneOptions($entity, $requestedLocale, $routeParams, $forcedDomain);
        } else {
            throw new \Exception('Cannot build zone for entity "%"', get_class($entity));
        }

        return $this->zoneBuilder->buildZone($options);

    }

    protected function buildDocumentZoneOptions(Document $entity, ?string $forcedDomain = null): array
    {
        $requestSource = 'document';

        // this is for DX only
        if ($forcedDomain !== null) {
            throw new \Exception('Forcing a domain context if requesting a zone for a document is forbidden (Site can be resolved by given document)');
        }

        $site = Frontend::getSiteForDocument($entity);

        return [
            'site'                         => $site,
            'request_source'               => $requestSource,
            'base_locale'                  => $entity->getProperty('language'),
            'path_generator_options'       => [
                'document' => $entity,
            ],
            'edit_mode'                    => false,
            'is_frontend_request_by_admin' => false,
        ];
    }

    protected function buildObjectZoneOptions(AbstractObject $entity, string $requestedLocale, array $pathGeneratorOptions = [], ?string $forcedDomain = null): array
    {
        $site = null;

        $linkGenerator = null;
        $requestSource = 'static_route';

        if ($forcedDomain !== null) {
            $site = Site::getByDomain($forcedDomain);
        }

        if ($entity instanceof Concrete) {
            $linkGenerator = $entity->getClass()?->getLinkGenerator();
        }

        if (!$linkGenerator instanceof LinkGeneratorInterface) {
            throw new \Exception(sprintf('Cannot build zone for DataObject %d: No LinkGenerator found', $entity->getId()));
        }

        return [
            'site'                         => $site,
            'request_source'               => $requestSource,
            'base_locale'                  => $requestedLocale,
            'path_generator_options'       => $pathGeneratorOptions,
            'edit_mode'                    => false,
            'is_frontend_request_by_admin' => false,
        ];

    }

}
