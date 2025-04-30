<?php

namespace App\Middleware;

use App\Util\SecurityUtil;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class EscapeRequestDataMiddleware
 *
 * Middleware for escape request data (for security)
 *
 * @package App\Service\Middleware
 */
class EscapeRequestDataMiddleware
{
    private SecurityUtil $securityUtil;
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(SecurityUtil $securityUtil, UrlGeneratorInterface $urlGenerator)
    {
        $this->securityUtil = $securityUtil;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * Handle request data escaping
     *
     * @param RequestEvent $event The request event
     *
     * @return void
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        // excluded controller paths from escaping
        if (
            $request->getPathInfo() == $this->urlGenerator->generate('app_manager_database_console') ||
            $request->getPathInfo() == $this->urlGenerator->generate('app_file_system_create_save') ||
            $request->getPathInfo() == $this->urlGenerator->generate('app_file_system_edit')
        ) {
            return;
        }

        // get form data
        $requestData = $request->query->all() + $request->request->all();

        // escape all inputs
        array_walk_recursive($requestData, function (&$value) {
            $value = $this->securityUtil->escapeString($value);
        });

        // replace request data with escaped data
        $request->query->replace($requestData);
        $request->request->replace($requestData);
    }
}
