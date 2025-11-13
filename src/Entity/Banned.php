<?php

namespace App\Entity;

use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\BannedRepository;

/**
 * Class Banned
 *
 * Entity object for mapping database table
 *
 * @package App\Entity
 */
#[ORM\Table(name: 'ban_list')]
#[ORM\Index(name: 'ban_list_status_idx', columns: ['status'])]
#[ORM\Index(name: 'ban_list_banned_by_id_idx', columns: ['banned_by_id'])]
#[ORM\Index(name: 'ban_list_banned_user_id_idx', columns: ['banned_user_id'])]
#[ORM\Entity(repositoryClass: BannedRepository::class)]
class Banned
{
    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $reason = null;

    #[ORM\Column(length: 255)]
    private ?string $status = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?DateTimeInterface $time = null;

    #[ORM\ManyToOne(inversedBy: 'issuedBans')]
    #[ORM\JoinColumn(name: 'banned_by_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $bannedBy = null;

    #[ORM\ManyToOne(inversedBy: 'bans')]
    #[ORM\JoinColumn(name: 'banned_user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?User $bannedUser = null;

    /**
     * Get id of the banned status (database ID)
     *
     * @return int|null The database ID of the banned status or null if not found
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Get ban reason
     *
     * @return string|null The reason of the ban or null if not found
     */
    public function getReason(): ?string
    {
        return $this->reason;
    }

    /**
     * Set the ban reason
     *
     * @param string $reason The reason of the ban
     *
     * @return static The banned status object
     */
    public function setReason(string $reason): static
    {
        $this->reason = $reason;

        return $this;
    }

    /**
     * Get status of the ban
     *
     * @return string|null The status of the ban or null if not found
     */
    public function getStatus(): ?string
    {
        return $this->status;
    }

    /**
     * Set status of the ban
     *
     * @param string $status The status of the ban
     */
    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get time of the ban
     *
     * @return DateTimeInterface|null The time of the ban or null if not found
     */
    public function getTime(): ?DateTimeInterface
    {
        return $this->time;
    }

    /**
     * Set time of the ban
     *
     * @param DateTimeInterface $time The time of the ban
     *
     * @return static The banned status object
     */
    public function setTime(DateTimeInterface $time): static
    {
        $this->time = $time;

        return $this;
    }

    /**
     * Get admin who issued ban
     *
     * @return User|null The admin who issued ban or null if not found
     */
    public function getBannedBy(): ?User
    {
        return $this->bannedBy;
    }

    /**
     * Set admin who issued ban
     *
     * @param User $bannedBy The admin who issued ban
     *
     * @return static The banned status object
     */
    public function setBannedBy(?User $bannedBy): static
    {
        $this->bannedBy = $bannedBy;

        return $this;
    }

    /**
     * Get banned user
     *
     * @return User|null The banned user or null if not found
     */
    public function getBannedUser(): ?User
    {
        return $this->bannedUser;
    }

    /**
     * Set banned user
     *
     * @param User $bannedUser The banned user
     *
     * @return static The banned status object
     */
    public function setBannedUser(?User $bannedUser): static
    {
        $this->bannedUser = $bannedUser;

        return $this;
    }
}
