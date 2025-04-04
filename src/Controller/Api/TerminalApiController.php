<?php

namespace App\Controller\Api;

use Exception;
use App\Util\AppUtil;
use App\Util\SessionUtil;
use App\Util\SecurityUtil;
use App\Manager\LogManager;
use App\Manager\AuthManager;
use App\Manager\ErrorManager;
use App\Annotation\Authorization;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Class TerminalApiController
 *
 * Controller for terminal API
 *
 * @package App\Controller\Api
 */
class TerminalApiController extends AbstractController
{
    private AppUtil $appUtil;
    private LogManager $logManager;
    private SessionUtil $sessionUtil;
    private AuthManager $authManager;
    private ErrorManager $errorManager;
    private SecurityUtil $securityUtil;

    public function __construct(
        AppUtil $appUtil,
        LogManager $logManager,
        AuthManager $authManager,
        SessionUtil $sessionUtil,
        ErrorManager $errorManager,
        SecurityUtil $securityUtil,
    ) {
        $this->appUtil = $appUtil;
        $this->logManager = $logManager;
        $this->sessionUtil = $sessionUtil;
        $this->authManager = $authManager;
        $this->errorManager = $errorManager;
        $this->securityUtil = $securityUtil;
    }

    /**
     * API to execute terminal command
     *
     * @param Request $request The request object
     *
     * @return Response The command output
     */
    #[Authorization(authorization: 'ADMIN')]
    #[Route('/api/system/terminal', methods: ['POST'], name: 'api_terminal')]
    public function terminalAction(Request $request): Response
    {
        try {
            // get command executor username
            $username = $this->authManager->getLoggedUsername();

            // get command from request parameter
            $command = (string) $request->request->get('command');

            // check if command empty
            if (empty($command)) {
                return new Response('Error: command is not set', Response::HTTP_OK);
            }

            // set default working dir
            if ($this->sessionUtil->checkSession('terminal-dir')) {
                /** @var string $currentDir get current working directory */
                $currentDir = $this->sessionUtil->getSessionValue('terminal-dir');

                // check if directory exist
                if (!file_exists($currentDir)) {
                    chdir('/');
                } else {
                    chdir($currentDir);
                }
            } else {
                chdir('/');
            }

            // escape security vulnerabilities from command
            $command = $this->securityUtil->escapeString($command);

            // load terminal config files
            $blockedCommands = $this->appUtil->loadConfig('terminal-blocked-commands.json');
            $aliases = $this->appUtil->loadConfig('terminal-aliases.json');

            // check if config files are iterable
            if (!is_iterable($blockedCommands) || !is_iterable($aliases)) {
                return new Response('Error to load terminal config files', Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            // check if command is blocked
            foreach ($blockedCommands as $blockedCommand) {
                /** @var string $blockedCommand */
                if (str_starts_with($command ?? '', $blockedCommand) || str_starts_with($command ?? '', 'sudo ' . $blockedCommand)) {
                    return new Response('Command: ' . $command . ' is not allowed', Response::HTTP_OK);
                }
            }

            // replace aliases with runnable command
            foreach ($aliases as $index => $value) {
                if ($command == $index) {
                    /** @var string $command */
                    $command = $value;
                }
            }

            // check if command is empty
            if (!$command) {
                return new Response('Error: command is not set', Response::HTTP_OK);
            }

            // get cwd (system get)
            if ($command == 'get_current_path_1181517815187484') {
                // get cwd
                $cwd = getcwd();

                // check is cwd get success
                if (!$cwd) {
                    return new Response('Error to get current working directory', Response::HTTP_OK);
                } else {
                    return new Response($cwd, Response::HTTP_OK);
                }
            }

            // update cwd (system get)
            if (str_starts_with($command, 'cd ')) {
                $newDir = str_replace('cd ', '', $command);

                // check if dir is / root dir
                if (!str_starts_with($newDir, '/')) {
                    if (getcwd() == '/') {
                        $finalDir = '/' . $newDir;
                    } else {
                        $finalDir = getcwd() . '/' . $newDir;
                    }
                } else {
                    $finalDir = $newDir;
                }

                // check if directory exists
                if (file_exists($finalDir)) {
                    // check if directory is readable
                    if (!is_readable($finalDir)) {
                        return new Response('Error: you do not have permission to access this directory', Response::HTTP_OK);
                    }
                    $this->sessionUtil->setSession('terminal-dir', $finalDir);
                    return new Response(status: Response::HTTP_OK);
                } else {
                    return new Response('Error: directory: ' . $finalDir . ' not found', Response::HTTP_OK);
                }

            // switch terminal user
            } elseif (str_starts_with($command, 'su ')) {
                $newUser = str_replace('su ', '', $command);

                // check if username is empty
                if (strlen($newUser) == 0) {
                    return new Response('Error: username is not set', Response::HTTP_OK);
                }

                // check if username exists in passwd file
                $userList = file_get_contents('/etc/passwd');
                if (!$userList) {
                    return new Response('Error to get user list', Response::HTTP_OK);
                }
                if (!str_contains($userList, $newUser)) {
                    return new Response('Error: username: ' . $newUser . ' not found', Response::HTTP_OK);
                }

                // save new user to session storage
                $this->sessionUtil->setSession('terminal-user', $newUser);

                // return user switch status
                return new Response('User switched to: ' . $newUser, Response::HTTP_OK);

            // regular command execution
            } else {
                // execute command
                exec('sudo -u ' . $this->sessionUtil->getSessionValue('terminal-user') . ' ' . $command, $output, $returnCode);

                // check if command run valid
                if ($returnCode !== 0) {
                    $this->logManager->log(
                        name: 'terminal',
                        message: $username . ' executed command: ' . $command . ' with error code: ' . $returnCode,
                        level: LogManager::LEVEL_WARNING
                    );
                    return new Response('Error to execute command: ' . $command . ', status code: ' . $returnCode, Response::HTTP_OK);
                }

                // log execute action
                $this->logManager->log(
                    name: 'terminal',
                    message: $username . ' executed command: ' . $command,
                    level: LogManager::LEVEL_WARNING
                );

                // get command output
                $output = implode("\n", $output);

                // escape security vulnerabilities from output
                $output = $this->securityUtil->escapeString($output);

                // return command output
                return new Response($output, Response::HTTP_OK);
            }
        } catch (Exception $e) {
            $this->errorManager->logError(
                message: 'error to execute command: ' . $command . ' with error: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
            return new Response(
                content: 'Error to execute command: ' . $command . ' with error: ' . $e->getMessage(),
                status: Response::HTTP_OK
            );
        }
    }
}
