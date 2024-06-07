<?php

namespace App\Controller\Auth;

use App\Manager\UserManager;
use App\Form\RegistrationFormType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Class RegisterController
 *
 * Controller to handle the registration of a new user.
 *
 * @package App\Controller\Auth
 */
class RegisterController extends AbstractController
{
    private UserManager $userManager;

    public function __construct(UserManager $userManager)
    {
        $this->userManager = $userManager;
    }

    /**
     * Handle the registration of a new user.
     *
     * @param Request $request The request object
     *
     * @return Response The response object
     */
    #[Route('/register', name: 'app_auth_register')]
    public function index(Request $request): Response
    {
        // create the registration form
        $form = $this->createForm(RegistrationFormType::class);
        $form->handleRequest($request);

        // check if the form is submitted and valid
        if ($form->isSubmitted() && $form->isValid()) {
            // get the form data
            /** @var \App\Entity\User $data */
            $data = $form->getData();

            // get the username and password
            $username = (string) $data->getUsername();
            $password = (string) $data->getPassword();

            // check if the username is already taken
            if ($this->userManager->checkIfUserExist($username)) {
                $this->addFlash('error', 'Username is already taken.');
            }

            // register the new user
            try {
                $this->userManager->registerUser($username, $password);

                // redirect to the login page
                return $this->redirectToRoute('app_auth_login');
            } catch (\Exception) {
                $this->addFlash('error', 'An error occurred while registering the new user.');
            }
        }

        // render the registration component view
        return $this->render('auth/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
}
