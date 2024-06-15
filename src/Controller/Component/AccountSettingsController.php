<?php

namespace App\Controller\Component;

use App\Manager\AuthManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Class AccountSettingsController
 *
 * Controller for the account settings page.
 *
 * @package App\Controller\Component
 */
class AccountSettingsController extends AbstractController
{
    private AuthManager $authManager;

    public function __construct(AuthManager $authManager)
    {
        $this->authManager = $authManager;
    }

    /**
     * Render the account settings table.
     *
     * @return Response
     */
    #[Route('/account/settings', methods:['GET'], name: 'app_account_settings_table')]
    public function accountSettingsTable(): Response
    {
        // return dashboard view
        return $this->render('component/account/settins-table.twig', [
            'is_admin' => $this->authManager->isLoggedInUserAdmin(),
            'user_data' => $this->authManager->getLoggedUserRepository()
        ]);
    }
}
