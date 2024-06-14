<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\BannedRepository;

/**
 * Banned entity class
 *
 * The Banned entity is used to store banned users in the database
 *
 * @package App\Entity
 */
#[ORM\Table(name: 'ban_list')]
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

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(string $reason): static
    {
        $this->reason = $reason;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getTime(): ?\DateTimeInterface
    {
        return $this->time;
    }

    public function setTime(\DateTimeInterface $time): static
    {
        $this->time = $time;

        return $this;
    }

    public function getBannedById(): ?int
    {
        return $this->banned_by_id;
    }

    public function setBannedById(int $banned_by_id): static
    {
        $this->banned_by_id = $banned_by_id;

        return $this;
    }

    public function getBannedUserId(): ?int
    {
        return $this->banned_user_id;
    }

    public function setBannedUserId(int $banned_user_id): static
    {
        $this->banned_user_id = $banned_user_id;

        return $this;
    }
}
