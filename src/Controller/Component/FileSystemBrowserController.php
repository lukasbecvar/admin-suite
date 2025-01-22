<?php

namespace App\Controller\Component;

use Exception;
use App\Manager\LogManager;
use App\Util\FileSystemUtil;
use App\Manager\ErrorManager;
use App\Annotation\Authorization;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Class FileSystemBrowserController
 *
 * Controller for file system component
 *
 * @package App\Controller\Component
 */
class FileSystemBrowserController extends AbstractController
{
    private LogManager $logManager;
    private ErrorManager $errorManager;
    private FileSystemUtil $fileSystemUtil;

    public function __construct(LogManager $logManager, ErrorManager $errorManager, FileSystemUtil $fileSystemUtil)
    {
        $this->logManager = $logManager;
        $this->errorManager = $errorManager;
        $this->fileSystemUtil = $fileSystemUtil;
    }

    /**
     * Render filesystem browser page
     *
     * @param Request $request The request object
     *
     * @return Response The filesystem browser view response
     */
    #[Authorization(authorization: 'ADMIN')]
    #[Route('/filesystem', methods:['GET'], name: 'app_file_system_browser')]
    public function filesystemList(Request $request): Response
    {
        // get filesystem path
        $path = (string) $request->query->get('path', '/');

        // get list of file in current path
        $filesystemList = $this->fileSystemUtil->getFilesList($path);

        // render filesystem list view
        return $this->render('component/file-system/file-system-browser.twig', [
            'currentPath' => $path,
            'filesystemList' => $filesystemList
        ]);
    }

    /**
     * Render file view component
     *
     * @param Request $request The request object
     *
     * @return Response The file browser view response
     */
    #[Authorization(authorization: 'ADMIN')]
    #[Route('/filesystem/view', methods:['GET'], name: 'app_file_system_view')]
    public function filesystemView(Request $request): Response
    {
        // get browsing file path
        $path = (string) $request->query->get('path', '/');

        // default file content value
        $mediaType = null;
        $fileContent = null;

        try {
            // check if file is executable
            if ($this->fileSystemUtil->isFileExecutable($path)) {
                $fileContent = 'You cannot view the content of an binnary executable file';
            } else {
                // get media type of the file
                $mediaType = $this->fileSystemUtil->detectMediaType($path);

                // get file content
                if ($mediaType == 'non-mediafile') {
                    $fileContent = $this->fileSystemUtil->getFileContent($path);

                    // log file access
                    $this->logManager->log(
                        name: 'file-browser',
                        message: 'File: ' . $path . ' was accessed',
                        level: LogManager::LEVEL_INFO
                    );
                }
            }
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error to get file content: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        // render file browser page view
        return $this->render('component/file-system/file-system-view.twig', [
            'currentPath' => $path,
            'mediaType' => $mediaType,
            'fileContent' => $fileContent
        ]);
    }

    /**
     * Get media file resource
     *
     * @param Request $request The request object
     *
     * @return StreamedResponse|JsonResponse The media file resource content or JSON response with error message
     */
    #[Authorization(authorization: 'ADMIN')]
    #[Route('/filesystem/get/resource', methods:['GET'], name: 'app_file_system_get_resource')]
    public function filesystemGetResource(Request $request): StreamedResponse|JsonResponse
    {
        // get resource file path
        $path = (string) $request->query->get('path', '/');

        try {
            // get media file type
            $mediaType = $this->fileSystemUtil->detectMediaType($path);

            // check if resource is a media file
            if ($mediaType == 'non-mediafile') {
                return $this->json([
                    'status' => 'error',
                    'message' => 'The resource is not a media file'
                ], Response::HTTP_BAD_REQUEST);
            }

            // get resource content
            $resourceContent = $this->fileSystemUtil->getFileContent($path);

            // log file access
            $this->logManager->log(
                name: 'file-browser',
                message: 'File: ' . $path . ' was accessed',
                level: LogManager::LEVEL_INFO
            );

            // check if file content is empty
            if ($resourceContent == null) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'The resource content is null'
                ], Response::HTTP_BAD_REQUEST);
            }

            // create a streamed response file content
            $response = new StreamedResponse(function () use ($resourceContent) {
                echo $resourceContent;
            });

            // set response headers
            $response->headers->set('Content-Type', $mediaType);
            $response->headers->set('Content-Disposition', 'inline; filename="' . $path . '"');
            $response->headers->set('Cache-Control', 'public, max-age=3600');
            $response->headers->set('Accept-Ranges', 'bytes');
            $response->headers->set('Content-Length', (string) strlen($resourceContent));

            // return file content response
            return $response;
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error to get media file content: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
