<?php

namespace App\Middleware;

use App\Util\AppUtil;
use Psr\Log\LoggerInterface;
use Doctrine\DBAL\Connection;
use App\Manager\ErrorManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Class DatabaseOnlineMiddleware
 *
 * The middleware for checking the database connection
 *
 * @package App\Middleware
 */
class DatabaseOnlineMiddleware
{
    private AppUtil $appUtil;
    private Connection $connection;
    private LoggerInterface $logger;
    private ErrorManager $errorManager;

    public function __construct(AppUtil $appUtil, Connection $connection, LoggerInterface $logger, ErrorManager $errorManager)
    {
        $this->logger = $logger;
        $this->appUtil = $appUtil;
        $this->connection = $connection;
        $this->errorManager = $errorManager;
    }

    /**
     * Handle the database connection check
     *
     * @param RequestEvent $event The request event
     *
     * @throws \Exception If the database connection fails
     *
     * @return void
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        try {
            // select for connection try
            $this->connection->executeQuery('SELECT 1');
        } catch (\Exception $e) {
            // handle debug mode exception
            if ($this->appUtil->isDevMode()) {
                $this->errorManager->handleError('database connection error: ' . $e->getMessage(), 500);
            } else {
                $this->logger->error('database connection error: ' . $e->getMessage());
            }

            // render the internal error template
            $content = $this->errorManager->getErrorView(500);
            $response = new Response($content, 500);
            $event->setResponse($response);
        }
    }
}
