<?php

namespace App\Middleware;

use ReflectionMethod;
use App\Manager\ErrorManager;
use App\Annotation\CsrfProtection;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Class CsrfProtectionMiddleware
 *
 * Middleware for CSRF protection POST requests
 * Controllers can disable the validation by attaching CsrfProtection attribute
 *
 * @package App\Middleware
 */
class CsrfProtectionMiddleware
{
    private ErrorManager $errorManager;
    private CsrfTokenManagerInterface $csrfTokenManager;

    public function __construct(ErrorManager $errorManager, CsrfTokenManagerInterface $csrfTokenManager)
    {
        $this->errorManager = $errorManager;
        $this->csrfTokenManager = $csrfTokenManager;
    }

    /**
     * Validate CSRF token
     *
     * @param RequestEvent $event The request event
     *
     * @return void
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if ($request->isMethodSafe()) {
            return;
        }

        // get controller atributes
        $controller = $request->attributes->get('_controller');

        // check if controller is valid
        if (!is_string($controller) || !str_contains($controller, '::')) {
            return;
        }
        [$class, $method] = explode('::', $controller, 2);
        if (!method_exists($class, $method)) {
            return;
        }

        // get method attributes
        $reflectionMethod = new ReflectionMethod($class, $method);
        $attributes = $reflectionMethod->getAttributes(CsrfProtection::class);

        // check csrf protection attribute
        if ($attributes !== []) {
            /** @var CsrfProtection $attribute */
            $attribute = $attributes[0]->newInstance();

            // check if csrf protection is enabled
            if (!$attribute->isEnabled()) {
                return;
            }
        }

        // get csrf token from request
        $tokenValue = $request->request->get('csrf_token');

        // check if csrf token type is string
        if (!is_string($tokenValue)) {
            $this->errorManager->handleError('invalid csrf token', Response::HTTP_FORBIDDEN);
        }

        // check if csrf token is valid
        $csrfToken = new CsrfToken('internal-csrf-token', $tokenValue);
        if (!$this->csrfTokenManager->isTokenValid($csrfToken)) {
            $this->errorManager->handleError('invalid csrf token', Response::HTTP_FORBIDDEN);
        }
    }
}
