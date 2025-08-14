<?php

namespace App\Controller\Component;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Class ConfigManagerController
 *
 * Controller for config manager component
 *
 * @package App\Controller\Component
 */
class ConfigManagerController extends AbstractController
{
    /**
     * Render settings category selector page
     *
     * @return Response The settings category selector page view
     */
    #[Route('/settings', methods:['GET'], name: 'app_settings')]
    public function settingsSelector(): Response
    {
        // render settings category selector page view
        return $this->render('component/config-manager/settings-selector.twig');
    }
}
