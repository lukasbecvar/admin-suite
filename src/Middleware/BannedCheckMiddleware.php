<?php

namespace App\Middleware;

use Twig\Environment;
use App\Util\AppUtil;
use App\Manager\BanManager;
use App\Manager\AuthManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Class BannedCheckMiddleware
 *
 * Middleware to check if the user is banned
 *
 * @package App\Service\Middleware
 */
class BannedCheckMiddleware
{
    private AppUtil $appUtil;
    private Environment $twig;
    private BanManager $banManager;
    private AuthManager $authManager;

    public function __construct(
        AppUtil $appUtil,
        Environment $twig,
        BanManager $banManager,
        AuthManager $authManager
    ) {
        $this->twig = $twig;
        $this->appUtil = $appUtil;
        $this->banManager = $banManager;
        $this->authManager = $authManager;
    }

    /**
     * Check if user is banned
     *
     * @param RequestEvent $event The request event
     *
     * @return void
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        // check if user is loged in
        if ($this->authManager->isUserLogedin()) {
            // get user id
            $userId = $this->authManager->getLoggedUserId();

            // check if user is banned
            if ($this->banManager->isUserBanned($userId)) {
                // render the internal error template
                $content = $this->twig->render('error/error-banned.twig', [
                    'reason' => $this->banManager->getBanReason($userId),
                    'admin_contact' => $this->appUtil->getAdminContactEmail()
                ]);

                $response = new Response($content, Response::HTTP_FORBIDDEN);
                $event->setResponse($response);
            }
        }
    }
}
