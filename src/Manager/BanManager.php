<?php

namespace App\Manager;

use DateTime;
use Exception;
use App\Entity\Banned;
use App\Repository\BannedRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class BanManager
 *
 * The BanManager is responsible for managing user ban system
 *
 * @package App\Manager
 */
class BanManager
{
    private LogManager $logManager;
    private UserManager $userManager;
    private AuthManager $authManager;
    private ErrorManager $errorManager;
    private BannedRepository $bannedRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(
        LogManager $logManager,
        UserManager $userManager,
        AuthManager $authManager,
        ErrorManager $errorManager,
        BannedRepository $bannedRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->logManager = $logManager;
        $this->userManager = $userManager;
        $this->authManager = $authManager;
        $this->errorManager = $errorManager;
        $this->entityManager = $entityManager;
        $this->bannedRepository = $bannedRepository;
    }

    /**
     * Ban a user
     *
     * @param int $userId The id of the user to ban
     * @param string $reason The reason for banning the user
     *
     * @throws Exception If an error occurs while banning the user
     *
     * @return void
     */
    public function banUser(int $userId, string $reason = 'no-reason'): void
    {
        // check if user is already banned
        if ($this->isUserBanned($userId)) {
            return;
        }

        // set banned data
        $banned = new Banned();
        $banned->setReason($reason)
            ->setStatus('active')
            ->setTime(new DateTime())
            ->setBannedById($this->authManager->getLoggedUserId())
            ->setBannedUserId($userId);

        // ban user
        try {
            $this->entityManager->persist($banned);
            $this->entityManager->flush();
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error to ban user: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        // log action
        $this->logManager->log(
            name: 'ban-manager',
            message: 'user: ' . $userId . ' has been banned',
            level: LogManager::LEVEL_WARNING
        );
    }

    /**
     * Check if user is banned
     *
     * @param int $userId The id of the user
     *
     * @return bool The banned status of the user
     */
    public function isUserBanned(int $userId): bool
    {
        // check if user is banned
        return $this->bannedRepository->isBanned($userId);
    }

    /**
     * Get ban reason
     *
     * @param int $userId The id of the user
     *
     * @return string|null The reason for banning the user, or null if the user
     */
    public function getBanReason(int $userId): ?string
    {
        // check if banned repository exists
        if ($this->bannedRepository->isBanned($userId)) {
            return $this->bannedRepository->getBanReason($userId);
        }

        return null;
    }

    /**
     * Unban a user
     *
     * @param int $userId The id of the user to unban
     *
     * @return void
     */
    public function unBanUser(int $userId): void
    {
        // check if banned repository exists
        if ($this->bannedRepository->isBanned($userId)) {
            // unban user
            try {
                // set banned status to inactive
                $this->bannedRepository->updateBanStatus($userId, 'inactive');

                $this->entityManager->flush();
            } catch (Exception $e) {
                $this->errorManager->handleError(
                    message: 'error to unban user: ' . $e->getMessage(),
                    code: Response::HTTP_INTERNAL_SERVER_ERROR
                );
            }

            // log action
            $this->logManager->log(
                name: 'ban-manager',
                message: 'user: ' . $userId . ' is unbanned',
                level: LogManager::LEVEL_WARNING
            );
        }
    }

    /**
     * Get banned users list
     *
     * @return array<\App\Entity\User> The list of banned users
     */
    public function getBannedUsers(): array
    {
        $banned = [];

        /** @var \App\Entity\User $users all users list */
        $users = $this->userManager->getAllUsersRepositories();

        // check if $users is iterable
        if (is_iterable($users)) {
            foreach ($users as $user) {
                // check if user is banned
                if ($this->isUserBanned($user->getId())) {
                    $banned[] = $user;
                }
            }
        }

        return $banned;
    }

    /**
     * Get the count of banned users
     *
     * @return int The count of banned users
     */
    public function getBannedCount(): int
    {
        $repository = $this->entityManager->getRepository(Banned::class);

        // get banned count
        $count = $repository->count(['status' => 'active']);

        // return banned users count
        return $count;
    }
}
