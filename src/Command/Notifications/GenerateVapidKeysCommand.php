<?php

namespace App\Command\Notifications;

use Exception;
use App\Util\AppUtil;
use App\Manager\NotificationsManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class GenerateVapidKeysCommand
 *
 * The command to generate VAPID keys for web push notifications
 *
 * @package App\Command
 */
#[AsCommand(name: 'app:notifications:vapid:keys:generate', description: 'Generate VAPID keys for web push notifications')]
class GenerateVapidKeysCommand extends Command
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
     * Execute the command to generate VAPID keys for web push notifications
     *
     * @param InputInterface $input The input interface
     * @param OutputInterface $output The output interface
     *
     * @return int The command exit code
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // fix get CLI ip address
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_USER_AGENT'] = 'console';

        // check if push notifications is enabled
        if ($this->appUtil->getEnvValue('PUSH_NOTIFICATIONS_ENABLED') != 'true') {
            $io->error('Push notifiations is disabled');
            return Command::FAILURE;
        }

        // confirmation before regenerating VAPID keys
        if (!$io->confirm('Do you really want to regenerate the VAPID keys? This will replace the existing ones.', false)) {
            $io->warning('VAPID keys regeneration was cancelled.');
            return Command::SUCCESS;
        }

        try {
            // regenerate VAPID keys
            $vapidKeys = $this->notificationsManager->regenerateVapidKeys();

            // print new VAPID keys
            $io->title('VAPID keys generated successfully.');
            $io->text('Public Key: ' . $vapidKeys['publicKey']);
            $io->text('Private Key: ' . $vapidKeys['privateKey']);

            // return success status
            $io->success('VAPID keys updated successfully.');
            return Command::SUCCESS;
        } catch (Exception $e) {
            $io->error('Error to generate VAPID keys: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
