<?php

namespace App\Controller\Component;

use App\Manager\LogManager;
use App\Util\FileSystemUtil;
use App\Annotation\Authorization;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
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
    private FileSystemUtil $fileSystemUtil;

    public function __construct(LogManager $logManager, FileSystemUtil $fileSystemUtil)
    {
        $this->logManager = $logManager;
        $this->fileSystemUtil = $fileSystemUtil;
    }

    /**
     * Render filesystem browser page
     *
     * @param Request $request The request object
     *
     * @return Response The file list view response
     */
    #[Authorization(authorization: 'ADMIN')]
    #[Route('/filesystem', methods:['GET'], name: 'app_file_system_browser')]
    public function filesystemList(Request $request): Response
    {
        // get filesystem path
        $path = (string) $request->query->get('path', '/');

        // get filesystem list
        $filesystemList = $this->fileSystemUtil->getFilesList($path);

        // render filesystem list view
        return $this->render('component/file-system/file-system-browser.twig', [
            'currentPath' => $path,
            'filesystemList' => $filesystemList
        ]);
    }

    /**
     * Handle get media file resource
     *
     * @param Request $request The request object
     *
     * @return Response The file resource content
     */
    #[Authorization(authorization: 'ADMIN')]
    #[Route('/filesystem/get/resource', methods:['GET'], name: 'app_file_system_get_resource')]
    public function filesystemGetResource(Request $request): Response
    {
        // get resource file path
        $path = (string) $request->query->get('path', '/');

        // get media file type
        $mediaType = $this->fileSystemUtil->detectMediaType($path);

        // check if the resource is a media file
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

        // check if resource content is null
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
        // get browsing path
        $path = (string) $request->query->get('path', '/');

        // default file content value
        $mediaType = null;
        $fileContent = null;

        // check if file is executable
        if ($this->fileSystemUtil->isFileExecutable($path)) {
            $fileContent = 'You cannot view the content of an binnary executable file';
        } else {
            // get the media type of the file
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

        // render file browser page view
        return $this->render('component/file-system/file-system-view.twig', [
            'currentPath' => $path,
            'mediaType' => $mediaType,
            'fileContent' => $fileContent
        ]);
    }
}
