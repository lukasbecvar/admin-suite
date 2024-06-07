<?php

namespace App\Controller\Auth;

use App\Manager\AuthManager;
use App\Manager\ErrorManager;
use App\Manager\UserManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class LogoutController extends AbstractController
{
    private AuthManager $authManager;
    private ErrorManager $errorManager;

    public function __construct(AuthManager $authManager, ErrorManager $errorManager)
    {
        $this->authManager = $authManager;
        $this->errorManager = $errorManager;
    }

    #[Route('/logout', name: 'app_auth_logout')]
    public function logout(): ?Response
    {
        // check if user loggedin
        if ($this->authManager->isUserLogedin()) {
            $this->authManager->logout();
        }

        // verify user logout
        if (!$this->authManager->isUserLogedin()) {
            return $this->redirectToRoute('app_auth_login');
        } else {
            // handle logpout error
            $this->errorManager->handleError('logout error: unknown error in logout function', Response::HTTP_INTERNAL_SERVER_ERROR);
            return null;
        }
    }
}
