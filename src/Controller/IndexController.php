<?php

namespace App\Controller;

use App\Manager\AuthManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Class IndexController
 *
 * The controller for the index component
 *
 * @package App\Controller
 */
class IndexController extends AbstractController
{
    private AuthManager $authManager;

    public function __construct(AuthManager $authManager)
    {
        $this->authManager = $authManager;
    }

    /**
     * Handle the index component
     *
     * @return Response The redirect response to the main dashboard or login component
     */
    #[Route('/', methods:['GET'], name: 'app_index')]
    public function index(): Response
    {
        // check if user is logged in
        if (!$this->authManager->isUserLogedin()) {
            return $this->redirectToRoute('app_auth_login');
        }

        // redirect to main dashboard component
        return $this->redirectToRoute('app_dashboard');
    }
}
