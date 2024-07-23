<?php

namespace App\Controller\Component;

use App\Manager\AuthManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Class DatabaseBrowserController
 *
 * This controller is responsible for rendering the database browser page
 *
 * @package App\Controller\Component
 */
class DatabaseBrowserController extends AbstractController
{
    private AuthManager $authManager;

    public function __construct(AuthManager $authManager)
    {
        $this->authManager = $authManager;
    }

    /**
     * Renders the database browser page
     *
     * @return Response The rendered database browser page
     */
    #[Route('/manager/database', methods:['GET'], name: 'app_manager_database')]
    public function databaseBrowser(): Response
    {
        return $this->render('component/database-browser/database-list.twig', [
            'isAdmin' => true,
            'userData' => $this->authManager->getLoggedUserRepository(),
        ]);
    }
}
