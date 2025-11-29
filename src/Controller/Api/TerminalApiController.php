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
use App\Annotation\CsrfProtection;
use App\Manager\TerminalJobManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
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
    private TerminalJobManager $terminalJobManager;

    public function __construct(
        AppUtil $appUtil,
        LogManager $logManager,
        AuthManager $authManager,
        SessionUtil $sessionUtil,
        ErrorManager $errorManager,
        SecurityUtil $securityUtil,
        TerminalJobManager $terminalJobManager,
    ) {
        $this->appUtil = $appUtil;
        $this->logManager = $logManager;
        $this->sessionUtil = $sessionUtil;
        $this->authManager = $authManager;
        $this->errorManager = $errorManager;
        $this->securityUtil = $securityUtil;
        $this->terminalJobManager = $terminalJobManager;
    }

    /**
     * API to execute terminal command
     *
     * This endpoint is used in terminal component
     *
     * Request body parameters:
     *  - command: command to execute (string)
     *
     * @param Request $request The request object
     *
     * @return Response The command output
     */
    #[CsrfProtection(enabled: false)]
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
            $blockedCommandsConfig = $this->appUtil->loadConfig('terminal-blocked-commands.json');
            $aliases = $this->appUtil->loadConfig('terminal-aliases.json');

            // check if config files are iterable
            if (!is_iterable($blockedCommandsConfig) || !is_iterable($aliases)) {
                return new Response('Error to load terminal config files', Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            // normalize blocked commands
            $normalized = [];
            foreach ($blockedCommandsConfig as $key => $blockedCommand) {
                if (is_string($blockedCommand) && $blockedCommand !== '') {
                    $normalized[] = $blockedCommand;
                    continue;
                }

                if (is_string($key) && $blockedCommand) {
                    $normalized[] = $key;
                }
            }
            $blockedCommands = array_values(array_unique($normalized));

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

    /**
     * Start background terminal job
     *
     * Request body parameters:
     *  - command: command to execute (string)
     *
     * @param Request $request The request object
     *
     * @return JsonResponse The JSON response
     */
    #[CsrfProtection(enabled: false)]
    #[Authorization(authorization: 'ADMIN')]
    #[Route('/api/system/terminal/job', methods: ['POST'], name: 'api_terminal_job_start')]
    public function startTerminalJob(Request $request): JsonResponse
    {
        // get current username
        $username = $this->authManager->getLoggedUsername();

        // get raw command from request
        $rawCommand = trim((string) $request->request->get('command', ''));
        if ($rawCommand === '') {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Error: command is not set'
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $blockedCommandsConfig = $this->appUtil->loadConfig('terminal-blocked-commands.json');
            $aliases = $this->appUtil->loadConfig('terminal-aliases.json');

            // check if configs are iterable
            if (!is_iterable($blockedCommandsConfig) || !is_iterable($aliases)) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'Error to load terminal config files'
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            // normalize blocked commands
            $normalized = [];
            foreach ($blockedCommandsConfig as $key => $blockedCommand) {
                if (is_string($blockedCommand) && $blockedCommand !== '') {
                    $normalized[] = $blockedCommand;
                    continue;
                }

                if (is_string($key) && $blockedCommand) {
                    $normalized[] = $key;
                }
            }
            $blockedCommands = array_values(array_unique($normalized));

            // translate aliases to commands
            foreach ($aliases as $alias => $value) {
                if ($rawCommand === (string) $alias) {
                    $rawCommand = (string) $value;
                    break;
                }
            }

            // check if command is blocked
            foreach ($blockedCommands as $blockedCommand) {
                /** @var string $blockedCommand */
                if (
                    str_starts_with($rawCommand, $blockedCommand) ||
                    str_starts_with($rawCommand, 'sudo ' . $blockedCommand)
                ) {
                    return new JsonResponse([
                        'status' => 'blocked',
                        'message' => 'Command: ' . $rawCommand . ' is not allowed'
                    ], Response::HTTP_OK);
                }
            }

            // resolve working directory
            $workingDirectory = '/';
            if ($this->sessionUtil->checkSession('terminal-dir')) {
                /** @var string $currentDir */
                $currentDir = (string) $this->sessionUtil->getSessionValue('terminal-dir');

                if ($currentDir !== '' && file_exists($currentDir)) {
                    $workingDirectory = $currentDir;
                }
            }

            // resolve terminal user
            $sudoUser = (string) $this->sessionUtil->getSessionValue('terminal-user', 'root');
            if ($sudoUser === '') {
                $sudoUser = 'root';
            }

            // start terminal job
            $job = $this->terminalJobManager->startJob($rawCommand, $sudoUser, $workingDirectory);

            // log terminal job start
            $this->logManager->log(
                name: 'terminal',
                message: $username . ' started background command: ' . $rawCommand . ' (job: ' . $job['jobId'] . ')',
                level: LogManager::LEVEL_WARNING
            );

            // return terminal job status
            return new JsonResponse([
                'status' => 'running',
                'jobId' => $job['jobId'],
                'offset' => 0,
                'chunk' => '',
                'isRunning' => true,
                'startedAt' => $job['startedAt'],
                'mode' => $job['mode'] ?? null
            ], Response::HTTP_OK);
        } catch (Exception $exception) {
            $this->errorManager->logError(
                message: 'error to start background command: ' . $rawCommand . ' with error: ' . $exception->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );

            // return error response
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Error to start command: ' . $rawCommand
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get background job output chunk
     *
     * Request body parameters:
     *  - jobId: job ID (string)
     *
     * @param Request $request The request object
     *
     * @return JsonResponse The JSON response
     */
    #[Authorization(authorization: 'ADMIN')]
    #[Route('/api/system/terminal/job', methods: ['GET'], name: 'api_terminal_job_status')]
    public function getTerminalJobStatus(Request $request): JsonResponse
    {
        // get job ID from request
        $jobId = (string) $request->query->get('jobId', '');
        if ($jobId === '') {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Error: job ID is not set'
            ], Response::HTTP_BAD_REQUEST);
        }

        // get limit and offset from request
        $offset = (int) $request->query->get('offset', '0');
        $limit = $request->query->has('limit') ? (int) $request->query->get('limit') : null;

        try {
            // get terminal job output
            $output = $this->terminalJobManager->getOutput($jobId, $offset, $limit);

            // return job output
            return new JsonResponse([
                'status' => $output['isRunning'] ? 'running' : 'finished',
                'chunk' => $output['chunk'],
                'offset' => $output['offset'],
                'isRunning' => $output['isRunning'],
                'exitCode' => $output['exitCode'],
                'startedAt' => $output['startedAt'],
                'mode' => $output['executionMode'] ?? null
            ], Response::HTTP_OK);
        } catch (Exception $exception) {
            // log error
            $this->errorManager->logError(
                message: 'error getting terminal job output: ' . $exception->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );

            // return error response
            return new JsonResponse([
                'status' => 'error',
                'message' => $exception->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Stop background terminal job
     *
     * Request body parameters:
     *  - jobId: job ID (string)
     *
     * @param Request $request The request object
     *
     * @return JsonResponse The JSON response
     */
    #[CsrfProtection(enabled: false)]
    #[Authorization(authorization: 'ADMIN')]
    #[Route('/api/system/terminal/job/stop', methods: ['POST'], name: 'api_terminal_job_stop')]
    public function stopTerminalJob(Request $request): JsonResponse
    {
        // get job ID from request
        $jobId = (string) $request->request->get('jobId', '');
        if ($jobId === '') {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Error: job ID is not set'
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            // stop job
            $this->terminalJobManager->stopJob($jobId);

            return new JsonResponse([
                'status' => 'stopped'
            ], Response::HTTP_OK);
        } catch (Exception $exception) {
            return new JsonResponse([
                'status' => 'error',
                'message' => $exception->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Send interactive input to running terminal job
     *
     * Request body parameters:
     *  - jobId: job ID (string)
     *  - input: input value (string)
     *
     * @param Request $request The request object
     *
     * @return JsonResponse The JSON response
     */
    #[CsrfProtection(enabled: false)]
    #[Authorization(authorization: 'ADMIN')]
    #[Route('/api/system/terminal/job/input', methods: ['POST'], name: 'api_terminal_job_input')]
    public function sendTerminalJobInput(Request $request): JsonResponse
    {
        // get job ID from request
        $jobId = (string) $request->request->get('jobId', '');
        if ($jobId === '') {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Error: job ID is not set'
            ], Response::HTTP_BAD_REQUEST);
        }

        // get input from request
        if (!$request->request->has('input')) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Error: input value is required'
            ], Response::HTTP_BAD_REQUEST);
        }
        $input = (string) $request->request->get('input');

        try {
            // append input to job
            $this->terminalJobManager->appendInput($jobId, $input);

            return new JsonResponse([
                'status' => 'ok'
            ], Response::HTTP_OK);
        } catch (Exception $exception) {
            return new JsonResponse([
                'status' => 'error',
                'message' => $exception->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }
}
