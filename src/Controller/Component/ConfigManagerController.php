<?php

namespace App\Controller\Component;

use App\Util\JsonUtil;
use App\Manager\ErrorManager;
use App\Manager\ConfigManager;
use Symfony\Component\HttpFoundation\Request;
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
    private JsonUtil $jsonUtil;
    private ErrorManager $errorManager;
    private ConfigManager $configManager;

    public function __construct(JsonUtil $jsonUtil, ErrorManager $errorManager, ConfigManager $configManager)
    {
        $this->jsonUtil = $jsonUtil;
        $this->errorManager = $errorManager;
        $this->configManager = $configManager;
    }

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

    /**
     * Render suite configurations list
     *
     * @return Response The suite configurations list view
     */
    #[Route('/settings/suite', methods: ['GET'], name: 'app_suite_config_index')]
    public function suiteConfigsList(): Response
    {
        // get suite configs list
        $configs = $this->configManager->getSuiteConfigs();

        // render suite configurations list view
        return $this->render('component/config-manager/suite-settings/config-list.twig', [
            'configs' => $configs,
        ]);
    }

    /**
     * Show specific suite configuration file
     *
     * @param Request $request The request object
     *
     * @return Response The suite configuration file view
     */
    #[Route('/settings/suite/show', methods: ['GET'], name: 'app_suite_config_show')]
    public function suiteConfigShow(Request $request): Response
    {
        // get config file name from query string
        $filename = $request->query->get('filename');

        // get config file content from query string (for update error redirect)
        $content = $request->query->get('content', '');

        // check if filename parameter is set
        if ($filename === null) {
            $this->errorManager->handleError(
                message: 'filename cannot be empty',
                code: Response::HTTP_BAD_REQUEST
            );
        }

        // read config file content
        if ($content === '') {
            $content = $this->configManager->readConfig($filename);
        }

        // check if file exists
        if ($content === null) {
            $this->errorManager->handleError(
                message: 'config: ' . $filename . ' file not found',
                code: Response::HTTP_NOT_FOUND
            );
        }

        // check if file is custom (only custom configs can be edited)
        $isCustom = $this->configManager->isCustomConfig($filename);

        // render suite configuration file view/edit
        return $this->render($isCustom ? 'component/config-manager/suite-settings/config-edit.twig' : 'component/config-manager/suite-settings/config-view.twig', [
            'filename' => $filename,
            'content' => $content,
        ]);
    }

    /**
     * Create custom suite configuration file (copy default config file to root directory)
     *
     * @param Request $request The request object
     *
     * @return Response Redirect to config show page
     */
    #[Route('/settings/suite/create', methods: ['GET'], name: 'app_suite_config_create')]
    public function suiteConfigCreate(Request $request): Response
    {
        // get config filename parameter from query string
        $filename = $request->query->get('filename');

        // check if filename parameter is set
        if ($filename === null) {
            $this->errorManager->handleError(
                message: 'filename cannot be empty',
                code: Response::HTTP_BAD_REQUEST
            );
        }

        // copy config file to root directory
        $status = $this->configManager->copyConfigToRoot($filename);

        // check if copy operation was successful
        if (!$status) {
            $this->errorManager->handleError(
                message: 'failed to create custom config file',
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        // redirect to config show page
        return $this->redirectToRoute('app_suite_config_show', ['filename' => $filename]);
    }

    /**
     * Delete specific suite configuration file (reset to default)
     *
     * @param Request $request The request object
     *
     * @return Response Redirect to config index page
     */
    #[Route('/settings/suite/delete', name: 'app_suite_config_delete', methods: ['GET'])]
    public function suiteConfigDelete(Request $request): Response
    {
        // get config filename from query string
        $filename = $request->query->get('filename');

        // check if filename parameter is set
        if ($filename === null) {
            $this->errorManager->handleError(
                message: 'filename cannot be empty',
                code: Response::HTTP_BAD_REQUEST
            );
        }

        // delete config file
        $status = $this->configManager->deleteConfig($filename);

        // check if delete operation was successful
        if (!$status) {
            $this->errorManager->handleError(
                message: 'failed to reset config file',
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        // redirect back to config index page
        return $this->redirectToRoute('app_suite_config_index');
    }

    /**
     * Update suite configuration file (write to custom config path)
     *
     * @param Request $request The request object
     *
     * @return Response Redirect to config index page
     */
    #[Route('/settings/suite/update', methods: ['POST'], name: 'app_suite_config_update')]
    public function suiteConfigUpdate(Request $request): Response
    {
        // get config filename from query string
        $filename = $request->query->get('filename');

        // get new config content
        $content = $request->request->get('content', '');

        // check if filename parameter is set
        if ($filename === null || $content === null) {
            $this->errorManager->handleError(
                message: 'filename or content cannot be empty',
                code: Response::HTTP_BAD_REQUEST
            );
        }

        // check if content is valid JSON
        if (!is_string($content) || !$this->jsonUtil->isJson($content)) {
            $this->addFlash('error', 'Invalid JSON format');
            return $this->redirectToRoute('app_suite_config_show', ['filename' => $filename, 'content' => $content]);
        }

        // update config file content
        $status = $this->configManager->writeConfig($filename, $content);

        // check if write operation was successful
        if (!$status) {
            $this->errorManager->handleError(
                message: 'failed to update config: ' . $filename,
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        // redirect back to config index page
        return $this->redirectToRoute('app_suite_config_index');
    }
}
