<?php

namespace App\Controller;

use Throwable;
use App\Util\AppUtil;
use App\Manager\ErrorManager;
use App\Exception\AppErrorException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Class ErrorController
 *
 * The controller for handling errors
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
     * @return Response The error view
     */
    #[Route('/error', methods: ['GET'], name: 'app_error_by_code')]
    public function errorHandle(Request $request): Response
    {
        // get error code
        $code = $request->query->get('code', '404');

        // convert error code to string
        $code = strval($code);

        // get error code as integer
        $code = intval($code);

        // block handeling (maintenance, banned use only from app logic)
        if ($code == 'maintenance' or $code == 'banned' or $code == null) {
            $code = 'unknown';
        }

        // get response code
        if (!is_int($code)) {
            $responeCode = 500;
        } else {
            $responeCode = intval($code);
        }

        // return error view
        return new Response($this->errorManager->getErrorView($code), $responeCode);
    }

    /**
     * Show the error page by exception
     *
     * @param Throwable $exception The exception object
     *
     * @throws AppErrorException The exception object
     *
     * @return Response The error view
     */
    public function show(Throwable $exception): Response
    {
        // get exception code
        $statusCode = $exception instanceof HttpException
            ? $exception->getStatusCode() : Response::HTTP_INTERNAL_SERVER_ERROR;

        // handle errors in dev mode
        if ($this->appUtil->isDevMode()) {
            throw new AppErrorException($statusCode, $exception->getMessage(), $exception);
        }

        // return error view
        return new Response($this->errorManager->getErrorView($statusCode));
    }
}
