<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\LogRepository;

/**
 * Class Log
 *
 * The Log entity database table mapping class
 *
 * @package App\Entity
 */
#[ORM\Table(name: 'logs')]
#[ORM\Index(name: 'name_idx', columns: ['name'])]
#[ORM\Index(name: 'time_idx', columns: ['time'])]
#[ORM\Index(name: 'status_idx', columns: ['status'])]
#[ORM\Index(name: 'user_id_idx', columns: ['user_id'])]
#[ORM\Index(name: 'user_agent_idx', columns: ['user_agent'])]
#[ORM\Index(name: 'ip_address_idx', columns: ['ip_address'])]
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
    private ?\DateTimeInterface $time = null;

    #[ORM\Column(length: 255)]
    private ?string $user_agent = null;

    #[ORM\Column(length: 255)]
    private ?string $ip_address = null;

    #[ORM\Column]
    private ?int $level = null;

    #[ORM\Column]
    private ?int $user_id = null;

    #[ORM\Column(length: 255)]
    private ?string $status = null;

    /**
     * Get the id of the log
     *
     * @return int|null The id of the log or null if not found
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Get the name of the log
     *
     * @return string|null The name of the log or null if not found
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Set the name of the log
     *
     * @param string $name The name of the log
     *
     * @return static The current object
     */
    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get the message of the log
     *
     * @return string|null The message of the log or null if not found
     */
    public function getMessage(): ?string
    {
        return $this->message;
    }

    /**
     * Set the message of the log
     *
     * @param string $message The message of the log
     *
     * @return static The current object
     */
    public function setMessage(string $message): static
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Get the time of the log
     *
     * @return \DateTimeInterface|null The time of the log or null if not found
     */
    public function getTime(): ?\DateTimeInterface
    {
        return $this->time;
    }

    /**
     * Set the time of the log
     *
     * @param \DateTimeInterface $time The time of the log
     *
     * @return static The current object
     */
    public function setTime(\DateTimeInterface $time): static
    {
        $this->time = $time;

        return $this;
    }

    /**
     * Get the user agent of the log
     *
     * @return string|null The user agent of the log or null if not found
     */
    public function getUserAgent(): ?string
    {
        return $this->user_agent;
    }

    /**
     * Set the user agent of the log
     *
     * @param string $user_agent The user agent of the log
     *
     * @return static The current object
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
     * Get the ip address of the log
     *
     * @return string|null The ip address of the log or null if not found
     */
    public function getIpAddress(): ?string
    {
        return $this->ip_address;
    }

    /**
     * Set the ip address of the log
     *
     * @param string $ip_address The ip address of the log
     *
     * @return static The current object
     */
    public function setIpAddress(string $ip_address): static
    {
        $this->ip_address = $ip_address;

        return $this;
    }

    /**
     * Get the level of the log
     *
     * @return int|null The level of the log or null if not found
     */
    public function getLevel(): ?int
    {
        return $this->level;
    }

    /**
     * Set the level of the log
     *
     * @param int|null $level The level of the log
     *
     * @return static The current object
     */
    public function setLevel(?int $level): static
    {
        $this->level = $level;

        return $this;
    }

    /**
     * Get the id of the user who created the log
     *
     * @return int|null The id of the user who created the log or null if not found
     */
    public function getUserId(): ?int
    {
        return $this->user_id;
    }

    /**
     * Set the id of the user who created the log
     *
     * @param int $user_id The id of the user who created the log
     *
     * @return static The current object
     */
    public function setUserId(int $user_id): static
    {
        $this->user_id = $user_id;

        return $this;
    }

    /**
     * Get the status of the log
     *
     * @return string|null The status of the log or null if not found
     */
    public function getStatus(): ?string
    {
        return $this->status;
    }

    /**
     * Set the status of the log
     *
     * @param string $status The status of the log
     *
     * @return static The current object
     */
    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }
}
