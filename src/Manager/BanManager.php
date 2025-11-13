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
 * Manager for user ban list management
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
    private NotificationsManager $notificationsManager;

    public function __construct(
        LogManager $logManager,
        UserManager $userManager,
        AuthManager $authManager,
        ErrorManager $errorManager,
        BannedRepository $bannedRepository,
        EntityManagerInterface $entityManager,
        NotificationsManager $notificationsManager
    ) {
        $this->logManager = $logManager;
        $this->userManager = $userManager;
        $this->authManager = $authManager;
        $this->errorManager = $errorManager;
        $this->entityManager = $entityManager;
        $this->bannedRepository = $bannedRepository;
        $this->notificationsManager = $notificationsManager;
    }

    /**
     * Ban user
     *
     * @param int $userId The id of user to ban
     * @param string $reason The ban reason
     *
     * @return void
     */
    public function banUser(int $userId, string $reason = 'no-reason'): void
    {
        // check if user is already banned
        if ($this->isUserBanned($userId)) {
            $this->errorManager->handleError(
                message: 'user is already banned',
                code: Response::HTTP_BAD_REQUEST
            );
        }

        // get banned user and issuer
        $bannedUser = $this->userManager->getUserReference($userId);
        $issuer = $this->userManager->getUserReference($this->authManager->getLoggedUserId());
        if ($bannedUser === null || $issuer === null) {
            $this->errorManager->handleError(
                message: 'invalid user context for ban operation',
                code: Response::HTTP_BAD_REQUEST
            );
        }

        // create banned entity
        $banned = new Banned();
        $banned->setReason($reason)
            ->setStatus('active')
            ->setTime(new DateTime())
            ->setBannedBy($issuer)
            ->setBannedUser($bannedUser);

        // persist and flush ban to database
        try {
            $this->entityManager->persist($banned);
            $this->entityManager->flush();
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error to ban user: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        // close notifications subscriber
        $this->notificationsManager->updateNotificationsSubscriberStatus($userId, 'closed');

        // log ban event
        $this->logManager->log(
            name: 'ban-manager',
            message: 'user: ' . $userId . ' has been banned',
            level: LogManager::LEVEL_WARNING
        );
    }

    /**
     * Check if user is banned
     *
     * @param int $userId The id of user
     *
     * @return bool The banned status of user
     */
    public function isUserBanned(int $userId): bool
    {
        // check if user is banned
        try {
            return $this->bannedRepository->isBanned($userId);
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error to check if user is banned: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get ban reason
     *
     * @param int $userId The id of user
     *
     * @return string|null The ban reason, or null if user is not banned
     */
    public function getBanReason(int $userId): ?string
    {
        // check if banned repository exists (is user banned)
        if ($this->bannedRepository->isBanned($userId)) {
            // get ban reason
            $banReason = $this->bannedRepository->getBanReason($userId);

            // return ban reason
            return $banReason;
        }

        return null;
    }

    /**
     * Unban user
     *
     * @param int $userId The id of user to unban
     *
     * @return void
     */
    public function unBanUser(int $userId): void
    {
        // check if banned repository exists (is user banned)
        if ($this->bannedRepository->isBanned($userId)) {
            // unban user
            try {
                // set banned status to inactive
                $this->bannedRepository->updateBanStatus($userId, 'inactive');

                // flush changes to database
                $this->entityManager->flush();
            } catch (Exception $e) {
                $this->errorManager->handleError(
                    message: 'error to unban user: ' . $e->getMessage(),
                    code: Response::HTTP_INTERNAL_SERVER_ERROR
                );
            }

            // log unban event
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

        /** @var array<\App\Entity\User> $users all users list */
        $users = $this->userManager->getAllUsersRepositories();

        // check if $users is iterable
        if (is_iterable($users)) {
            foreach ($users as $user) {
                // get user id
                $userId = $user->getId();

                // check if user id is integer
                if (!is_integer($userId)) {
                    continue;
                }

                // check if user is banned
                if ($this->isUserBanned($userId)) {
                    $banned[] = $user;
                }
            }
        }

        return $banned;
    }

    /**
     * Get banned users count
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
