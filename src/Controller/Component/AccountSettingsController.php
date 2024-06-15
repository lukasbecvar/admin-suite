<?php

namespace App\Controller\Component;

use App\Manager\AuthManager;
use App\Manager\UserManager;
use App\Manager\ErrorManager;
use App\Form\Settings\PasswordChangeForm;
use App\Form\Settings\UsernameChangeFormType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Form\Settings\ProfilePicChangeFormType;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Class AccountSettingsController
 *
 * Controller for the account settings page
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
     * Render the account settings table
     *
     * @return Response
     */
    #[Route('/account/settings', methods:['GET'], name: 'app_account_settings_table')]
    public function accountSettingsTable(): Response
    {
        // return account settings table
        return $this->render('component/account-settings/account-settings-table.twig', [
            'isAdmin' => $this->authManager->isLoggedInUserAdmin(),
            'userData' => $this->authManager->getLoggedUserRepository()
        ]);
    }

    /**
     * Render the change profile picture form
     *
     * @param Request $request The request object
     *
     * @return Response The response view
     */
    #[Route('/account/settings/change/picture', methods:['GET', 'POST'], name: 'app_account_settings_change_picture')]
    public function accountSettingsChangePicture(Request $request): Response
    {
        // create the profile picture change form
        $form = $this->createForm(ProfilePicChangeFormType::class);
        $form->handleRequest($request);

        // check if the form is submitted and valid
        if ($form->isSubmitted() && $form->isValid()) {
            // get image data
            $image = $form->get('profile-pic')->getData();

            if (!($image instanceof UploadedFile)) {
                $this->errorManager->handleError('error to get image data', 400);
            } else {
                // get image extension
                $extension = $image->getClientOriginalExtension();

                // check if file is image
                if ($extension != 'jpg' && $extension != 'jpeg' && $extension != 'png') {
                    $this->addFlash('error', 'Unsupported file type.');
                } else {
                    // get image content
                    $fileContents = file_get_contents($image);

                    // encode image
                    $imageCode = base64_encode((string) $fileContents);

                    // update profile picture
                    try {
                        $this->userManager->updateProfilePicture($this->authManager->getLoggedUserId(), $imageCode);

                        // redirect back to the account settings page
                        return $this->redirectToRoute('app_account_settings_table');
                    } catch (\Exception) {
                        $this->addFlash('error', 'An error occurred while changing the profile picture.');
                    }
                }
            }
        }

        // render the change profile picture form
        return $this->render('component/account-settings/forms/change-picture.twig', [
            'isAdmin' => $this->authManager->isLoggedInUserAdmin(),
            'userData' => $this->authManager->getLoggedUserRepository(),

            // profile picture change form
            'profilePicChangeForm' => $form->createView()
        ]);
    }

    /**
     * Render the change username form
     *
     * @param Request $request The request object
     *
     * @return Response The response view
     */
    #[Route('/account/settings/change/username', methods:['GET', 'POST'], name: 'app_account_settings_change_username')]
    public function accountSettingsChangeUsername(Request $request): Response
    {
        // create the username change form
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

                        // redirect back to the account settings page
                        return $this->redirectToRoute('app_account_settings_table');
                    } catch (\Exception) {
                        $this->addFlash('error', 'An error occurred while changing the username.');
                    }
                }
            }
        }

        // render the change username form
        return $this->render('component/account-settings/forms/chnage-username.twig', [
            'isAdmin' => $this->authManager->isLoggedInUserAdmin(),
            'userData' => $this->authManager->getLoggedUserRepository(),

            // username change form
            'usernameChangeForm' => $form->createView()
        ]);
    }

    /**
     * Render the change password form
     *
     * @param Request $request The request object
     *
     * @return Response The response view
     */
    #[Route('/account/settings/change/password', methods:['GET', 'POST'], name: 'app_account_settings_change_password')]
    public function accountSettingsChangePassword(Request $request): Response
    {
        // create the password change form
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

                    // redirect back to the account settings page
                    return $this->redirectToRoute('app_account_settings_table');
                } catch (\Exception) {
                    $this->addFlash('error', 'An error occurred while changing the password.');
                }
            }
        }

        // render the change password form
        return $this->render('component/account-settings/forms/change-password.twig', [
            'isAdmin' => $this->authManager->isLoggedInUserAdmin(),
            'userData' => $this->authManager->getLoggedUserRepository(),

            // password change form
            'passwordChangeForm' => $form->createView()
        ]);
    }
}