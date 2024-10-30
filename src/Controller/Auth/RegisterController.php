<?php

namespace App\Controller\Auth;

use Exception;
use App\Util\AppUtil;
use App\Manager\UserManager;
use App\Manager\AuthManager;
use App\Manager\ErrorManager;
use App\Form\Auth\RegistrationFormType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Class RegisterController
 *
 * Controller responsible for handling the user registration functionality
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
     * Handle the registration component
     *
     * @param Request $request The request object
     *
     * @return Response The registration view or redirect
     */
    #[Route('/register', methods:['GET', 'POST'], name: 'app_auth_register')]
    public function register(Request $request): Response
    {
        // check if user is already logged in
        if ($this->authManager->isUserLogedin()) {
            return $this->redirectToRoute('app_index');
        }

        // check if user database is empty
        if (!$this->userManager->isUsersEmpty()) {
            return $this->redirectToRoute('app_auth_login');
        }

        // create the registration form
        $form = $this->createForm(RegistrationFormType::class);
        $form->handleRequest($request);

        // check if the form is submitted and valid
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var \App\Entity\User $data get the form data */
            $data = $form->getData();

            // get the username and password
            $username = (string) $data->getUsername();
            $password = (string) $data->getPassword();

            // check if the username is already taken
            if ($this->userManager->checkIfUserExist($username)) {
                $this->addFlash('error', 'Username is already taken.');
            } elseif ($this->authManager->isUsernameBlocked($username)) {
                $this->addFlash('error', 'Username: ' . $username . ' is blocked.');
            } else {
                try {
                    // register the new user
                    $this->authManager->registerUser($username, $password);

                    // auto login after registration
                    $this->authManager->login($username, false);

                    // redirect to the login page
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

        // render the registration component view
        return $this->render('auth/register.twig', [
            'registrationForm' => $form->createView()
        ]);
    }
}
