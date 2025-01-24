<?php

namespace App\Repository;

use App\Entity\Banned;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * Class BannedRepository
 *
 * Repository for Banned database entity
 *
 * @extends ServiceEntityRepository<Banned>
 *
 * @package App\Repository
 */
class BannedRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Banned::class);
    }

    /**
     * Check if user is banned
     *
     * @param int $bannedUserId The ID of user
     *
     * @return bool True if the user is banned
     */
    public function isBanned(int $bannedUserId): bool
    {
        $ban = $this->findOneBy([
            'banned_user_id' => $bannedUserId,
            'status' => 'active'
        ]);

        return $ban !== null;
    }

    /**
     * Get ban reason
     *
     * @param int $bannedUserId The ID of banned user
     *
     * @return string|null The reason of banned user
     */
    public function getBanReason(int $bannedUserId): ?string
    {
        $ban = $this->findOneBy([
            'banned_user_id' => $bannedUserId,
            'status' => 'active'
        ]);

        return $ban ? $ban->getReason() : null;
    }

    /**
     * Update status of a banned user
     *
     * @param int $bannedUserId The ID of banned user
     * @param string $newStatus The new status of banned user
     *
     * @return void
     */
    public function updateBanStatus(int $bannedUserId, string $newStatus): void
    {
        $ban = $this->findOneBy([
            'banned_user_id' => $bannedUserId,
            'status' => 'active'
        ]);

        // update ban status
        if ($ban) {
            $ban->setStatus($newStatus);
            $this->getEntityManager()->flush();
        }
    }
}
