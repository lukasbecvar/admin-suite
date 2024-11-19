<?php

namespace App\Command\Notifications;

use Exception;
use App\Util\AppUtil;
use App\Manager\NotificationsManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class SendNotificationCommand
 *
 * Command to send notification to all subscribers
 *
 * @package App\Command
 */
#[AsCommand(name: 'app:notifications:send', description: 'Send notification to all subscribers')]
class SendNotificationCommand extends Command
{
    private AppUtil $appUtil;
    private NotificationsManager $notificationsManager;

    public function __construct(AppUtil $appUtil, NotificationsManager $notificationsManager)
    {
        $this->appUtil = $appUtil;
        $this->notificationsManager = $notificationsManager;
        parent::__construct();
    }

    /**
     * Configures command arguments
     *
     * @return void
     */
    protected function configure(): void
    {
        $this->addArgument('message', InputArgument::REQUIRED, 'Notification message');
    }

    /**
     * Execute command to send notification to all subscribers
     *
     * @param InputInterface $input The input interface
     * @param OutputInterface $output The output interface
     *
     * @return int The status code
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // fix get CLI visitor info
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_USER_AGENT'] = 'console';

        // get message from input argument
        $message = $input->getArgument('message');

        // check input notification message is empty
        if (empty($message)) {
            $io->error('Message cannot be empty');
            return Command::FAILURE;
        }

        // check message input type
        if (!is_string($message)) {
            $io->error('Invalid message type provided');
            return Command::FAILURE;
        }

        // check is push notifications is enabled
        if ($this->appUtil->getEnvValue('PUSH_NOTIFICATIONS_ENABLED') != 'true') {
            $io->error('Push notifiations is disabled');
            return Command::FAILURE;
        }

        try {
            // send notification
            $this->notificationsManager->sendNotification(
                title: 'Admin-suite notification',
                message: $message
            );

            // return success status
            $io->success('Notification sent successfully.');
            return Command::SUCCESS;
        } catch (Exception $e) {
            $io->error('Error to send notification: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
