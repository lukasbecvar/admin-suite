<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\BannedRepository;

/**
 * Class Banned
 *
 * The Banned entity database table mapping class
 *
 * @package App\Entity
 */
#[ORM\Table(name: 'ban_list')]
#[ORM\Index(name: 'status_idx', columns: ['status'])]
#[ORM\Index(name: 'banned_by_id_idx', columns: ['banned_by_id'])]
#[ORM\Index(name: 'banned_user_id_idx', columns: ['banned_user_id'])]
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
    private ?\DateTimeInterface $time = null;

    #[ORM\Column]
    private ?int $banned_by_id = null;

    #[ORM\Column]
    private ?int $banned_user_id = null;

    /**
     * Get the id of the banned user
     *
     * @return int|null The id of the banned user or null if not found
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Get the reason of the ban
     *
     * @return string|null The reason of the ban or null if not found
     */
    public function getReason(): ?string
    {
        return $this->reason;
    }

    /**
     * Set the reason of the ban
     *
     * @param string $reason The reason of the ban
     *
     * @return static The current object
     */
    public function setReason(string $reason): static
    {
        $this->reason = $reason;

        return $this;
    }

    /**
     * Get the status of the ban
     *
     * @return string|null The status of the ban or null if not found
     */
    public function getStatus(): ?string
    {
        return $this->status;
    }

    /**
     * Set the status of the ban
     *
     * @param string $status The status of the ban
     */
    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get the time of the ban
     *
     * @return \DateTimeInterface|null The time of the ban or null if not found
     */
    public function getTime(): ?\DateTimeInterface
    {
        return $this->time;
    }

    /**
     * Set the time of the ban
     *
     * @param \DateTimeInterface $time The time of the ban
     *
     * @return static The current object
     */
    public function setTime(\DateTimeInterface $time): static
    {
        $this->time = $time;

        return $this;
    }

    /**
     * Get the id of the user who banned the user
     *
     * @return int|null The id of the user who banned the user or null if not found
     */
    public function getBannedById(): ?int
    {
        return $this->banned_by_id;
    }

    /**
     * Set the id of the user who banned the user
     *
     * @param int $banned_by_id The id of the user who banned the user
     *
     * @return static The current object
     */
    public function setBannedById(int $banned_by_id): static
    {
        $this->banned_by_id = $banned_by_id;

        return $this;
    }

    /**
     * Get the id of the banned user
     *
     * @return int|null The id of the banned user or null if not found
     */
    public function getBannedUserId(): ?int
    {
        return $this->banned_user_id;
    }

    /**
     * Set the id of the banned user
     *
     * @param int $banned_user_id The id of the banned user
     *
     * @return static The current object
     */
    public function setBannedUserId(int $banned_user_id): static
    {
        $this->banned_user_id = $banned_user_id;

        return $this;
    }
}
