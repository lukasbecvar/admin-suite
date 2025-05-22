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

        // check if path exists
        if (!file_exists($path)) {
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

        // get list of file in current path
        $filesystemList = $this->fileSystemUtil->getFilesList($path);

        // add information about editability for each file
        foreach ($filesystemList as &$file) {
            if (!$file['isDir']) {
                $file['isEditable'] = $this->fileSystemUtil->isFileEditable($file['path']);
            } else {
                $file['isEditable'] = false;
            }
        }

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

        // check if file exists
        if (!$this->fileSystemUtil->checkIfFileExist($path)) {
            // get directory path for return link
            $directoryPath = dirname($path);
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
            // check if file is executable but not a shell script
            $fileInfo = exec('sudo file ' . escapeshellarg($path));
            if ($fileInfo === false) {
                $fileInfo = '';
            }
            $isShellScript = strpos($fileInfo, 'shell script') !== false || str_ends_with($path, '.sh') || str_ends_with($path, '.bash');

            if ($this->fileSystemUtil->isFileExecutable($path) && !$isShellScript) {
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
    #[Route('/filesystem/create', methods:['GET'], name: 'app_file_system_create')]
    public function filesystemCreate(Request $request): Response
    {
        // get directory path
        $path = (string) $request->query->get('path', '/');

        // ensure path is a directory
        if (file_exists($path) && !is_dir($path)) {
            // if path is a file, use its directory
            $path = dirname($path);
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
    #[Route('/filesystem/create/directory', methods:['GET'], name: 'app_file_system_create_directory')]
    public function filesystemCreateDirectory(Request $request): Response
    {
        // get directory path
        $path = (string) $request->query->get('path', '/');

        // ensure path is a directory
        if (file_exists($path) && !is_dir($path)) {
            // if path is a file, use its directory
            $path = dirname($path);
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
    #[Route('/filesystem/create/save', methods:['POST'], name: 'app_file_system_create_save')]
    public function filesystemCreateSave(Request $request): Response
    {
        // get directory path, filename and content
        $directoryPath = (string) $request->request->get('directory', '/');
        $filename = (string) $request->request->get('filename', '');
        $content = (string) $request->request->get('content', '');

        // check if directory exists
        if (!file_exists($directoryPath)) {
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
            if (file_exists($filePath)) {
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
            $parentDir = dirname($filePath);
            if (!file_exists($parentDir)) {
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
                $chmodCommand = 'sudo chmod +x ' . escapeshellarg($filePath);
                shell_exec($chmodCommand);
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
    #[Route('/filesystem/create/directory/save', methods:['POST'], name: 'app_file_system_create_directory_save')]
    public function filesystemCreateDirectorySave(Request $request): Response
    {
        // get directory path and new directory name
        $parentPath = (string) $request->request->get('directory', '/');
        $directoryName = (string) $request->request->get('directoryname', '');

        // check if parent directory exists
        if (!file_exists($parentPath)) {
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
            if (file_exists($directoryPath)) {
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
    #[Route('/filesystem/rename', methods:['GET'], name: 'app_file_system_rename')]
    public function filesystemRename(Request $request): Response
    {
        // get file path
        $path = (string) $request->query->get('path', '/');

        // check if path exists
        if (!file_exists($path)) {
            $this->errorManager->handleError(
                message: 'error renaming file: ' . $path . ' does not exist',
                code: Response::HTTP_BAD_REQUEST
            );
        }

        // get current name and directory path
        $isDirectory = is_dir($path);
        $directoryPath = dirname($path);
        if ($directoryPath === '.') {
            $directoryPath = '/';
        }

        // always use basename for the display name
        $currentName = basename($path);

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
    #[Route('/filesystem/rename/save', methods:['POST'], name: 'app_file_system_rename_save')]
    public function filesystemRenameSave(Request $request): Response
    {
        // get old path and new name
        $oldPath = (string) $request->request->get('path', '/');
        $newName = (string) $request->request->get('newName', '');

        // additional validation for the old path
        if (!file_exists($oldPath)) {
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
            $this->addFlash('success', (is_dir($oldPath) ? 'Directory' : 'File') . ' renamed successfully');

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
            $resourceData = $this->fileSystemUtil->getFileContent($path);
            $resourceContent = $resourceData['content'];

            // log file access
            $this->logManager->log(
                name: 'file-browser',
                message: 'file: ' . $path . ' was accessed',
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

    /**
     * Render file edit component
     *
     * @param Request $request The request object
     *
     * @return Response The file editor view response
     */
    #[Authorization(authorization: 'ADMIN')]
    #[Route('/filesystem/edit', methods:['GET'], name: 'app_file_system_edit')]
    public function filesystemEdit(Request $request): Response
    {
        // get file path
        $path = (string) $request->query->get('path', '/');

        // default file content value
        $fileContent = null;

        try {
            // check if file is executable but not a shell script
            $fileInfo = exec('sudo file ' . escapeshellarg($path));
            if ($fileInfo === false) {
                $fileInfo = '';
            }
            $isShellScript = strpos($fileInfo, 'shell script') !== false || str_ends_with($path, '.sh') || str_ends_with($path, '.bash');

            if ($this->fileSystemUtil->isFileExecutable($path) && !$isShellScript) {
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
    #[Route('/filesystem/save', methods:['POST'], name: 'app_file_system_save')]
    public function filesystemSave(Request $request): Response
    {
        // get file path and content (raw, without escaping)
        $path = (string) $request->request->get('path', '/');
        $content = (string) $request->request->get('content', '');

        try {
            // check if file is executable but not a shell script
            $fileInfo = exec('sudo file ' . escapeshellarg($path));
            if ($fileInfo === false) {
                $fileInfo = '';
            }
            $isShellScript = strpos($fileInfo, 'shell script') !== false || str_ends_with($path, '.sh') || str_ends_with($path, '.bash');

            if ($this->fileSystemUtil->isFileExecutable($path) && !$isShellScript) {
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
    #[Route('/filesystem/delete', methods:['POST'], name: 'app_file_system_delete')]
    public function filesystemDelete(Request $request): Response
    {
        // get file path
        $path = (string) $request->request->get('path', '/');

        // additional validation for the path
        if (!file_exists($path)) {
            $this->errorManager->handleError(
                message: 'the file or directory to delete does not exist: ' . $path,
                code: Response::HTTP_BAD_REQUEST
            );
        }

        // get return path (directory containing the file)
        $directoryPath = dirname($path);
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
            $this->addFlash('success', (is_dir($path) ? 'Directory' : 'File') . ' deleted successfully');

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
    #[Route('/filesystem/move', methods:['GET'], name: 'app_file_system_move')]
    public function filesystemMove(Request $request): Response
    {
        // get file path
        $path = (string) $request->query->get('path', '/');

        // check if path exists
        if (!file_exists($path)) {
            $this->errorManager->handleError(
                message: 'error moving file: ' . $path . ' does not exist',
                code: Response::HTTP_BAD_REQUEST
            );
        }

        // get current name and directory path
        $isDirectory = is_dir($path);
        $directoryPath = dirname($path);
        if ($directoryPath === '.') {
            $directoryPath = '/';
        }

        // always use basename for the display name
        $currentName = basename($path);

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
            if (file_exists($dir) && is_dir($dir) && $dir !== $path && !str_starts_with($path, $dir . '/')) {
                $availableFolders[] = [
                    'path' => $dir,
                    'displayPath' => $dir
                ];
            }
        }

        // add parent directory of the current path
        $parentDir = dirname($path);
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
            'directoryPath' => $directoryPath,
            'isDirectory' => $isDirectory,
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
    #[Route('/filesystem/move/save', methods:['POST'], name: 'app_file_system_move_save')]
    public function filesystemMoveSave(Request $request): Response
    {
        // get source path
        $sourcePath = (string) $request->request->get('sourcePath', '/');

        // get destination path based on the selected type
        $destinationPathType = (string) $request->request->get('destinationPathType', 'select');
        if ($destinationPathType === 'custom') {
            $destinationPath = (string) $request->request->get('customDestinationPath', '/');
        } else {
            $destinationPath = (string) $request->request->get('destinationPath', '/');
        }

        // additional validation for the source path
        if (!file_exists($sourcePath)) {
            $this->errorManager->handleError(
                message: 'the file or directory to move does not exist: ' . $sourcePath,
                code: Response::HTTP_BAD_REQUEST
            );
        }

        // validate custom destination path
        if ($destinationPathType === 'custom') {
            // check if path is empty
            if (empty($destinationPath)) {
                $this->errorManager->handleError(
                    message: 'destination path cannot be empty',
                    code: Response::HTTP_BAD_REQUEST
                );
            }

            // check if path starts with /
            if (!str_starts_with($destinationPath, '/')) {
                $this->errorManager->handleError(
                    message: 'destination path must start with /',
                    code: Response::HTTP_BAD_REQUEST
                );
            }

            // check if destination path exists and is a directory
            if (!file_exists($destinationPath) || !is_dir($destinationPath)) {
                // render error page instead of throwing an exception
                return $this->render('component/file-system/file-system-error.twig', [
                    'errorTitle' => 'Invalid Destination',
                    'errorMessage' => 'The destination path does not exist or is not a directory.',
                    'details' => 'Path: ' . $destinationPath,
                    'returnPath' => dirname($sourcePath),
                    'actionPath' => $this->generateUrl('app_file_system_move', ['path' => $sourcePath]),
                    'actionText' => 'Try Different Path',
                    'actionIcon' => 'exchange'
                ]);
            }
        }

        // fet return path (directory containing the file)
        $directoryPath = dirname($sourcePath);
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
            $basename = basename($sourcePath);

            // log file move
            $this->logManager->log(
                name: 'file-browser',
                message: 'file/directory: ' . $sourcePath . ' was moved to ' . $destinationPath . '/' . $basename,
                level: LogManager::LEVEL_INFO
            );

            // add flash message
            $this->addFlash('success', (is_dir($destinationPath . '/' . $basename) ? 'Directory' : 'File') . ' moved successfully');

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
}
