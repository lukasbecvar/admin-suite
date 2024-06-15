<?php

namespace App\Controller\Component;

use App\Form\PasswordChangeForm;
use App\Form\UsernameChangeFormType;
use App\Manager\AuthManager;
use App\Manager\ErrorManager;
use App\Manager\UserManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class AccountSettingsController
 *
 * Controller for the account settings page.
 *
 * @package App\Controller\Component
 */
class AccountSettingsController extends AbstractController
{
    private UserManager $userManager;
    private AuthManager $authManager;
    private ErrorManager $errorManager;

    public function __construct(UserManager $userManager, AuthManager $authManager, ErrorManager $errorManager)
    {
        $this->userManager = $userManager;
        $this->authManager = $authManager;
        $this->errorManager = $errorManager;
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
        return $this->render('component/account-settings/settins-table.twig', [
            'is_admin' => $this->authManager->isLoggedInUserAdmin(),
            'user_data' => $this->authManager->getLoggedUserRepository()
        ]);
    }

    /**
     * Render the change username form.
     *
     * @param Request $request The request object
     *
     * @return Response The response view
     */
    #[Route('/account/settings/change/username', methods:['GET', 'POST'], name: 'app_account_settings_change_username')]
    public function accountSettingsChangeUsername(Request $request): Response
    {
        // create the registration form
        $form = $this->createForm(UsernameChangeFormType::class);
        $form->handleRequest($request);

        // check if the form is submitted and valid
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var \App\Entity\User $data */
            $data = $form->getData();

            // get new username
            $username = $data->getUsername();

            // check if the new username is empty
            if ($username == null) {
                $this->errorManager->handleError('error to get username from request data', 400);
            } else {
                // check if the username is already taken
                if ($this->userManager->checkIfUserExist($username)) {
                    $this->addFlash('error', 'Username is already taken.');
                } else {
                    // change the username
                    try {
                        $this->userManager->updateUsername($this->authManager->getLoggedUserId(), $username);

                        // redirect to the account settings page
                        return $this->redirectToRoute('app_account_settings_table');
                    } catch (\Exception) {
                        $this->addFlash('error', 'An error occurred while changing the username.');
                    }
                }
            }
        }

        return $this->render('component/account-settings/chnage-username.twig', [
            'is_admin' => $this->authManager->isLoggedInUserAdmin(),
            'user_data' => $this->authManager->getLoggedUserRepository(),

            'username_change_form' => $form->createView()
        ]);
    }

    #[Route('/account/settings/change/picture', methods:['GET', 'POST'], name: 'app_account_settings_change_picture')]
    public function accountSettingsChangePicture(): Response
    {
        return $this->render('component/account-settings/change-picture.twig', [
            'is_admin' => $this->authManager->isLoggedInUserAdmin(),
            'user_data' => $this->authManager->getLoggedUserRepository()
        ]);
    }

    /**
     * Render the change password form.
     *
     * @param Request $request The request object
     *
     * @return Response The response view
     */
    #[Route('/account/settings/change/password', methods:['GET', 'POST'], name: 'app_account_settings_change_password')]
    public function accountSettingsChangePassword(Request $request): Response
    {
        // create the registration form
        $form = $this->createForm(PasswordChangeForm::class);
        $form->handleRequest($request);

        // check if the form is submitted and valid
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var \App\Entity\User $data */
            $data = $form->getData();

            // get new password
            $password = $data->getPassword();

            // check if the new password is empty
            if ($password == null) {
                $this->errorManager->handleError('error to get password from request data', 400);
            } else {
                // change the password
                try {
                    $this->userManager->updatePassword($this->authManager->getLoggedUserId(), $password);

                    // redirect to the account settings page
                    return $this->redirectToRoute('app_account_settings_table');
                } catch (\Exception) {
                    $this->addFlash('error', 'An error occurred while changing the password.');
                }
            }
        }

        return $this->render('component/account-settings/change-password.twig', [
            'is_admin' => $this->authManager->isLoggedInUserAdmin(),
            'user_data' => $this->authManager->getLoggedUserRepository(),

            'password_change_form' => $form->createView()
        ]);
    }
}
