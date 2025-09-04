<?php

namespace App\Controller;

use Throwable;
use App\Util\AppUtil;
use App\Manager\ErrorManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ErrorHandler\ErrorRenderer\HtmlErrorRenderer;

/**
 * Class ErrorController
 *
 * Controller for error handling
 *
 * @package App\Controller
 */
class ErrorController extends AbstractController
{
    private AppUtil $appUtil;
    private ErrorManager $errorManager;

    public function __construct(AppUtil $appUtil, ErrorManager $errorManager)
    {
        $this->appUtil = $appUtil;
        $this->errorManager = $errorManager;
    }

    /**
     * Handle errors by code
     *
     * @param Request $request The request object
     *
     * @return Response The error page view
     */
    #[Route('/error', methods: ['GET'], name: 'app_error_by_code')]
    public function errorHandle(Request $request): Response
    {
        // get error code
        $code = $request->query->get('code', '404');

        // block handling for special cases
        if ($code === 'maintenance' || $code === 'banned') {
            $code = 'unknown';
        }

        // convert to integer for response code
        if (!is_numeric($code)) {
            $responseCode = Response::HTTP_INTERNAL_SERVER_ERROR;
        } else {
            $responseCode = intval($code);
        }

        // return error view
        return new Response($this->errorManager->getErrorView($code), $responseCode);
    }

    /**
     * Show error page by exception
     *
     * @param Throwable $exception The exception object
     *
     * @return Response The error page view
     */
    public function showException(Throwable $exception): Response
    {
        // get exception code
        $statusCode = $exception instanceof HttpException
            ? $exception->getStatusCode() : Response::HTTP_INTERNAL_SERVER_ERROR;

        // handle error with symfony error handler in deb mode
        if ($this->appUtil->isDevMode()) {
            $errorRenderer = new HtmlErrorRenderer(true);
            $errorContent = $errorRenderer->render($exception)->getAsString();
            return new Response($errorContent, $statusCode);
        }

        // return error view
        return new Response($this->errorManager->getErrorView($statusCode));
    }
}
