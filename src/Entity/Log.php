<?php

namespace App\Entity;

use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\LogRepository;

/**
 * Class Log
 *
 * Entity object for mapping database table
 *
 * @package App\Entity
 */
#[ORM\Table(name: 'logs')]
#[ORM\Index(name: 'logs_name_idx', columns: ['name'])]
#[ORM\Index(name: 'logs_time_idx', columns: ['time'])]
#[ORM\Index(name: 'logs_status_idx', columns: ['status'])]
#[ORM\Index(name: 'logs_user_id_idx', columns: ['user_id'])]
#[ORM\Index(name: 'logs_user_agent_idx', columns: ['user_agent'])]
#[ORM\Index(name: 'logs_ip_address_idx', columns: ['ip_address'])]
#[ORM\Entity(repositoryClass: LogRepository::class)]
class Log
{
    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $message = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?DateTimeInterface $time = null;

    #[ORM\Column(length: 255)]
    private ?string $user_agent = null;

    #[ORM\Column(length: 255)]
    private ?string $ip_address = null;

    #[ORM\Column]
    private ?int $level = null;

    #[ORM\ManyToOne(inversedBy: 'logs')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $user = null;

    #[ORM\Column(length: 255)]
    private ?string $status = null;

    /**
     * Get database ID of the log
     *
     * @return int|null The database ID of the log or null if not found
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Get name of the log
     *
     * @return string|null The name of the log or null if not found
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Set name of the log
     *
     * @param string $name The name of the log
     *
     * @return static The log object
     */
    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get message of the log
     *
     * @return string|null The message of the log or null if not found
     */
    public function getMessage(): ?string
    {
        return $this->message;
    }

    /**
     * Set message of the log
     *
     * @param string $message The message of the log
     *
     * @return static The log object
     */
    public function setMessage(string $message): static
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Get time of the log
     *
     * @return DateTimeInterface|null The time of the log or null if not found
     */
    public function getTime(): ?DateTimeInterface
    {
        return $this->time;
    }

    /**
     * Set time of the log
     *
     * @param DateTimeInterface $time The time of the log
     *
     * @return static The log object
     */
    public function setTime(DateTimeInterface $time): static
    {
        $this->time = $time;

        return $this;
    }

    /**
     * Get user agent of the log
     *
     * @return string|null The user agent of the log or null if not found
     */
    public function getUserAgent(): ?string
    {
        return $this->user_agent;
    }

    /**
     * Set user agent of the log
     *
     * @param string $user_agent The user agent of the log
     *
     * @return static The log object
     */
    public function setUserAgent(string $user_agent): static
    {
        // prevent maximal user agent length
        if (strlen($user_agent) > 255) {
            $user_agent = substr($user_agent, 0, 250) . "...";
        }

        $this->user_agent = $user_agent;

        return $this;
    }

    /**
     * Get ip address of the user who created the log
     *
     * @return string|null The ip address of the user who created the log or null if not found
     */
    public function getIpAddress(): ?string
    {
        return $this->ip_address;
    }

    /**
     * Set ip address of the user who created the log
     *
     * @param string $ip_address The ip address of the user who created the log
     *
     * @return static The log object
     */
    public function setIpAddress(string $ip_address): static
    {
        $this->ip_address = $ip_address;

        return $this;
    }

    /**
     * Get level of the log
     *
     * @return int|null The level of the log or null if not found
     */
    public function getLevel(): ?int
    {
        return $this->level;
    }

    /**
     * Set level of the log
     *
     * @param int|null $level The level of the log
     *
     * @return static The log object
     */
    public function setLevel(?int $level): static
    {
        $this->level = $level;

        return $this;
    }

    /**
     * Get user who created the log
     *
     * @return User|null The user who created the log or null if not found
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * Set user who created the log
     *
     * @param User $user The user who created the log
     *
     * @return static The log object
     */
    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get status of the log
     *
     * @return string|null The status of the log or null if not found
     */
    public function getStatus(): ?string
    {
        return $this->status;
    }

    /**
     * Set status of the log
     *
     * @param string $status The status of the log
     *
     * @return static The log object
     */
    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }
}
