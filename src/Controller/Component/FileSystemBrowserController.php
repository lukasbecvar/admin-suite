<?php

namespace App\Controller\Component;

use Exception;
use App\Util\AppUtil;
use App\Manager\LogManager;
use App\Util\FileSystemUtil;
use App\Util\FileUploadUtil;
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
    private AppUtil $appUtil;
    private LogManager $logManager;
    private ErrorManager $errorManager;
    private FileSystemUtil $fileSystemUtil;
    private FileUploadUtil $fileUploadUtil;

    public function __construct(
        AppUtil $appUtil,
        LogManager $logManager,
        ErrorManager $errorManager,
        FileSystemUtil $fileSystemUtil,
        FileUploadUtil $fileUploadUtil
    ) {
        $this->appUtil = $appUtil;
        $this->logManager = $logManager;
        $this->errorManager = $errorManager;
        $this->fileSystemUtil = $fileSystemUtil;
        $this->fileUploadUtil = $fileUploadUtil;
    }

    /**
     * Render filesystem browser page
     *
     * @param Request $request The request object
     *
     * @return Response The filesystem browser view response
     */
    #[Authorization(authorization: 'ADMIN')]
    #[Route('/filesystem', methods: ['GET'], name: 'app_file_system_browser')]
    public function filesystemList(Request $request): Response
    {
        // get filesystem path
        $path = (string) $request->query->get('path', '/');

        // get page
        $page = (int) $request->query->get('page', '1');

        // check if path exists
        if (!$this->fileSystemUtil->checkIfFileExist($path)) {
            return $this->render('component/file-system/file-system-error.twig', [
                'errorTitle' => 'Path Not Found',
                'errorMessage' => 'The path you are trying to access does not exist.',
                'details' => 'Path: ' . $path,
                'returnPath' => '/',
                'actionPath' => null,
                'actionText' => null,
                'actionIcon' => null
            ]);
        }

        // get limit per page
        $limitPerPage = (int) $this->appUtil->getEnvValue('LIMIT_CONTENT_PER_PAGE');

        // get files count
        $filesystemListCount = $this->fileSystemUtil->getFilesCount($path);

        // get list of file in current path
        $filesystemList = $this->fileSystemUtil->getFilesList($path, false, $page, $limitPerPage);

        // add information about editability for each file
        foreach ($filesystemList as &$file) {
            if (!$file['isDir']) {
                $file['isEditable'] = $this->fileSystemUtil->isFileEditable($file['path']);
            } else {
                $file['isEditable'] = false;
            }
        }

        // set last page
        $lastPage = 1;
        if ($filesystemListCount > $limitPerPage) {
            $lastPage = ceil($filesystemListCount / $limitPerPage);
        }

        // render filesystem list view
        return $this->render('component/file-system/file-system-browser.twig', [
            'filesystemList' => $filesystemList,
            'currentPath' => $path,
            'total' => $filesystemListCount,
            'limit' => $limitPerPage,
            'currentPage' => $page,
            'lastPage' => $lastPage
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
    #[Route('/filesystem/view', methods: ['GET'], name: 'app_file_system_view')]
    public function filesystemView(Request $request): Response
    {
        // get browsing file path
        $path = (string) $request->query->get('path', '/');

        // check if file exists
        if (!$this->fileSystemUtil->checkIfFileExist($path)) {
            // get directory path for return link
            $directoryPath = $this->fileSystemUtil->getDirname($path);
            if ($directoryPath === '.') {
                $directoryPath = '/';
            }

            // render error page for non-existent file
            return $this->render('component/file-system/file-system-error.twig', [
                'errorTitle' => 'File Not Found',
                'errorMessage' => 'The file you are trying to view does not exist.',
                'details' => 'Path: ' . $path,
                'returnPath' => $directoryPath,
                'actionPath' => null,
                'actionText' => null,
                'actionIcon' => null
            ]);
        }

        // default file content value
        $mediaType = null;
        $fileContent = null;
        $fileMetadata = null;

        // get start line and max lines from request (for pagination)
        $startLine = (int) $request->query->get('start_line', '1');
        $maxLines = (int) $request->query->get('max_lines', '1000'); // default to 1000 lines
        if ($startLine < 1) {
            $startLine = 1;
        }
        if ($maxLines < 1) {
            $maxLines = 1000;
        }

        try {
            // check if file is executable but not a shell script (or is a shell script)
            if ($this->fileSystemUtil->isFileExecutable($path) && !$this->fileSystemUtil->isShellScript($path)) {
                $fileContent = 'You cannot view the content of a binary executable file';
            } else {
                // get media type of the file
                $mediaType = $this->fileSystemUtil->detectMediaType($path);

                // get file content
                if ($mediaType == 'non-mediafile') {
                    // get file content with metadata
                    $fileData = $this->fileSystemUtil->getFileContent($path, $maxLines, $startLine);
                    $fileContent = $fileData['content'];
                    $fileMetadata = [
                        'totalLines' => $fileData['totalLines'],
                        'readLines' => $fileData['readLines'],
                        'isTruncated' => $fileData['isTruncated'],
                        'fileSize' => $fileData['fileSize'],
                        'readSize' => $fileData['readSize'],
                        'startLine' => $startLine,
                        'endLine' => $startLine + $fileData['readLines'] - 1,
                        'formattedSize' => $this->fileSystemUtil->formatFileSize($fileData['fileSize']),
                        'formattedReadSize' => $this->fileSystemUtil->formatFileSize($fileData['readSize']),
                        'maxLines' => $maxLines // store original max_lines value
                    ];

                    // log file access
                    $this->logManager->log(
                        name: 'file-browser',
                        message: 'file: ' . $path . ' was accessed',
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
            'fileContent' => $fileContent,
            'fileMetadata' => $fileMetadata
        ]);
    }

    /**
     * Render file create component
     *
     * @param Request $request The request object
     *
     * @return Response The file create view response
     */
    #[Authorization(authorization: 'ADMIN')]
    #[Route('/filesystem/create', methods: ['GET'], name: 'app_file_system_create')]
    public function filesystemCreate(Request $request): Response
    {
        // get directory path
        $path = (string) $request->query->get('path', '/');

        // ensure path is a directory
        if ($this->fileSystemUtil->checkIfFileExist($path) && !$this->fileSystemUtil->isPathDirectory($path)) {
            $path = $this->fileSystemUtil->getDirname($path);
        }

        // render file create view
        return $this->render('component/file-system/file-system-create.twig', [
            'currentPath' => $path
        ]);
    }

    /**
     * Render directory create component
     *
     * @param Request $request The request object
     *
     * @return Response The directory create view response
     */
    #[Authorization(authorization: 'ADMIN')]
    #[Route('/filesystem/create/directory', methods: ['GET'], name: 'app_file_system_create_directory')]
    public function filesystemCreateDirectory(Request $request): Response
    {
        // get directory path
        $path = (string) $request->query->get('path', '/');

        // ensure path is a directory
        if ($this->fileSystemUtil->checkIfFileExist($path) && !$this->fileSystemUtil->isPathDirectory($path)) {
            $path = $this->fileSystemUtil->getDirname($path);
        }

        // render directory create view
        return $this->render('component/file-system/file-system-create-directory.twig', [
            'currentPath' => $path
        ]);
    }

    /**
     * Create new file
     *
     * @param Request $request The request object
     *
     * @return Response The redirect response
     */
    #[Authorization(authorization: 'ADMIN')]
    #[Route('/filesystem/create/save', methods: ['POST'], name: 'app_file_system_create_save')]
    public function filesystemCreateSave(Request $request): Response
    {
        // get directory path, filename and content
        $directoryPath = (string) $request->request->get('directory', '/');
        $filename = (string) $request->request->get('filename', '');
        $content = (string) $request->request->get('content', '');

        // check if directory exists
        if (!$this->fileSystemUtil->checkIfFileExist($directoryPath)) {
            $this->errorManager->handleError(
                message: 'directory does not exist: ' . $directoryPath,
                code: Response::HTTP_BAD_REQUEST
            );
        }

        // validate filename
        if (empty($filename)) {
            $this->errorManager->handleError(
                message: 'filename cannot be empty',
                code: Response::HTTP_BAD_REQUEST
            );
        }

        // check that filename does not contain path separators
        if (str_contains($filename, '/')) {
            $this->errorManager->handleError(
                message: 'filename cannot contain path separators (/)',
                code: Response::HTTP_BAD_REQUEST
            );
        }

        // check filename length (max 255 characters)
        if (strlen($filename) > 255) {
            $this->errorManager->handleError(
                message: 'filename must be between 1 and 255 characters',
                code: Response::HTTP_BAD_REQUEST
            );
        }

        // build full file path
        $filePath = rtrim($directoryPath, '/') . '/' . $filename;

        try {
            // check if file already exists
            if ($this->fileSystemUtil->checkIfFileExist($filePath)) {
                // render error page instead of throwing an exception
                return $this->render('component/file-system/file-system-error.twig', [
                    'errorTitle' => 'File Already Exists',
                    'errorMessage' => 'The file you are trying to create already exists.',
                    'details' => 'Path: ' . $filePath,
                    'returnPath' => $directoryPath,
                    'actionPath' => $this->generateUrl('app_file_system_create', ['path' => $directoryPath]),
                    'actionText' => 'Try Different Name',
                    'actionIcon' => 'edit'
                ]);
            }

            // ensure parent directory exists
            $parentDir = $this->fileSystemUtil->getDirname($filePath);
            if (!$this->fileSystemUtil->checkIfFileExist($parentDir)) {
                $this->errorManager->handleError(
                    message: 'parent directory does not exist: ' . $parentDir,
                    code: Response::HTTP_BAD_REQUEST
                );
            }

            // decode HTML entities in content
            $content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5);

            // check if file is a shell script
            $isShellScript = str_ends_with($filePath, '.sh') || str_ends_with($filePath, '.bash');

            // for shell scripts, ensure we use LF line endings and have a shebang
            if ($isShellScript) {
                // convert all line endings to LF
                $content = str_replace("\r\n", "\n", $content);
                $content = str_replace("\r", "\n", $content);

                // ensure first line has shebang if it's a shell script
                if (!empty($content) && !preg_match('/^#!/', $content)) {
                    // add shebang if it doesn't exist
                    $content = "#!/bin/bash\n" . $content;
                }
            }

            // save file content
            $result = $this->fileSystemUtil->saveFileContent($filePath, $content);

            // check if save was successful
            if (!$result) {
                $this->errorManager->handleError(
                    message: 'failed to create file',
                    code: Response::HTTP_INTERNAL_SERVER_ERROR
                );
            }

            // make shell scripts executable
            if ($isShellScript) {
                $makeScriptExecutable = $this->fileSystemUtil->makeScriptExecutable($filePath);
                if (!$makeScriptExecutable) {
                    $this->errorManager->handleError(
                        message: 'failed to make script executable',
                        code: Response::HTTP_INTERNAL_SERVER_ERROR
                    );
                }
            }

            // log file creation
            $this->logManager->log(
                name: 'file-browser',
                message: 'file: ' . $filePath . ' was created',
                level: LogManager::LEVEL_INFO
            );

            // add flash message
            $this->addFlash('success', 'File created successfully');

            // redirect to file view
            return $this->redirectToRoute('app_file_system_view', ['path' => $filePath]);
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error to create file: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Create new directory
     *
     * @param Request $request The request object
     *
     * @return Response The redirect response
     */
    #[Authorization(authorization: 'ADMIN')]
    #[Route('/filesystem/create/directory/save', methods: ['POST'], name: 'app_file_system_create_directory_save')]
    public function filesystemCreateDirectorySave(Request $request): Response
    {
        // get directory path and new directory name
        $parentPath = (string) $request->request->get('directory', '/');
        $directoryName = (string) $request->request->get('directoryname', '');

        // check if parent directory exists
        if (!$this->fileSystemUtil->checkIfFileExist($parentPath)) {
            $this->errorManager->handleError(
                message: 'parent directory does not exist: ' . $parentPath,
                code: Response::HTTP_BAD_REQUEST
            );
        }

        // validate directory name
        if (empty($directoryName)) {
            $this->errorManager->handleError(
                message: 'directory name cannot be empty',
                code: Response::HTTP_BAD_REQUEST
            );
        }

        // check that directory name does not contain path separators
        if (str_contains($directoryName, '/')) {
            $this->errorManager->handleError(
                message: 'directory name cannot contain path separators (/)',
                code: Response::HTTP_BAD_REQUEST
            );
        }

        // check directory name length (max 255 characters)
        if (strlen($directoryName) > 255) {
            $this->errorManager->handleError(
                message: 'directory name must be between 1 and 255 characters',
                code: Response::HTTP_BAD_REQUEST
            );
        }

        // build full directory path
        $directoryPath = rtrim($parentPath, '/') . '/' . $directoryName;

        try {
            // check if directory already exists
            if ($this->fileSystemUtil->checkIfFileExist($directoryPath)) {
                // render error page instead of throwing an exception
                return $this->render('component/file-system/file-system-error.twig', [
                    'errorTitle' => 'Directory Already Exists',
                    'errorMessage' => 'The directory you are trying to create already exists.',
                    'details' => 'Path: ' . $directoryPath,
                    'returnPath' => $parentPath,
                    'actionPath' => $this->generateUrl('app_file_system_create_directory', ['path' => $parentPath]),
                    'actionText' => 'Try Different Name',
                    'actionIcon' => 'edit'
                ]);
            }

            // create directory
            $result = $this->fileSystemUtil->createDirectory($directoryPath);

            // check if creation was successful
            if (!$result) {
                $this->errorManager->handleError(
                    message: 'failed to create directory',
                    code: Response::HTTP_INTERNAL_SERVER_ERROR
                );
            }

            // log directory creation
            $this->logManager->log(
                name: 'file-browser',
                message: 'directory: ' . $directoryPath . ' was created',
                level: LogManager::LEVEL_INFO
            );

            // add flash message
            $this->addFlash('success', 'Directory created successfully');

            // redirect to directory
            return $this->redirectToRoute('app_file_system_browser', ['path' => $directoryPath]);
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error to create directory: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Render file rename component
     *
     * @param Request $request The request object
     *
     * @return Response The file rename view response
     */
    #[Authorization(authorization: 'ADMIN')]
    #[Route('/filesystem/rename', methods: ['GET'], name: 'app_file_system_rename')]
    public function filesystemRename(Request $request): Response
    {
        // get file path
        $path = (string) $request->query->get('path', '/');

        // check if path exists
        if (!$this->fileSystemUtil->checkIfFileExist($path)) {
            $this->errorManager->handleError(
                message: 'error renaming file: ' . $path . ' does not exist',
                code: Response::HTTP_BAD_REQUEST
            );
        }

        // get current name and directory path
        $isDirectory = $this->fileSystemUtil->isPathDirectory($path);
        $directoryPath = dirname($path);
        if ($directoryPath === '.') {
            $directoryPath = '/';
        }

        // always use basename for the display name
        $currentName = $this->fileSystemUtil->getBasename($path);

        // render file rename view
        return $this->render('component/file-system/file-system-rename.twig', [
            'currentPath' => $path,
            'currentName' => $currentName,
            'directoryPath' => $directoryPath,
            'isDirectory' => $isDirectory
        ]);
    }

    /**
     * Rename file or directory
     *
     * @param Request $request The request object
     *
     * @return Response The redirect response
     */
    #[Authorization(authorization: 'ADMIN')]
    #[Route('/filesystem/rename/save', methods: ['POST'], name: 'app_file_system_rename_save')]
    public function filesystemRenameSave(Request $request): Response
    {
        // get old path and new name
        $oldPath = (string) $request->request->get('path', '/');
        $newName = (string) $request->request->get('newName', '');

        // additional validation for the old path
        if (!$this->fileSystemUtil->checkIfFileExist($oldPath)) {
            $this->errorManager->handleError(
                message: 'the file or directory to rename does not exist: ' . $oldPath,
                code: Response::HTTP_BAD_REQUEST
            );
        }

        // get directory path
        $directoryPath = dirname($oldPath);
        if ($directoryPath === '.') {
            $directoryPath = '/';
        }

        // validate new name
        if (empty($newName)) {
            $this->errorManager->handleError(
                message: 'new name cannot be empty',
                code: Response::HTTP_BAD_REQUEST
            );
        }

        // validate that the new name does not contain path separators
        if (str_contains($newName, '/')) {
            $this->errorManager->handleError(
                message: 'new name cannot contain path separators (/).',
                code: Response::HTTP_BAD_REQUEST
            );
        }

        // check new name length (max 255 characters)
        if (strlen($newName) > 255) {
            $this->errorManager->handleError(
                message: 'new name must be between 1 and 255 characters',
                code: Response::HTTP_BAD_REQUEST
            );
        }

        try {
            // always construct new path by combining directory path with new name
            $newPath = rtrim($directoryPath, '/') . '/' . $newName;

            // rename file or directory
            $result = $this->fileSystemUtil->renameFileOrDirectory($oldPath, $newPath);

            // check if rename was successful
            if (!$result) {
                $this->errorManager->handleError(
                    message: 'failed to rename file or directory',
                    code: Response::HTTP_INTERNAL_SERVER_ERROR
                );
            }

            // log file rename
            $this->logManager->log(
                name: 'file-browser',
                message: 'file: ' . $oldPath . ' was renamed to ' . $newName,
                level: LogManager::LEVEL_INFO
            );

            // add flash message
            $this->addFlash('success', ($this->fileSystemUtil->isPathDirectory($oldPath) ? 'Directory' : 'File') . ' renamed successfully');

            // redirect to directory
            return $this->redirectToRoute('app_file_system_browser', ['path' => $directoryPath]);
        } catch (Exception $e) {
            // check if error is about destination already existing
            if (str_contains($e->getMessage(), 'Destination already exists')) {
                // render error page instead of throwing exception
                return $this->render('component/file-system/file-system-error.twig', [
                    'errorTitle' => 'Destination Already Exists',
                    'errorMessage' => 'The destination path already exists. Please choose a different name.',
                    'details' => $e->getMessage(),
                    'returnPath' => $directoryPath,
                    'actionPath' => $this->generateUrl('app_file_system_rename', ['path' => $oldPath]),
                    'actionText' => 'Try Different Name',
                    'actionIcon' => 'edit'
                ]);
            }

            // handle other errors
            $this->errorManager->handleError(
                message: 'error to rename file or directory: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get media file resource with Range support
     *
     * @param Request $request The request object
     *
     * @return StreamedResponse|JsonResponse The media file resource content or JSON response with error message
     */
    #[Authorization(authorization: 'ADMIN')]
    #[Route('/filesystem/get/resource', methods: ['GET'], name: 'app_file_system_get_resource')]
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

            // get file size
            $fileSize = $this->fileSystemUtil->getFileSize($path);

            // log file access
            $this->logManager->log(
                name: 'file-browser',
                message: 'file: ' . $path . ' was accessed as media resource',
                level: LogManager::LEVEL_INFO
            );

            // handle range requests for video/audio streaming
            $rangeHeader = $request->headers->get('Range');
            $start = 0;
            $end = $fileSize - 1;
            $length = $fileSize;
            $statusCode = Response::HTTP_OK;

            if ($rangeHeader) {
                // parse range header (e.g., "bytes=0-1023", "bytes=1024-", "bytes=-1024")
                if (preg_match('/bytes=(\d*)-(\d*)/', $rangeHeader, $matches)) {
                    $rangeStart = $matches[1];
                    $rangeEnd = $matches[2];

                    // handle different range formats
                    if ($rangeStart !== '' && $rangeEnd !== '') {
                        // bytes=start-end
                        $start = (int) $rangeStart;
                        $end = (int) $rangeEnd;
                    } elseif ($rangeStart !== '' && $rangeEnd === '') {
                        // bytes=start- (from start to end of file)
                        $start = (int) $rangeStart;
                        $end = $fileSize - 1;
                    } elseif ($rangeStart === '' && $rangeEnd !== '') {
                        // bytes=-end (last N bytes)
                        $start = $fileSize - (int) $rangeEnd;
                        $end = $fileSize - 1;
                    }

                    // validate and fix range bounds
                    $start = max(0, min($start, $fileSize - 1));
                    $end = max($start, min($end, $fileSize - 1));

                    $length = $end - $start + 1;
                    $statusCode = Response::HTTP_PARTIAL_CONTENT;
                }
            }

            // create streamed response with range support
            $response = new StreamedResponse(function () use ($path, $start, $length) {
                $this->fileUploadUtil->streamFileRange($path, $start, $length);
            }, $statusCode);

            // set essential headers for video streaming
            $response->headers->set('Content-Type', $mediaType);
            $response->headers->set('Accept-Ranges', 'bytes');
            $response->headers->set('Content-Length', (string) $length);

            // better cache headers for video streaming
            $response->headers->set('Cache-Control', 'public, max-age=3600'); // cache for 1 hour
            $response->headers->set('Expires', gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT');

            // add ETag for better caching
            $etag = md5($path . $this->fileSystemUtil->getMtime($path) . $fileSize);
            $response->headers->set('ETag', '"' . $etag . '"');

            // add Last-Modified header
            $lastModTime = $this->fileSystemUtil->getMtime($path);
            $response->headers->set('Last-Modified', gmdate('D, d M Y H:i:s', $lastModTime) . ' GMT');

            if ($statusCode === Response::HTTP_PARTIAL_CONTENT) {
                $response->headers->set('Content-Range', "bytes $start-$end/$fileSize");
            }

            // set filename for inline display
            $filename = $this->fileSystemUtil->getBasename($path);
            $response->headers->set('Content-Disposition', 'inline; filename="' . $filename . '"');

            // return reponse
            return $response;
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error to get media file content: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Render file edit component
     *
     * @param Request $request The request object
     *
     * @return Response The file editor view response
     */
    #[Authorization(authorization: 'ADMIN')]
    #[Route('/filesystem/edit', methods: ['GET'], name: 'app_file_system_edit')]
    public function filesystemEdit(Request $request): Response
    {
        // get file path
        $path = (string) $request->query->get('path', '/');

        // default file content value
        $fileContent = null;

        try {
            if ($this->fileSystemUtil->isFileExecutable($path) && !$this->fileSystemUtil->isShellScript($path)) {
                $this->errorManager->handleError(
                    message: 'you cannot edit an executable file (except shell scripts)',
                    code: Response::HTTP_BAD_REQUEST
                );
            }

            // get media type of the file
            $mediaType = $this->fileSystemUtil->detectMediaType($path);

            // check if file is a media file
            if ($mediaType != 'non-mediafile') {
                $this->errorManager->handleError(
                    message: 'you cannot edit a media file',
                    code: Response::HTTP_BAD_REQUEST
                );
            }

            // get full file content without pagination for editing
            $fileContent = $this->fileSystemUtil->getFullFileContent($path);

            // log file access
            $this->logManager->log(
                name: 'file-browser',
                message: 'file: ' . $path . ' was opened for editing',
                level: LogManager::LEVEL_INFO
            );
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error to get file content: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        // render file editor view
        return $this->render('component/file-system/file-system-edit.twig', [
            'currentPath' => $path,
            'fileContent' => $fileContent
        ]);
    }

    /**
     * Save file content
     *
     * @param Request $request The request object
     *
     * @return Response The redirect response
     */
    #[Authorization(authorization: 'ADMIN')]
    #[Route('/filesystem/save', methods: ['POST'], name: 'app_file_system_save')]
    public function filesystemSave(Request $request): Response
    {
        // get file path and content (raw, without escaping)
        $path = (string) $request->request->get('path', '/');
        $content = (string) $request->request->get('content', '');

        try {
            if ($this->fileSystemUtil->isFileExecutable($path) && !$this->fileSystemUtil->isShellScript($path)) {
                $this->errorManager->handleError(
                    message: 'you cannot edit an executable file (except shell scripts)',
                    code: Response::HTTP_BAD_REQUEST
                );
            }

            // get media type of the file
            $mediaType = $this->fileSystemUtil->detectMediaType($path);

            // check if file is a media file
            if ($mediaType != 'non-mediafile') {
                $this->errorManager->handleError(
                    message: 'you cannot edit a media file',
                    code: Response::HTTP_BAD_REQUEST
                );
            }

            // decode HTML entities in content
            $content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5);

            // save file content
            $result = $this->fileSystemUtil->saveFileContent($path, $content);

            // check if save was successful
            if (!$result) {
                $this->errorManager->handleError(
                    message: 'failed to save file content',
                    code: Response::HTTP_INTERNAL_SERVER_ERROR
                );
            }

            // log file save
            $this->logManager->log(
                name: 'file-browser',
                message: 'file: ' . $path . ' was saved',
                level: LogManager::LEVEL_INFO
            );

            // add flash message
            $this->addFlash('success', 'File saved successfully');

            // redirect to file view
            return $this->redirectToRoute('app_file_system_view', ['path' => $path]);
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error to save file content: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Delete file or directory
     *
     * @param Request $request The request object
     *
     * @return Response The redirect response
     */
    #[Authorization(authorization: 'ADMIN')]
    #[Route('/filesystem/delete', methods: ['POST'], name: 'app_file_system_delete')]
    public function filesystemDelete(Request $request): Response
    {
        // get file path
        $path = (string) $request->request->get('path', '/');

        // additional validation for the path
        if (!$this->fileSystemUtil->checkIfFileExist($path)) {
            $this->errorManager->handleError(
                message: 'the file or directory to delete does not exist: ' . $path,
                code: Response::HTTP_BAD_REQUEST
            );
        }

        // get return path (directory containing the file)
        $directoryPath = $this->fileSystemUtil->getDirname($path);
        if ($directoryPath === '.') {
            $directoryPath = '/';
        }

        try {
            // delete file or directory (always recursive for directories)
            $result = $this->fileSystemUtil->deleteFileOrDirectory($path);

            // check if delete was successful
            if (!$result) {
                $this->errorManager->handleError(
                    message: 'failed to delete file or directory',
                    code: Response::HTTP_INTERNAL_SERVER_ERROR
                );
            }

            // log file deletion
            $this->logManager->log(
                name: 'file-browser',
                message: 'file: ' . $path . ' was deleted',
                level: LogManager::LEVEL_INFO
            );

            // add flash message
            $this->addFlash('success', ($this->fileSystemUtil->isPathDirectory($path) ? 'Directory' : 'File') . ' deleted successfully');

            // redirect to directory
            return $this->redirectToRoute('app_file_system_browser', ['path' => $directoryPath]);
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error to delete file or directory: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Render file/directory move component
     *
     * @param Request $request The request object
     *
     * @return Response The file move view response
     */
    #[Authorization(authorization: 'ADMIN')]
    #[Route('/filesystem/move', methods: ['GET'], name: 'app_file_system_move')]
    public function filesystemMove(Request $request): Response
    {
        // get file path
        $path = (string) $request->query->get('path', '/');

        // check if path exists
        if (!$this->fileSystemUtil->checkIfFileExist($path)) {
            $this->errorManager->handleError(
                message: 'error moving file: ' . $path . ' does not exist',
                code: Response::HTTP_BAD_REQUEST
            );
        }

        // get current name and directory path
        $isDirectory = $this->fileSystemUtil->isPathDirectory($path);
        $directoryPath = $this->fileSystemUtil->getDirname($path);
        if ($directoryPath === '.') {
            $directoryPath = '/';
        }

        // always use basename for the display name
        $currentName = $this->fileSystemUtil->getBasename($path);

        // get list of all directories for destination selection
        $availableFolders = [];

        // add root directory
        $availableFolders[] = [
            'path' => '/',
            'displayPath' => '/root'
        ];

        // add common directories
        $commonDirs = ['/var', '/var/www', '/var/www/html', '/home', '/tmp', '/opt'];
        foreach ($commonDirs as $dir) {
            if ($this->fileSystemUtil->checkIfFileExist($dir) && $this->fileSystemUtil->isPathDirectory($dir) && $dir !== $path && !str_starts_with($path, $dir . '/')) {
                $availableFolders[] = [
                    'path' => $dir,
                    'displayPath' => $dir
                ];
            }
        }

        // add parent directory of the current path
        $parentDir = $this->fileSystemUtil->getDirname($path);
        if ($parentDir !== '/' && $parentDir !== $path) {
            $availableFolders[] = [
                'path' => $parentDir,
                'displayPath' => $parentDir
            ];
        }

        // render file move view
        return $this->render('component/file-system/file-system-move.twig', [
            'currentPath' => $path,
            'currentName' => $currentName,
            'isDirectory' => $isDirectory,
            'directoryPath' => $directoryPath,
            'availableFolders' => $availableFolders
        ]);
    }

    /**
     * Move file or directory
     *
     * @param Request $request The request object
     *
     * @return Response The redirect response
     */
    #[Authorization(authorization: 'ADMIN')]
    #[Route('/filesystem/move/save', methods: ['POST'], name: 'app_file_system_move_save')]
    public function filesystemMoveSave(Request $request): Response
    {
        // get source path
        $sourcePath = (string) $request->request->get('sourcePath', '/');

        // get destination path based on the selected type
        $destinationPath = (string) $request->request->get('customDestinationPath', '/');

        // additional validation for the source path
        if (!$this->fileSystemUtil->checkIfFileExist($sourcePath)) {
            $this->errorManager->handleError(
                message: 'the file or directory to move does not exist: ' . $sourcePath,
                code: Response::HTTP_BAD_REQUEST
            );
        }

        // fet return path (directory containing the file)
        $directoryPath = $this->fileSystemUtil->getDirname($sourcePath);
        if ($directoryPath === '.') {
            $directoryPath = '/';
        }

        try {
            // move file or directory
            $result = $this->fileSystemUtil->moveFileOrDirectory($sourcePath, $destinationPath);

            // check if move was successful
            if (!$result) {
                $this->errorManager->handleError(
                    message: 'failed to move file or directory',
                    code: Response::HTTP_INTERNAL_SERVER_ERROR
                );
            }

            // get the basename of the source
            $basename = $this->fileSystemUtil->getBasename($sourcePath);

            // log file move
            $this->logManager->log(
                name: 'file-browser',
                message: 'file/directory: ' . $sourcePath . ' was moved to ' . $destinationPath . '/' . $basename,
                level: LogManager::LEVEL_INFO
            );

            // add flash message
            $this->addFlash('success', ($this->fileSystemUtil->isPathDirectory($destinationPath . '/' . $basename) ? 'Directory' : 'File') . ' moved successfully');

            // redirect to destination directory
            return $this->redirectToRoute('app_file_system_browser', ['path' => $destinationPath]);
        } catch (Exception $e) {
            // check if the error is about destination already existing
            if (str_contains($e->getMessage(), 'Destination already exists')) {
                // render error page instead of throwing an exception
                return $this->render('component/file-system/file-system-error.twig', [
                    'errorTitle' => 'Destination Already Exists',
                    'errorMessage' => 'A file or directory with the same name already exists in the destination folder.',
                    'details' => $e->getMessage(),
                    'returnPath' => $directoryPath,
                    'actionPath' => $this->generateUrl('app_file_system_move', ['path' => $sourcePath]),
                    'actionText' => 'Try Different Destination',
                    'actionIcon' => 'exchange'
                ]);
            } elseif (str_contains($e->getMessage(), 'Cannot move a directory into its own subdirectory')) {
                // render error page for subdirectory move attempt
                return $this->render('component/file-system/file-system-error.twig', [
                    'errorTitle' => 'Invalid Move Operation',
                    'errorMessage' => 'You cannot move a directory into its own subdirectory.',
                    'details' => $e->getMessage(),
                    'returnPath' => $directoryPath,
                    'actionPath' => $this->generateUrl('app_file_system_move', ['path' => $sourcePath]),
                    'actionText' => 'Try Different Destination',
                    'actionIcon' => 'exchange'
                ]);
            }

            // handle other errors
            $this->errorManager->handleError(
                message: 'error moving file or directory: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Download file
     *
     * @param Request $request The request object
     *
     * @return StreamedResponse The file download response
     */
    #[Authorization(authorization: 'ADMIN')]
    #[Route('/filesystem/download', methods: ['GET'], name: 'app_file_system_download')]
    public function filesystemDownload(Request $request): StreamedResponse
    {
        // get file path
        $path = (string) $request->query->get('path', '/');

        try {
            // check if path exists
            if (!$this->fileSystemUtil->checkIfFileExist($path)) {
                $this->errorManager->handleError(
                    message: 'file does not exist: ' . $path,
                    code: Response::HTTP_BAD_REQUEST
                );
            }

            // get file size
            $fileSize = $this->fileSystemUtil->getFileSize($path);

            // get filename for download
            $filename = $this->fileSystemUtil->getBasename($path);

            // detect media type for proper content-type header
            $mediaType = $this->fileSystemUtil->detectMediaType($path);
            if ($mediaType === 'non-mediafile') {
                $mediaType = 'application/octet-stream';
            }

            // log file download
            $this->logManager->log(
                name: 'file-browser',
                message: 'file: ' . $path . ' was downloaded',
                level: LogManager::LEVEL_INFO
            );

            // create streamed response for file download with chunked streaming
            $response = new StreamedResponse(function () use ($path, $fileSize) {
                $this->fileUploadUtil->streamFileRange($path, 0, $fileSize);
            });

            // set response headers for download
            $response->headers->set('Content-Type', $mediaType);
            $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
            $response->headers->set('Content-Length', (string) $fileSize);
            $response->headers->set('Cache-Control', 'no-cache, must-revalidate');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');

            return $response;
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error downloading file: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Render file upload component
     *
     * @param Request $request The request object
     *
     * @return Response The file upload view response
     */
    #[Authorization(authorization: 'ADMIN')]
    #[Route('/filesystem/upload', methods: ['GET'], name: 'app_file_system_upload')]
    public function filesystemUpload(Request $request): Response
    {
        // get directory path
        $path = (string) $request->query->get('path', '/');

        // ensure path is a directory
        if ($this->fileSystemUtil->checkIfFileExist($path) && !$this->fileSystemUtil->isPathDirectory($path)) {
            $path = $this->fileSystemUtil->getDirname($path);
        }

        // check if directory exists
        if (!$this->fileSystemUtil->checkIfFileExist($path)) {
            return $this->render('component/file-system/file-system-error.twig', [
                'errorTitle' => 'Directory Not Found',
                'errorMessage' => 'The directory you are trying to upload to does not exist.',
                'details' => 'Path: ' . $path,
                'returnPath' => '/',
                'actionPath' => null,
                'actionText' => null,
                'actionIcon' => null
            ]);
        }

        // render file upload view
        return $this->render('component/file-system/file-system-upload.twig', [
            'currentPath' => $path
        ]);
    }

    /**
     * Process chunked file upload
     *
     * @param Request $request The request object
     *
     * @return JsonResponse The upload status response
     */
    #[Authorization(authorization: 'ADMIN')]
    #[Route('/filesystem/upload/chunk', methods: ['POST'], name: 'app_file_system_upload_chunk')]
    public function filesystemUploadChunk(Request $request): JsonResponse
    {
        try {
            // get request parameters
            $directoryPath = (string) $request->request->get('directory', '/');
            $filename = (string) $request->request->get('filename', '');
            $chunkIndex = (int) $request->request->get('chunkIndex', 0);
            $totalChunks = (int) $request->request->get('totalChunks', 1);
            $fileId = (string) $request->request->get('fileId', '');

            // validate parameters
            if (empty($filename) || empty($fileId)) {
                return $this->json([
                    'success' => false,
                    'error' => 'Missing required parameters'
                ], Response::HTTP_BAD_REQUEST);
            }

            // check if directory exists
            if (!$this->fileSystemUtil->checkIfFileExist($directoryPath) || !$this->fileSystemUtil->isPathDirectory($directoryPath)) {
                return $this->json([
                    'success' => false,
                    'error' => 'Directory does not exist: ' . $directoryPath
                ], Response::HTTP_BAD_REQUEST);
            }

            // validate filename
            if (str_contains($filename, '/') || strlen($filename) > 255) {
                return $this->json([
                    'success' => false,
                    'error' => 'Invalid filename'
                ], Response::HTTP_BAD_REQUEST);
            }

            // get uploaded chunk
            $uploadedFile = $request->files->get('chunk');
            if (!$uploadedFile) {
                return $this->json([
                    'success' => false,
                    'error' => 'No chunk data received'
                ], Response::HTTP_BAD_REQUEST);
            }

            // create temp directory for chunks
            $tempDir = sys_get_temp_dir() . '/admin_suite_upload_' . $fileId;
            if (!$this->fileSystemUtil->isPathDirectory($tempDir)) {
                $this->fileSystemUtil->createDirectory($tempDir, 0755);
            }

            // save chunk to temp file
            $this->fileUploadUtil->saveUploadedChunk(
                file: $uploadedFile,
                targetDir: $tempDir,
                targetFilename: 'chunk_' . $chunkIndex,
                useSudo: true
            );

            // check if all chunks are uploaded
            $uploadedChunks = $this->fileUploadUtil->listChunks($tempDir, 'chunk_', useSudo: true);
            if (count($uploadedChunks) === $totalChunks) {
                // combine all chunks
                $targetPath = rtrim($directoryPath, '/') . '/' . $filename;

                // check if file already exists
                if ($this->fileSystemUtil->checkIfFileExist($targetPath)) {
                    // cleanup temp directory
                    $this->fileUploadUtil->cleanupTempDirectory($tempDir);
                    return $this->json([
                        'success' => false,
                        'error' => 'File already exists: ' . $filename
                    ], Response::HTTP_CONFLICT);
                }

                // combine chunks into final file
                $result = $this->fileUploadUtil->combineChunks($tempDir, $targetPath, $totalChunks);

                if ($result) {
                    // log file upload
                    $this->logManager->log(
                        name: 'file-browser',
                        message: 'file: ' . $targetPath . ' was uploaded via chunked upload',
                        level: LogManager::LEVEL_INFO
                    );

                    // cleanup temp directory
                    $this->fileUploadUtil->cleanupTempDirectory($tempDir);

                    // return success response
                    return $this->json([
                        'success' => true,
                        'message' => 'File uploaded successfully',
                        'filename' => $filename
                    ]);
                } else {
                    // cleanup temp directory
                    $this->fileUploadUtil->cleanupTempDirectory($tempDir);
                    return $this->json([
                        'success' => false,
                        'error' => 'Failed to combine chunks'
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);
                }
            }

            // return progress status
            return $this->json([
                'success' => true,
                'progress' => round((count($uploadedChunks) / $totalChunks) * 100, 2),
                'chunksUploaded' => count($uploadedChunks),
                'totalChunks' => $totalChunks
            ]);
        } catch (Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Upload error: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Process file upload (fallback for non-JS clients)
     *
     * @param Request $request The request object
     *
     * @return Response The redirect response
     */
    #[Authorization(authorization: 'ADMIN')]
    #[Route('/filesystem/upload/save', methods: ['POST'], name: 'app_file_system_upload_save')]
    public function filesystemUploadSave(Request $request): Response
    {
        // get directory path
        $directoryPath = (string) $request->request->get('directory', '/');

        // check if directory exists
        if (!$this->fileSystemUtil->checkIfFileExist($directoryPath)) {
            $this->errorManager->handleError(
                message: 'directory does not exist: ' . $directoryPath,
                code: Response::HTTP_BAD_REQUEST
            );
        }

        // check if directory is writable
        if (!$this->fileSystemUtil->isPathDirectory($directoryPath)) {
            $this->errorManager->handleError(
                message: 'path is not a directory: ' . $directoryPath,
                code: Response::HTTP_BAD_REQUEST
            );
        }

        try {
            // get uploaded files
            $uploadedFiles = $request->files->get('files', []);

            // check if files were uploaded
            if (empty($uploadedFiles)) {
                $this->addFlash('error', 'No files were selected for upload');
                return $this->redirectToRoute('app_file_system_upload', ['path' => $directoryPath]);
            }

            $uploadedCount = 0;
            $errors = [];

            // process each uploaded file
            foreach ($uploadedFiles as $uploadedFile) {
                if ($uploadedFile === null) {
                    continue;
                }

                // get original filename
                $originalFilename = $uploadedFile->getClientOriginalName();
                if (empty($originalFilename)) {
                    $errors[] = 'File with empty name was skipped';
                    continue;
                }

                // validate filename
                if (str_contains($originalFilename, '/')) {
                    $errors[] = 'File "' . $originalFilename . '" contains invalid characters';
                    continue;
                }

                // check filename length
                if (strlen($originalFilename) > 255) {
                    $errors[] = 'File "' . $originalFilename . '" name is too long (max 255 characters)';
                    continue;
                }

                // build target file path
                $targetPath = rtrim($directoryPath, '/') . '/' . $originalFilename;

                // check if file already exists
                if ($this->fileSystemUtil->checkIfFileExist($targetPath)) {
                    $errors[] = 'File "' . $originalFilename . '" already exists';
                    continue;
                }

                // get file content
                $fileContent = $this->fileSystemUtil->getFullFileContent($uploadedFile->getPathname());

                // save file
                $result = $this->fileSystemUtil->saveFileContent($targetPath, $fileContent);

                // check if save was successful
                if ($result) {
                    $uploadedCount++;

                    // log file upload
                    $this->logManager->log(
                        name: 'file-browser',
                        message: 'file: ' . $targetPath . ' was uploaded',
                        level: LogManager::LEVEL_INFO
                    );
                } else {
                    $errors[] = 'Failed to save file "' . $originalFilename . '"';
                }
            }

            // add flash messages
            if ($uploadedCount > 0) {
                $this->addFlash('success', $uploadedCount . ' file(s) uploaded successfully');
            }

            // show errors as flash messages
            if (!empty($errors)) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                }
            }

            // redirect back to directory view
            return $this->redirectToRoute('app_file_system_browser', ['path' => $directoryPath]);
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error uploading files: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
