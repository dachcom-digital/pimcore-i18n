<?php

namespace App\Controller;

use Pimcore\Controller\FrontendController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends FrontendController
{
    public function defaultAction(Request $request): Response
    {
        $publicCacheDirective = $request->headers->getCacheControlDirective('public');

        $response = null;
        if ($publicCacheDirective === true) {
            $response = new Response();
            $response->headers->addCacheControlDirective('public', true);
        }

        return $this->render('default/default.html.twig', [], $response);
    }

    public function languageSelectorAction(Request $request): Response
    {
        return $this->render('default/language-selector.html.twig');
    }
}
