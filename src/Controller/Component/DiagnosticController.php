<?php

namespace App\Controller\Component;

use App\Util\ServerUtil;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Class DiagnosticController
 *
 * Controller for diagnostic component
 *
 * @package App\Controller\Component
 */
class DiagnosticController extends AbstractController
{
    private ServerUtil $serverUtil;

    public function __construct(ServerUtil $serverUtil)
    {
        $this->serverUtil = $serverUtil;
    }

    /**
     * Render diagnostic page
     *
     * @return Response The diagnostic page view
     */
    #[Route('/diagnostic', methods:['GET'], name: 'app_diagnostic')]
    public function diagnosticPage(): Response
    {
        // get diagnostic data
        $diagnosticData = $this->serverUtil->getDiagnosticData();

        // return diagnostic page view
        return $this->render('component/diagnostic/diagnostics-page.twig', [
            'diagnosticData' => $diagnosticData
        ]);
    }
}
