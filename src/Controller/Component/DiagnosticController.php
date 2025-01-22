<?php

namespace App\Controller\Component;

use Exception;
use App\Util\ServerUtil;
use App\Manager\ErrorManager;
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
    private ErrorManager $errorManager;

    public function __construct(ServerUtil $serverUtil, ErrorManager $errorManager)
    {
        $this->serverUtil = $serverUtil;
        $this->errorManager = $errorManager;
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
        try {
            $diagnosticData = $this->serverUtil->getDiagnosticData();
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error to get diagnostic data: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        // return diagnostic page view
        return $this->render('component/diagnostic/diagnostics-page.twig', [
            'diagnosticData' => $diagnosticData
        ]);
    }
}
