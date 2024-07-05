<?php

namespace App\Controller\Component;

use App\Util\AppUtil;
use App\Util\ServerUtil;
use App\Manager\AuthManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Class DiagnosticController
 *
 * This controller is responsible for rendering the diagnostic page
 *
 * @package App\Controller\Component
 */
class DiagnosticController extends AbstractController
{
    private AppUtil $appUtil;
    private ServerUtil $serverUtil;
    private AuthManager $authManager;

    public function __construct(AppUtil $appUtil, ServerUtil $serverUtil, AuthManager $authManager)
    {
        $this->appUtil = $appUtil;
        $this->serverUtil = $serverUtil;
        $this->authManager = $authManager;
    }

    /**
     * Renders the diagnostic page
     *
     * @return Response The rendered diagnostic page
     */
    #[Route('/diagnostic', methods:['GET'], name: 'app_diagnostic')]
    public function diagnosticPage(): Response
    {
        // get diagnostic data
        $isSSL = $this->appUtil->isSsl();
        $isDevMode = $this->appUtil->isDevMode();
        $cpuUsage = $this->serverUtil->getCpuUsage();
        $webUsername = $this->serverUtil->getWebUsername();
        $isWebUserSudo = $this->serverUtil->isWebUserSudo();
        $ramUsage = $this->serverUtil->getRamUsage()['used'];
        $driveSpace = $this->serverUtil->getDriveUsagePercentage();
        $notInstalledRequirements = $this->serverUtil->getNotInstalledRequirements();

        // return diagnostic view
        return $this->render('component/diagnostic/diagnostics-page.twig', [
            'isAdmin' => $this->authManager->isLoggedInUserAdmin(),
            'userData' => $this->authManager->getLoggedUserRepository(),

            // diagnostic data
            'isSSL' => $isSSL,
            'cpuUsage' => $cpuUsage,
            'ramUsage' => $ramUsage,
            'isDevMode' => $isDevMode,
            'driveSpace' => $driveSpace,
            'webUsername' => $webUsername,
            'isWebUserSudo' => $isWebUserSudo,
            'notInstalledRequirements' => $notInstalledRequirements
        ]);
    }
}
