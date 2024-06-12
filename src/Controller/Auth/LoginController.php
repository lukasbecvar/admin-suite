<?php

namespace App\Controller\Auth;

use App\Form\LoginFormType;
use App\Manager\AuthManager;
use App\Manager\UserManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Class LoginController
 *
 * The controller for the login page
 *
 * @package App\Controller\Auth
 */
class LoginController extends AbstractController
{
    private AuthManager $authManager;
    private UserManager $userManager;

    public function __construct(AuthManager $authManager, UserManager $userManager)
    {
        $this->authManager = $authManager;
        $this->userManager = $userManager;
    }

    /**
     * Handle the user login component.
     *
     * @param Request $request The request object
     *
     * @return Response The login view
     */
    #[Route('/login', methods:['GET', 'POST'], name: 'app_auth_login')]
    public function login(Request $request): Response
    {
        // check if user is already logged in
        if ($this->authManager->isUserLogedin()) {
            return $this->redirectToRoute('app_index');
        }

        // create the registration form
        $form = $this->createForm(LoginFormType::class);
        $form->handleRequest($request);

        // check if the form is submitted and valid
        if ($form->isSubmitted() && $form->isValid()) {
            // get the form data
            /** @var \App\Entity\User $data */
            $data = $form->getData();

            // get the username and password
            $username = (string) $data->getUsername();
            $password = (string) $data->getPassword();

            // get the remember me option status
            $remember = (bool) $form->get('remember')->getData();

            // check user credentials
            if ($this->authManager->canLogin($username, $password)) {
                try {
                    // login the user
                    $this->authManager->login($username, $remember);

                    // redirect to the index page
                    return $this->redirectToRoute('app_index');
                } catch (\Exception) {
                    $this->addFlash('error', 'An error occurred while logging in.');
                }
            } else {
                $this->addFlash('error', 'Invalid username or password.');
            }
        }

        // render the registration component view
        return $this->render('auth/login.twig', [
            'is_users_empty' => $this->userManager->isUsersEmpty(),
            'login_form' => $form->createView()
        ]);
    }
}
