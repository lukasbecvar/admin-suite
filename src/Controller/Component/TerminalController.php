<?php

namespace App\Controller\Component;

use App\Util\SessionUtil;
use App\Annotation\Authorization;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Class TerminalController
 *
 * Controller for terminal component
 *
 * @package App\Controller\Component
 */
class TerminalController extends AbstractController
{
    private SessionUtil $sessionUtil;

    public function __construct(SessionUtil $sessionUtil)
    {
        $this->sessionUtil = $sessionUtil;
    }

    /**
     * Render terminal component page
     *
     * @return Response The terminal page view
     */
    #[Authorization(authorization: 'ADMIN')]
    #[Route('/terminal', methods: ['GET'], name: 'app_terminal')]
    public function terminalPage(): Response
    {
        // set default terminal user
        $this->sessionUtil->setSession('terminal-user', 'root');

        // return terminal component page view
        return $this->render('component/terminal/terminal.twig');
    }
}
