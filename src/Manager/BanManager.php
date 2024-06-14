<?php

namespace App\Manager;

use App\Entity\Banned;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Class BanManager
 *
 * The BanManager is responsible for managing bans in the application.
 *
 * @package App\Manager
 */
class BanManager
{
    private LogManager $logManager;
    private AuthManager $authManager;
    private ErrorManager $errorManager;
    private EntityManagerInterface $entityManager;

    public function __construct(LogManager $logManager, AuthManager $authManager, ErrorManager $errorManager, EntityManagerInterface $entityManager)
    {
        $this->logManager = $logManager;
        $this->authManager = $authManager;
        $this->errorManager = $errorManager;
        $this->entityManager = $entityManager;
    }

    /**
     * Ban a user.
     *
     * @param int $userId The id of the user to ban.
     * @param string $reason The reason for banning the user.
     *
     * @return void
     */
    public function banUser(int $userId, string $reason = 'no-reason'): void
    {
        if ($this->isUserBanned($userId)) {
            return;
        }

        $banned = new Banned();

        // set banned data
        $banned->setReason($reason);
        $banned->setStatus('active');
        $banned->setTime(new \DateTime());
        $banned->setBannedById($this->authManager->getLoggedUserId());
        $banned->setBannedUserId($userId);

        // ban user
        try {
            $this->entityManager->persist($banned);
            $this->entityManager->flush();
        } catch (\Exception $e) {
            $this->errorManager->handleError('error to ban user: ' . $e->getMessage(), 500);
        }

        // log action
        $this->logManager->log('banned', 'banned user: ' . $userId);
    }

    /**
     * Check if user is banned.
     *
     * @param int $userId The id of the user.
     *
     * @return bool The banned status of the user.
     */
    public function isUserBanned(int $userId): bool
    {
        // check if user is banned
        return $this->entityManager->getRepository(Banned::class)->findOneBy(['banned_user_id' => $userId,'status' => 'active']) != null;
    }

    /**
     * Get ban reason.
     *
     * @param int $userId The id of the user.
     *
     * @return string|null The reason for banning the user, or null if the user
     */
    public function getBanReason(int $userId): ?string
    {
        // check if user is banned
        $banned = $this->entityManager->getRepository(Banned::class)->findOneBy(['banned_user_id' => $userId,'status' => 'active']);

        if ($banned) {
            return $banned->getReason();
        }

        return null;
    }

    /**
     * Unban a user.
     *
     * @param int $userId The id of the user to unban.
     *
     * @return void
     */
    public function unBanUser(int $userId): void
    {
        // check if user is banned
        $banned = $this->entityManager->getRepository(Banned::class)->findOneBy(['banned_user_id' => $userId,'status' => 'active']);

        if ($banned) {
            // unban user
            try {
                // set banned status to inactive
                $banned->setStatus('inactive');

                $this->entityManager->flush();
            } catch (\Exception $e) {
                $this->errorManager->handleError('error to unban user: ' . $e->getMessage(), 500);
            }

            // log action
            $this->logManager->log('unbanned', 'unbanned user: ' . $userId);
        }
    }
}
