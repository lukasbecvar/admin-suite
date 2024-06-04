<?php

namespace App\Middleware;

use App\Util\SecurityUtil;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Class EscapeRequestDataMiddleware
 *
 * The middleware for escaping request data
 *
 * @package App\Middleware
 */
class EscapeRequestDataMiddleware
{
    private SecurityUtil $securityUtil;

    public function __construct(SecurityUtil $securityUtil)
    {
        $this->securityUtil = $securityUtil;
    }

    /**
     * Handle the request data escaping
     *
     * @param RequestEvent $event The request event
     *
     * @return void
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $formData = [];

        // check if request is post or get
        if ($request->isMethod('POST') || $request->isMethod('GET')) {
            // get form data
            if ($request->isMethod('POST')) {
                $formData = $request->request->all();
            } elseif ($request->isMethod('GET')) {
                $formData = $request->query->all();
            }

            // escape all inputs
            array_walk_recursive($formData, function (&$value) {
                $value = $this->securityUtil->escapeString($value);
            });

            // update request data with escaped form data
            if ($request->isMethod('POST')) {
                $request->request->replace($formData);
            } elseif ($request->isMethod('GET')) {
                $request->query->replace($formData);
            }
        }
    }
}
