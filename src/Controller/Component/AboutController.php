<?php

namespace App\Controller\Component;

use App\Manager\AuthManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Class AboutController
 *
 * Handles the rendering of the about page
 *
 * @package App\Controller\Component
 */
class AboutController extends AbstractController
{
    private AuthManager $authManager;

    public function __construct(AuthManager $authManager)
    {
        $this->authManager = $authManager;
    }

    /**
     * Renders the about page
     *
     * @return Response The rendered about page view
     */
    #[Route('/about', methods:['GET'], name: 'app_about')]
    public function about(): Response
    {
        // return about view
        return $this->render('component/about/info.twig', [
            'isAdmin' => $this->authManager->isLoggedInUserAdmin(),
            'userData' => $this->authManager->getLoggedUserRepository()
        ]);
    }
}
