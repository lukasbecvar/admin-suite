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
 * This class is responsible for handling file system browser related operations
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
     * Returns a list of files and directories in the specified path
     *
     * @param Request $request The request object
     *
     * @return Response The file list view response
     */
    #[Authorization(authorization: 'ADMIN')]
    #[Route('/filesystem', methods:['GET'], name: 'app_file_system_browser')]
    public function filesystemList(Request $request): Response
    {
        // get current page from request query params
        $path = (string) $request->query->get('path', '/');

        // get filesystem list
        $filesystemList = $this->fileSystemUtil->getFilesList($path);

        // render the file browser list
        return $this->render('component/file-system/file-system-browser.twig', [
            // file browser data
            'currentPath' => $path,
            'filesystemList' => $filesystemList
        ]);
    }

    /**
     * Returns the contents of media files
     *
     * @param Request $request The request object
     *
     * @return Response The file browser view response
     */
    #[Authorization(authorization: 'ADMIN')]
    #[Route('/filesystem/get/resource', methods:['GET'], name: 'app_file_system_get_resource')]
    public function filesystemGetResource(Request $request): Response
    {
        // get the resource path
        $path = (string) $request->query->get('path', '/');

        // get the media type of the file
        $mediaType = $this->fileSystemUtil->detectMediaType($path);

        // check if the resource is a media file
        if ($mediaType == 'non-mediafile') {
            return $this->json([
                'status' => 'error',
                'message' => 'The resource is not a media file'
            ], Response::HTTP_BAD_REQUEST);
        }

        // get the resource content
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

        // create a StreamedResponse file content
        $response = new StreamedResponse(function () use ($resourceContent) {
            echo $resourceContent;
        });

        // set headers
        $response->headers->set('Content-Type', $mediaType);
        $response->headers->set('Content-Disposition', 'inline; filename="' . $path . '"');
        $response->headers->set('Cache-Control', 'public, max-age=3600');
        $response->headers->set('Accept-Ranges', 'bytes');
        $response->headers->set('Content-Length', (string) strlen($resourceContent));

        return $response;
    }

    /**
     * Returns the contents of a file
     *
     * @param Request $request The request object
     *
     * @return Response The file browser view response
     */
    #[Authorization(authorization: 'ADMIN')]
    #[Route('/filesystem/view', methods:['GET'], name: 'app_file_system_view')]
    public function filesystemView(Request $request): Response
    {
        // get the browsing path
        $path = (string) $request->query->get('path', '/');

        // default file content value
        $fileContent = null;
        $mediaType = null;

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

        // render the file browser view
        return $this->render('component/file-system/file-system-view.twig', [
            // file browser data
            'currentPath' => $path,
            'mediaType' => $mediaType,
            'fileContent' => $fileContent
        ]);
    }
}
