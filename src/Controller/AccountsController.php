<?php

namespace App\Controller;

use App\Manager\AuthManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Class DashboardController
 *
 * Controller to handle the accounts page.
 *
 * @package App\Controller
 */
class AccountsController extends AbstractController
{
    private AuthManager $authManager;

    public function __construct(AuthManager $authManager)
    {
        $this->authManager = $authManager;
    }

    /**
     * Handle the accounts page.
     *
     * @return Response The accounts view
     */
    #[Route('/accounts', methods:['GET'], name: 'app_accounts')]
    public function accounts(): Response
    {
        // return accounts view
        return $this->render('accounts.html.twig', [
            'is_admin' => $this->authManager->isLoggedInUserAdmin(),
            'user_data' => $this->authManager->getLoggedUserRepository()
        ]);
    }
}
