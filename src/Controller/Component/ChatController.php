<?php

namespace App\Controller\Component;

use App\Manager\AuthManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Class ChatController
 *
 * This controller is responsible for rendering the chat component
 *
 * @package App\Controller\Component
 */
class ChatController extends AbstractController
{
    private AuthManager $authManager;

    public function __construct(AuthManager $authManager)
    {
        $this->authManager = $authManager;
    }

    /**
     * Renders the chat component
     *
     * @return Response The rendered chat component
     */
    #[Route('/chat', methods:['GET'], name: 'app_chat')]
    public function chatComponent(): Response
    {
        // return diagnostic view
        return $this->render('component/chat/chat.twig', [
            'isAdmin' => $this->authManager->isLoggedInUserAdmin(),
            'userData' => $this->authManager->getLoggedUserRepository(),

        ]);
    }
}
