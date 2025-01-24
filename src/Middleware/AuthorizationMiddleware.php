<?php

namespace App\Middleware;

use ReflectionMethod;
use Twig\Environment;
use App\Manager\AuthManager;
use App\Annotation\Authorization;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Class AuthorizationMiddleware
 *
 * Middleware for checking user authorization before accessing admin routes
 *
 * @package App\Middleware
 */
class AuthorizationMiddleware
{
    private Environment $twig;
    private AuthManager $authManager;

    public function __construct(Environment $twig, AuthManager $authManager)
    {
        $this->twig = $twig;
        $this->authManager = $authManager;
    }

    /**
     * Check if user have permission to access to admin page
     *
     * @param RequestEvent $event The request event
     *
     * @return void
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        /** @var string $controller controller class path */
        $controller = $request->attributes->get('_controller');

        // split controller string into class and method
        list($controllerClass, $methodName) = explode('::', $controller);

        // check if method exists in controller class
        if (!method_exists($controllerClass, $methodName)) {
            return;
        }

        // get authorization attribute from method annotation
        $reflectionMethod = new ReflectionMethod($controllerClass, $methodName);
        $authorization = $reflectionMethod->getAttributes(Authorization::class);

        // check if annotation exists
        if (empty($authorization)) {
            $authorizationRequired = 'USER';
        } else {
            // get authorization attribute from annotation
            $authorizationAttribute = $authorization[0]->newInstance();
            $authorizationRequired = $authorizationAttribute->getAuthorization();
        }

        // check if user have permission to access to component
        if ($authorizationRequired == 'ADMIN' && !$this->authManager->isLoggedInUserAdmin()) {
            // return no permissions page
            $content = $this->twig->render('component/no-permissions.twig');
            $response = new Response($content, Response::HTTP_FORBIDDEN);
            $event->setResponse($response);
        }
    }
}
