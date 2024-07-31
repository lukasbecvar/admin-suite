<?php

namespace App\Controller\Component;

use App\Util\AppUtil;
use App\Manager\AuthManager;
use App\Manager\UserManager;
use App\Manager\ErrorManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Form\AccountSettings\PasswordChangeForm;
use App\Form\AccountSettings\UsernameChangeFormType;
use App\Form\AccountSettings\ProfilePicChangeFormType;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Class AccountSettingsController
 *
 * Controller for account settings component
 *
 * @package App\Controller\Component
 */
class AccountSettingsController extends AbstractController
{
    private AppUtil $appUtil;
    private UserManager $userManager;
    private AuthManager $authManager;
    private ErrorManager $errorManager;

    public function __construct(
        AppUtil $appUtil,
        UserManager $userManager,
        AuthManager $authManager,
        ErrorManager $errorManager
    ) {
        $this->appUtil = $appUtil;
        $this->userManager = $userManager;
        $this->authManager = $authManager;
        $this->errorManager = $errorManager;
    }

    /**
     * Render the account settings page
     *
     * @return Response The default account settings page
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
     * @return Response The response view with the change profile picture form
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

            // check if image is uploaded file instance
            if (!($image instanceof UploadedFile)) {
                $this->errorManager->handleError(
                    message: 'error to get image data',
                    code: Response::HTTP_BAD_REQUEST
                );
            } else {
                // get image extension
                $extension = $image->getClientOriginalExtension();

                // convert extension to lowercase
                $extension = strtolower($extension);

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
                        $this->userManager->updateProfilePicture(
                            userId: $this->authManager->getLoggedUserId(),
                            newProfilePicture: $imageCode
                        );

                        // redirect back to the account settings page
                        return $this->redirectToRoute('app_account_settings_table');
                    } catch (\Exception $e) {
                        // handle change profile picture error
                        if ($this->appUtil->isDevMode()) {
                            $this->errorManager->handleError(
                                message: 'change profile picture error: ' . $e->getMessage(),
                                code: Response::HTTP_INTERNAL_SERVER_ERROR
                            );
                        } else {
                            $this->addFlash('error', 'An error occurred while changing the profile picture.');
                        }
                    }
                }
            }
        }

        // render the change profile picture form
        return $this->render('component/account-settings/form/change-picture-form.twig', [
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
     * @return Response The response view with the change username form
     */
    #[Route('/account/settings/change/username', methods:['GET', 'POST'], name: 'app_account_settings_change_username')]
    public function accountSettingsChangeUsername(Request $request): Response
    {
        // create the username change form
        $form = $this->createForm(UsernameChangeFormType::class);
        $form->handleRequest($request);

        // check if the form is submitted and valid
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var \App\Entity\User $data form input data */
            $data = $form->getData();

            // get new username
            $username = $data->getUsername();

            // check if the new username is empty
            if ($username == null) {
                $this->errorManager->handleError(
                    message: 'error to get username from request data',
                    code: Response::HTTP_BAD_REQUEST
                );
            } else {
                // check if the username is already taken
                if ($this->userManager->checkIfUserExist($username)) {
                    $this->addFlash('error', 'Username is already taken.');
                } else {
                    // change the username
                    try {
                        $this->userManager->updateUsername(
                            userId: $this->authManager->getLoggedUserId(),
                            newUsername: $username
                        );

                        // redirect back to the account settings page
                        return $this->redirectToRoute('app_account_settings_table');
                    } catch (\Exception $e) {
                        // handle change username error
                        if ($this->appUtil->isDevMode()) {
                            $this->errorManager->handleError(
                                message: 'change username error: ' . $e->getMessage(),
                                code: Response::HTTP_INTERNAL_SERVER_ERROR
                            );
                        } else {
                            $this->addFlash('error', 'An error occurred while changing the username.');
                        }
                    }
                }
            }
        }

        // render the change username form
        return $this->render('component/account-settings/form/chnage-username-form.twig', [
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
     * @return Response The response view with the change password form
     */
    #[Route('/account/settings/change/password', methods:['GET', 'POST'], name: 'app_account_settings_change_password')]
    public function accountSettingsChangePassword(Request $request): Response
    {
        // create the password change form
        $form = $this->createForm(PasswordChangeForm::class);
        $form->handleRequest($request);

        // check if the form is submitted and valid
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var \App\Entity\User $data form input data */
            $data = $form->getData();

            // get new password
            $password = $data->getPassword();

            // check if the new password is empty
            if ($password == null) {
                $this->errorManager->handleError(
                    message: 'error to get password from request data',
                    code: Response::HTTP_BAD_REQUEST
                );
            } else {
                // change the password
                try {
                    $this->userManager->updatePassword(
                        userId: $this->authManager->getLoggedUserId(),
                        newPassword: $password
                    );

                    // redirect back to the account settings page
                    return $this->redirectToRoute('app_account_settings_table');
                } catch (\Exception $e) {
                    // handle change password error
                    if ($this->appUtil->isDevMode()) {
                        $this->errorManager->handleError(
                            message: 'change password error: ' . $e->getMessage(),
                            code: Response::HTTP_INTERNAL_SERVER_ERROR
                        );
                    } else {
                        $this->addFlash('error', 'An error occurred while changing the password.');
                    }
                }
            }
        }

        // render the change password form
        return $this->render('component/account-settings/form/change-password-form.twig', [
            'isAdmin' => $this->authManager->isLoggedInUserAdmin(),
            'userData' => $this->authManager->getLoggedUserRepository(),

            // password change form
            'passwordChangeForm' => $form->createView()
        ]);
    }
}
