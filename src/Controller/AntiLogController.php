<?php

namespace App\Controller;

use App\Manager\LogManager;
use App\Manager\AuthManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Class AntiLogController
 *
 * The controller for the anti log component
 *
 * @package App\Controller
 */
class AntiLogController extends AbstractController
{
    private LogManager $logManager;
    private AuthManager $authManager;

    public function __construct(LogManager $logManager, AuthManager $authManager)
    {
        $this->logManager = $logManager;
        $this->authManager = $authManager;
    }

    /**
     * Handle the anti log component
     *
     * @param Request $request The request object
     *
     * @return Response The redirect response
     */
    #[Route('/13378/antilog', methods:['GET'], name: 'app_anti_log_enable')]
    public function enableAntiLog(Request $request): Response
    {
        // check if user is logged in
        if (!$this->authManager->isUserLogedin()) {
            return $this->redirectToRoute('app_auth_login');
        }

        // check if user have admin permissions
        if (!$this->authManager->isLoggedInUserAdmin()) {
            return $this->render('component/no-permissions.twig', [
                'isAdmin' => $this->authManager->isLoggedInUserAdmin(),
                'userData' => $this->authManager->getLoggedUserRepository(),
            ]);
        }

        // get anti log state parameter
        $state = $request->query->get('state', 'enable');

        // check if anti log is enabled
        if ($state == 'disable') {
            if ($this->logManager->isAntiLogEnabled()) {
                // disable anti log
                $this->logManager->unSetAntiLog();
            }
        } else {
            if (!$this->logManager->isAntiLogEnabled()) {
                // enable anti log
                $this->logManager->setAntiLog();
            }
        }

        // redirect back to dashboard
        return $this->redirectToRoute('app_dashboard');
    }
}
