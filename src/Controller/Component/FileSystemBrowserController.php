<?php

namespace App\Controller\Component;

use App\Util\FilesystemUtil;
use App\Annotation\Authorization;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
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
    private FilesystemUtil $filesystemUtil;

    public function __construct(FilesystemUtil $filesystemUtil)
    {
        $this->filesystemUtil = $filesystemUtil;
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
        $filesystemList = $this->filesystemUtil->getFilesList($path);

        // render the file browser list
        return $this->render('component/file-system/file-system-browser.twig', [
            // file browser data
            'currentPath' => $path,
            'filesystemList' => $filesystemList
        ]);
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

        // check if file is executable
        if ($this->filesystemUtil->isFileExecutable($path)) {
            $fileContent = 'You cannot view the content of an binnary executable file';
        } else {
            // get file content
            $fileContent = $this->filesystemUtil->getFileContent($path);
        }

        // render the file browser view
        return $this->render('component/file-system/file-system-view.twig', [
            // file browser data
            'currentPath' => $path,
            'fileContent' => $fileContent
        ]);
    }
}
