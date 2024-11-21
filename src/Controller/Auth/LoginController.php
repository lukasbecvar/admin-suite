<?php

namespace App\Controller\Auth;

use Exception;
use App\Util\AppUtil;
use App\Manager\AuthManager;
use App\Manager\UserManager;
use App\Manager\ErrorManager;
use App\Form\Auth\LoginFormType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Class LoginController
 *
 * Controller for login component
 *
 * @package App\Controller\Auth
 */
class LoginController extends AbstractController
{
    private AppUtil $appUtil;
    private AuthManager $authManager;
    private UserManager $userManager;
    private ErrorManager $errorManager;

    public function __construct(
        AppUtil $appUtil,
        AuthManager $authManager,
        UserManager $userManager,
        ErrorManager $errorManager
    ) {
        $this->appUtil = $appUtil;
        $this->authManager = $authManager;
        $this->userManager = $userManager;
        $this->errorManager = $errorManager;
    }

    /**
     * Handle user login component
     *
     * @param Request $request The request object
     *
     * @throws Exception Error to user login process
     *
     * @return Response The login view or redirect
     */
    #[Route('/login', methods:['GET', 'POST'], name: 'app_auth_login')]
    public function login(Request $request): Response
    {
        // check if user is already logged in
        if ($this->authManager->isUserLogedin()) {
            return $this->redirectToRoute('app_index');
        }

        // create registration form
        $form = $this->createForm(LoginFormType::class);
        $form->handleRequest($request);

        // check if form is submitted and valid
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var \App\Entity\User $data get the form data */
            $data = $form->getData();

            // get username and password
            $username = (string) $data->getUsername();
            $password = (string) $data->getPassword();

            // get remember me option status
            $remember = (bool) $form->get('remember')->getData();

            // check user credentials (if user can login)
            if ($this->authManager->canLogin($username, $password)) {
                try {
                    // login user
                    $this->authManager->login($username, $remember);

                    // redirect to app index page
                    return $this->redirectToRoute('app_index');
                } catch (Exception $e) {
                    // handle login error
                    if ($this->appUtil->isDevMode()) {
                        $this->errorManager->handleError(
                            message: 'login error: ' . $e->getMessage(),
                            code: Response::HTTP_INTERNAL_SERVER_ERROR
                        );
                    } else {
                        $this->addFlash('error', 'An error occurred while logging in.');
                    }
                }
            } else {
                $this->addFlash('error', 'Invalid username or password.');
            }
        }

        // render login page view
        return $this->render('auth/login.twig', [
            'isUsersEmpty' => $this->userManager->isUsersEmpty(),
            'loginForm' => $form->createView()
        ]);
    }
}
