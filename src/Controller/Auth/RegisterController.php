<?php

namespace App\Controller\Auth;

use Exception;
use App\Util\AppUtil;
use App\Manager\UserManager;
use App\Manager\AuthManager;
use App\Manager\ErrorManager;
use App\Annotation\CsrfProtection;
use App\Form\Auth\RegistrationFormType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Class RegisterController
 *
 * Controller for user register component
 *
 * @package App\Controller\Auth
 */
class RegisterController extends AbstractController
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
     * Handle registration component
     *
     * @param Request $request The request object
     *
     * @return Response The registration view or redirect
     */
    #[CsrfProtection(enabled: false)]
    #[Route('/register', methods: ['GET', 'POST'], name: 'app_auth_register')]
    public function register(Request $request): Response
    {
        // check if user is already logged in
        if ($this->authManager->isUserLogedin()) {
            return $this->redirectToRoute('app_index');
        }

        // check if user database is empty (registration is enabled only for first user)
        if (!$this->userManager->isUsersEmpty()) {
            return $this->redirectToRoute('app_auth_login');
        }

        // create registration form
        $form = $this->createForm(RegistrationFormType::class);
        $form->handleRequest($request);

        // check if form is submitted and valid
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var \App\Entity\User $data get the form data */
            $data = $form->getData();

            // get username and password from request
            $username = (string) $data->getUsername();
            $password = (string) $data->getPassword();

            // check if username is already used
            if ($this->userManager->checkIfUserExist($username)) {
                $this->addFlash('error', 'Username is already taken.');
            } elseif ($this->authManager->isUsernameBlocked($username)) {
                $this->addFlash('error', 'Username: ' . $username . ' is blocked.');
            } else {
                try {
                    // register new user
                    $this->authManager->registerUser($username, $password);

                    // login user to system
                    $this->authManager->login($username, false);

                    // redirect to dashboard page
                    return $this->redirectToRoute('app_dashboard');
                } catch (Exception $e) {
                    // handle register error
                    if ($this->appUtil->isDevMode()) {
                        $this->errorManager->handleError(
                            message: 'register error: ' . $e->getMessage(),
                            code: Response::HTTP_INTERNAL_SERVER_ERROR
                        );
                    } else {
                        $this->addFlash('error', 'An error occurred while registering the new user.');
                    }
                }
            }
        }

        // render registration page view
        return $this->render('auth/register.twig', [
            'registrationForm' => $form->createView()
        ]);
    }
}
