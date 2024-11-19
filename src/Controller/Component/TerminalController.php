<?php

namespace App\Controller\Component;

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
    /**
     * Render terminal component page
     *
     * @return Response The terminal page view
     */
    #[Authorization(authorization: 'ADMIN')]
    #[Route('/terminal', methods:['GET'], name: 'app_terminal')]
    public function terminalPage(): Response
    {
        // return terminal component page view
        return $this->render('component/terminal/terminal.twig');
    }
}
