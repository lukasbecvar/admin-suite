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
        // return account settings table
        return $this->render('component/account/settins-table.twig', [
            'is_admin' => $this->authManager->isLoggedInUserAdmin(),
            'user_data' => $this->authManager->getLoggedUserRepository()
        ]);
    }

    #[Route('/account/settings/change/username', methods:['GET'], name: 'app_account_settings_change_username')]
    public function accountSettingsChangeUsername(): Response
    {
        return $this->render('component/account/chnage-username.twig', [
            'is_admin' => $this->authManager->isLoggedInUserAdmin(),
            'user_data' => $this->authManager->getLoggedUserRepository()
        ]);
    }

    #[Route('/account/settings/change/picture', methods:['GET'], name: 'app_account_settings_change_picture')]
    public function accountSettingsChangePicture(): Response
    {
        return $this->render('component/account/chnage-picture.twig', [
            'is_admin' => $this->authManager->isLoggedInUserAdmin(),
            'user_data' => $this->authManager->getLoggedUserRepository()
        ]);
    }

    #[Route('/account/settings/change/password', methods:['GET'], name: 'app_account_settings_change_password')]
    public function accountSettingsChangePassword(): Response
    {
        return $this->render('component/account/chnage-password.twig', [
            'is_admin' => $this->authManager->isLoggedInUserAdmin(),
            'user_data' => $this->authManager->getLoggedUserRepository()
        ]);
    }
}
